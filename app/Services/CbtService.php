<?php

namespace App\Services\Cbt;

use App\Exceptions\CbtException;
use App\Supports\Helper;   // your existing static helper class: change this line if it lives elsewhere
use App\Models\Cbt;
use App\Models\CbtAttempt;
use App\Models\CbtAttemptAnswer;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\TempQuestion;
use App\Models\TempQuestionGroup;
use App\Models\User;
use App\Notifications\CbtAssigned;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Everything CBT, in one place. Sections:
 *
 *   1. Authoring    create / update / delete / list a CBT and its questions
 *   2. Taking       start, resume, autosave, submit, grade, result
 *   3. Question rules   validation and clean-up shared by every way a question enters a CBT
 *   4. Bank         teacher request, save to my bank, remove, edit; admin vet, unvet, promote
 *   5. Maintenance  auto-submit overdue attempts, flush expired CBTs
 *   6. Pure helpers (no database; safe to unit test)
 *
 * Hard rule: correct answers leave the server only (a) in the author's own panel payloads,
 * (b) to teachers/admins in bank blocks, and (c) in resultFor(), after submission. The student
 * payload is built from an explicit whitelist.
 */
class CbtService
{
    /** Seconds past ends_at that a submit or autosave is still accepted (network lag, clock drift). */
    private const GRACE_SECONDS = 60;
    /** How long a CBT's temporary questions live after it closes, unless the author sets expires_at. */
    private const RETENTION_DAYS = 14;
    /** How many questions past the requested count a whole group may add. */
    private const OVERSHOOT = 4;
    private const MAX_BLOCKS = 200;
    private const GROUP_TYPES = ['gap', 'passage', 'diagram', 'audio', 'video'];
    private const DIFFICULTIES = ['substandard', 'standard', 'advanced'];
    /** Mirrors the frontend's media detection (utils/content.js). */
    private const MEDIA = '/^(data:(image|audio)\/\S+|\S+\.(png|jpe?g|gif|webp|avif|svg|mp3|wav|ogg|m4a|aac)(\?\S*)?)$/i';

    // ==================================================================
    // 1. AUTHORING
    // ==================================================================

    /**
     * WHAT IT DOES: Builds the list of rules used to check a CBT's own settings (title, type,
     *    timing and who it is for) before anything is saved. The date rules are added only when
     *    those dates were actually sent: the closing time must come after the opening time, and the
     *    expiry must not come before the closing time.
     *
     * USED WHEN: The controller calls it when a teacher creates or edits a CBT.
     *
     * GIVES BACK: An array of Laravel validation rules.
     */
    public function settingsRules(bool $creating, array $input = []): array
    {
        $required = $creating ? 'required' : 'sometimes';

        $closes = ['nullable', 'date'];
        if (! empty($input['opens_at'])) {
            $closes[] = 'after:opens_at';
        }

        $expires = ['nullable', 'date', 'after:now'];
        if (! empty($input['closes_at'])) {
            $expires[] = 'after_or_equal:closes_at';   // never flushed before it closes
        }

        return [
            'title' => [$required, 'string', 'max:255'],
            'type' => [$required, Rule::in(Cbt::TYPES)],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => $closes,
            'expires_at' => $expires,
            'blocks' => ['sometimes', 'array'],        // shape is checked by validateBlocks()
            'audience' => ['nullable', 'array'],       // who the CBT is for; null = the whole school
            'audience.classes' => ['sometimes', 'array', 'max:100'],
            'audience.classes.*' => ['integer', 'exists:my_classes,id'],
            'audience.enrollments' => ['sometimes', 'array', 'max:1000'],
            'audience.enrollments.*' => ['integer'],
        ];
    }

    /**
     * WHAT IT DOES: Lists the CBTs one person has set up, newest first, with how many questions and
     *    how many attempts each one has.
     *
     * USED WHEN: When a teacher opens their list of CBTs.
     *
     * GIVES BACK: A collection of CBTs (each with questions_count and attempts_count).
     */
    public function hostedBy(Model $actor): Collection
    {
        return Cbt::query()
            ->where('host_type', $actor->getMorphClass())
            ->where('host_id', $actor->getKey())
            ->withCount(['tempQuestions as questions_count', 'attempts'])
            ->latest('id')
            ->get();
    }

    /**
     * WHAT IT DOES: Creates a brand-new CBT. The school always comes from the person creating it,
     *    never from the request. It checks the questions first, then saves the CBT and its
     *    questions together (everything is saved, or nothing is), and finally tells the students it
     *    is for.
     *
     * USED WHEN: When a teacher saves a new CBT from the setting panel.
     *
     * GIVES BACK: The new CBT.
     */
    public function create(Model $host, array $meta, ?array $blocks = null): Cbt
    {
        if (! $host->school_id) {
            throw CbtException::notAllowed();
        }

        if (array_key_exists('audience', $meta)) {
            $meta['audience'] = $this->cleanAudience((int) $host->school_id, $meta['audience']);
        }

        $clean = $blocks === null ? null : $this->validateBlocks($blocks);   // before touching the database

        $cbt = DB::transaction(function () use ($host, $meta, $clean) {
            $cbt = new Cbt($meta + ['school_id' => $host->school_id]);        // school always comes from the host
            if (! $cbt->expires_at) {
                $cbt->expires_at = $this->defaultExpiry($cbt);
            }
            $cbt->host()->associate($host);
            $cbt->save();

            if ($clean) {
                $this->replaceBlocks($cbt, $host, $clean);
            }

            return $cbt;
        });

        $this->notifyAudience($cbt);                    // after the save is safely committed

        return $cbt;
    }

    /**
     * WHAT IT DOES: Changes a CBT's settings and/or replaces all its questions. Once any student
     *    has started, only the title and the expiry may change, because changing the time or the
     *    questions mid-exam would be unfair. It locks the CBT while checking, so a student starting
     *    at that very moment cannot slip through.
     *
     * USED WHEN: When the teacher saves changes to an existing CBT.
     *
     * GIVES BACK: The updated CBT.
     */
    public function update(Cbt $cbt, Model $actor, array $meta, ?array $blocks): Cbt
    {
        $this->assertHost($cbt, $actor);

        if (array_key_exists('audience', $meta)) {
            $meta['audience'] = $this->cleanAudience((int) $cbt->school_id, $meta['audience']);
        }

        $clean = $blocks === null ? null : $this->validateBlocks($blocks);

        $cbt = DB::transaction(function () use ($cbt, $actor, $meta, $clean) {
            // Lock the CBT so a student starting at this moment cannot slip past the check below.
            $cbt = Cbt::query()->lockForUpdate()->findOrFail($cbt->id);
            $locked = $cbt->attempts()->exists();

            $cbt->fill($meta);

            if ($locked && ($clean !== null || $cbt->isDirty(['type', 'duration_minutes', 'opens_at', 'closes_at']))) {
                throw CbtException::locked();
            }

            if ($cbt->closes_at && $cbt->expires_at && $cbt->expires_at->lt($cbt->closes_at)) {
                $cbt->expires_at = $this->defaultExpiry($cbt);
            }
            $cbt->save();

            if ($clean !== null) {
                $this->replaceBlocks($cbt, $actor, $clean);
            }

            return $cbt;
        });

        $this->notifyAudience($cbt);                    // covers a CBT that only got its questions now

        return $cbt;
    }

    /**
     * WHAT IT DOES: Deletes a CBT and its questions. It refuses while someone is in the middle of
     *    taking it. Students who already finished keep their scores.
     *
     * USED WHEN: When the teacher deletes a CBT.
     *
     * GIVES BACK: Nothing.
     */
    public function destroy(Cbt $cbt, Model $actor): void
    {
        $this->assertHost($cbt, $actor);

        DB::transaction(function () use ($cbt) {
            $cbt = Cbt::query()->lockForUpdate()->findOrFail($cbt->id);

            if ($cbt->attempts()->whereNull('submitted_at')->exists()) {
                throw CbtException::inProgress();
            }

            $cbt->delete();
        });
    }

    /**
     * WHAT IT DOES: Packs everything the editing panel needs into one array: the settings, whether
     *    the CBT is locked (someone already started) and all the questions WITH their correct
     *    answers. Only the person who set the CBT up may ever receive this.
     *
     * USED WHEN: When the teacher opens a CBT to edit it, and right after saving.
     *
     * GIVES BACK: An array ready to be sent as JSON.
     */
    public function authorPayload(Cbt $cbt): array
    {
        return [
            'cbt' => [
                'id' => $cbt->id,
                'title' => $cbt->title,
                'type' => $cbt->type,
                'duration_minutes' => $cbt->duration_minutes,
                'opens_at' => $cbt->opens_at?->toIso8601String(),
                'closes_at' => $cbt->closes_at?->toIso8601String(),
                'expires_at' => $cbt->expires_at?->toIso8601String(),
            ],
            'locked' => $cbt->attempts()->exists(),     // the panel disables editing when true
            'blocks' => $this->authorBlocks($cbt),
        ];
    }

    /**
     * WHAT IT DOES: Reads the CBT's questions from the database and arranges them in the same shape
     *    the setting panel sends them in, so the teacher can edit them and send them straight back.
     *    A passage and its questions stay together as one block.
     *
     * USED WHEN: Used by authorPayload.
     *
     * GIVES BACK: A list of blocks (single questions and groups).
     */
    public function authorBlocks(Cbt $cbt): array
    {
        $questions = $cbt->tempQuestions()
            ->orderByRaw('question_number is null')
            ->orderBy('question_number')
            ->orderBy('id')
            ->get();
        $groups = $cbt->tempGroups()->get()->keyBy('id');

        $shape = fn (TempQuestion $q) => [
            'text' => $q->text,
            'options' => $q->options,
            'correct_answers' => $q->correct_answers,
            'marks' => $q->marks,
            'subject_id' => $q->subject_id,
            'class_id' => $q->class_id,
            'topic_id' => $q->topic_id,
            'difficulty_level' => $q->difficulty_level,
            'source_question_id' => $q->source_question_id,
        ];

        $blocks = [];
        $seen = [];
        foreach ($questions as $q) {
            if ($q->group_id === null) {
                $blocks[] = ['kind' => 'question', 'question' => $shape($q)];
                continue;
            }
            if (isset($seen[$q->group_id])) {
                continue;
            }
            $seen[$q->group_id] = true;
            $g = $groups[$q->group_id];

            $blocks[] = [
                'kind' => 'group',
                'group' => [
                    'type' => $g->type,
                    'title' => $g->title,
                    'introduction' => $g->introduction,
                    'content' => $g->content,
                    'source_group_id' => $g->source_group_id,
                ],
                'questions' => $questions->where('group_id', $q->group_id)->sortBy('position')
                    ->map($shape)->values()->all(),
            ];
        }

        return $blocks;
    }

    /**
     * WHAT IT DOES: Throws away the CBT's current questions and saves the new set. Question numbers
     *    run 1, 2, 3 across the whole CBT, counting the questions inside passages too.
     *
     * USED WHEN: Inside create and update, after the questions have been checked.
     *
     * GIVES BACK: Nothing (it changes the database).
     */
    private function replaceBlocks(Cbt $cbt, Model $actor, array $clean): void
    {
        $allowed = $this->visibleSources($actor, $clean);

        $cbt->tempQuestions()->delete();
        $cbt->tempGroups()->delete();

        $number = 1;                                   // question_number runs across the whole CBT

        foreach ($clean as $block) {
            if ($block['kind'] === 'question') {
                $this->storeQuestion($cbt, $block['question'], null, $number++, $allowed);
                continue;
            }

            $g = $block['group'];
            $group = $cbt->tempGroups()->forceCreate([
                'source_group_id' => isset($allowed['g'][$g['source_group_id']]) ? $g['source_group_id'] : null,
                'type' => $g['type'],
                'title' => $g['title'],
                'introduction' => $g['introduction'],
                'content' => $g['content'],
            ]);

            foreach ($block['questions'] as $q) {
                $this->storeQuestion($cbt, $q, $group->id, $number++, $allowed);
            }
        }
    }

    /**
     * WHAT IT DOES: Saves one question row for a CBT.
     *
     * USED WHEN: Called by replaceBlocks, once for every question.
     *
     * GIVES BACK: Nothing.
     */
    private function storeQuestion(Cbt $cbt, array $q, ?int $groupId, int $number, array $allowed): void
    {
        $cbt->tempQuestions()->forceCreate([
            'group_id' => $groupId,
            'position' => $q['position'],
            'question_number' => $number,
            'source_question_id' => isset($allowed['q'][$q['source_question_id']]) ? $q['source_question_id'] : null,
            'subject_id' => $q['subject_id'],
            'class_id' => $q['class_id'],
            'topic_id' => $q['topic_id'],
            'difficulty_level' => $q['difficulty_level'],
            'text' => $q['text'],
            'type' => $q['type'],
            'marks' => $q['marks'],
            'options' => $q['options'],
            'correct_answers' => $q['correct_answers'],
        ]);
    }

    /**
     * WHAT IT DOES: Some questions are copied from the question bank and carry the id of the bank
     *    question they came from. This keeps that id only if the teacher is allowed to see that
     *    bank question (it is vetted, or their own). Otherwise the id is quietly dropped, so nobody
     *    can use ids to peek at other schools' questions. Student hosts never use the bank, so they
     *    get nothing.
     *
     * USED WHEN: While saving questions.
     *
     * GIVES BACK: Two lists of allowed ids: one for questions, one for groups.
     */
    private function visibleSources(Model $actor, array $clean): array
    {
        $none = ['q' => [], 'g' => []];
        if (! $actor instanceof User) {
            return $none;                              // student hosts never use the bank
        }

        $qIds = [];
        $gIds = [];
        foreach ($clean as $block) {
            if ($block['kind'] === 'question') {
                $qIds[] = $block['question']['source_question_id'];
                continue;
            }
            $gIds[] = $block['group']['source_group_id'];
            foreach ($block['questions'] as $q) {
                $qIds[] = $q['source_question_id'];
            }
        }
        $qIds = array_values(array_filter(array_unique($qIds)));
        $gIds = array_values(array_filter(array_unique($gIds)));

        $visible = fn ($q) => $q->where(fn ($w) => $w->where('vetted', true)->orWhere('created_by', $actor->getKey()));
        $ids = fn ($query, array $in) => $in ? $visible($query)->whereIn('id', $in)->pluck('id')->mapWithKeys(fn ($id) => [$id => true])->all() : [];

        return ['q' => $ids(Question::query(), $qIds), 'g' => $ids(QuestionGroup::query(), $gIds)];
    }

    /**
     * WHAT IT DOES: Works out when a CBT's temporary questions should be thrown away if the teacher
     *    did not pick a date: 14 days after it closes (or 14 days from now if it has no closing
     *    time).
     *
     * USED WHEN: When a CBT is created without an expiry, or its closing time moves past its
     *    expiry.
     *
     * GIVES BACK: A date.
     */
    private function defaultExpiry(Cbt $cbt): Carbon
    {
        return ($cbt->closes_at ?? now())->copy()->addDays(self::RETENTION_DAYS);
    }

    /**
     * WHAT IT DOES: Tidies the "who is this CBT for" choice. It keeps whole numbers only, removes
     *    repeats, and keeps only students from the same school, so nobody can aim a CBT at another
     *    school's students. If nothing is left it returns null, which means the whole school.
     *
     * USED WHEN: In create and update, before saving.
     *
     * GIVES BACK: null, or an array holding the class ids and the enrollment ids.
     */
    private function cleanAudience(int $schoolId, mixed $audience): ?array
    {
        if (! is_array($audience)) {
            return null;
        }

        $classes = array_values(array_unique(array_map('intval', $audience['classes'] ?? [])));
        $enrollments = array_values(array_unique(array_map('intval', $audience['enrollments'] ?? [])));

        if ($enrollments) {
            // Keep only this school's enrollments, so nobody can aim a CBT at another school's students.
            $enrollments = Enrollment::query()
                ->where('school_id', $schoolId)
                ->whereIn('id', $enrollments)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return ($classes || $enrollments) ? ['classes' => $classes, 'enrollments' => $enrollments] : null;
    }

    /**
     * WHAT IT DOES: Builds the database search that finds every currently enrolled student the CBT
     *    is for: the students in the chosen classes plus the students chosen one by one, all in the
     *    CBT's school.
     *
     * USED WHEN: When sending notifications.
     *
     * GIVES BACK: A query (it has not been run yet).
     */
    private function audienceEnrollments(Cbt $cbt): Builder
    {
        $audience = $cbt->audience ?? [];

        return Enrollment::query()
            ->current()                                   // only students who are currently enrolled
            ->where('school_id', $cbt->school_id)
            ->where(function ($w) use ($audience) {
                $w->whereIn('class_id', $audience['classes'] ?? [])
                    ->orWhereIn('id', $audience['enrollments'] ?? []);
            });
    }

    /**
     * WHAT IT DOES: Sends each targeted student an in-app notification and an email saying a CBT
     *    has been set for them. It happens only once per CBT, only if the CBT has a chosen audience
     *    and already has questions. It marks the CBT as "notified" first, so pressing save twice
     *    cannot send everything twice. A CBT open to the whole school does not email everybody.
     *
     * USED WHEN: Right after a CBT is created or updated.
     *
     * GIVES BACK: Nothing.
     */
    private function notifyAudience(Cbt $cbt): void
    {
        if ($cbt->notified_at || ! $cbt->audience || ! $cbt->tempQuestions()->exists()) {
            return;
        }

        // Claim the "already told them" flag first, so a double submit cannot send everything twice.
        $claimed = Cbt::query()->whereKey($cbt->id)->whereNull('notified_at')->update(['notified_at' => now()]);
        if (! $claimed) {
            return;
        }

        $notification = new CbtAssigned(
            $cbt->id,
            $cbt->title,
            $cbt->type,
            $cbt->opens_at?->toIso8601String(),
            $cbt->closes_at?->toIso8601String(),
            $cbt->duration_minutes,
        );

        $this->audienceEnrollments($cbt)->with('student')->chunkById(200, function ($enrollments) use ($notification) {
            Notification::send($enrollments->pluck('student')->filter()->unique('id'), $notification);
        });
    }

    /**
     * WHAT IT DOES: Checks that the person asking is the one who set this CBT up. If not, it stops
     *    with a "not allowed" error.
     *
     * USED WHEN: At the start of update, destroy and other author-only actions.
     *
     * GIVES BACK: Nothing (it throws an error when the check fails).
     */
    private function assertHost(Cbt $cbt, Model $actor): void
    {
        $isHost = $cbt->host_type === $actor->getMorphClass()
            && (int) $cbt->host_id === (int) $actor->getKey();

        if (! $isHost) {
            throw CbtException::notAllowed();
        }
    }

    // ==================================================================
    // 2. TAKING
    // ==================================================================

    /**
     * WHAT IT DOES: Lists the CBTs a person can take: same school, not a live quiz, not expired or
     *    closed, has questions, and aimed at them (their class, them personally, or open to the
     *    whole school). Each one also shows that person's own status: not started, in progress or
     *    submitted.
     *
     * USED WHEN: When a student opens their list of tests.
     *
     * GIVES BACK: A list of small arrays for the screen.
     */
    public function availableTo(Model $taker): array
    {
        $cbts = Cbt::query()
            ->where('school_id', $taker->school_id)
            ->where('type', '!=', 'quiz')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()))
            ->whereHas('tempQuestions')
            ->latest('id')
            ->get()
            ->filter(fn (Cbt $cbt) => $cbt->canBeTakenBy($taker))   // hides CBTs aimed at other classes or students
            ->values();

        $attempts = CbtAttempt::query()
            ->whereIn('cbt_id', $cbts->pluck('id'))
            ->where('taker_type', $taker->getMorphClass())
            ->where('taker_id', $taker->getKey())
            ->get()
            ->keyBy('cbt_id');

        return $cbts->map(function (Cbt $cbt) use ($attempts) {
            $attempt = $attempts->get($cbt->id);

            return [
                'id' => $cbt->id,
                'title' => $cbt->title,
                'type' => $cbt->type,
                'duration_minutes' => $cbt->duration_minutes,
                'opens_at' => $cbt->opens_at?->toIso8601String(),
                'closes_at' => $cbt->closes_at?->toIso8601String(),
                'open_now' => $cbt->isOpen(),
                'status' => $attempt ? ($attempt->submitted_at ? 'submitted' : 'in_progress') : 'not_started',
                'attempt_id' => $attempt?->id,
            ];
        })->values()->all();
    }

    /**
     * WHAT IT DOES: Starts the test for a person, or continues one they already started. If an
     *    unfinished attempt exists it returns that one, so a refresh never restarts the clock. It
     *    refuses when the CBT is a live quiz, the person is not allowed, the CBT is closed, it has
     *    no questions, or they already finished it (no retakes). Otherwise it creates the attempt
     *    with a start time and a deadline.
     *
     * USED WHEN: When a student presses Start.
     *
     * GIVES BACK: The attempt.
     */
    public function startAttempt(Cbt $cbt, Model $taker): CbtAttempt
    {
        if ($cbt->type === 'quiz') {
            throw CbtException::wrongMode();
        }
        if (! $cbt->canBeTakenBy($taker)) {
            throw CbtException::notAllowed();
        }
        if (! $cbt->isOpen()) {
            throw CbtException::closed();
        }
        if (! $cbt->tempQuestions()->exists()) {
            throw CbtException::empty();
        }

        return DB::transaction(function () use ($cbt, $taker) {
            $mine = fn () => CbtAttempt::query()
                ->where('cbt_id', $cbt->id)
                ->where('taker_type', $taker->getMorphClass())
                ->where('taker_id', $taker->getKey());

            $open = $mine()->whereNull('submitted_at')->lockForUpdate()->latest('id')->first();
            if ($open) {
                return $open;
            }

            if ($mine()->whereNotNull('submitted_at')->exists()) {
                throw CbtException::alreadyTaken();
            }

            $attempt = new CbtAttempt([
                'cbt_id' => $cbt->id,
                'started_at' => now(),
                'ends_at' => $this->deadlineFor($cbt),
            ]);
            $attempt->taker()->associate($taker);
            $attempt->save();

            return $attempt;
        });
    }

    /**
     * WHAT IT DOES: Works out when this attempt must end: now plus the duration. If the CBT has a
     *    closing time that comes sooner, the closing time wins for everyone.
     *
     * USED WHEN: Inside startAttempt.
     *
     * GIVES BACK: A date, or null when there is no time limit.
     */
    private function deadlineFor(Cbt $cbt): ?Carbon
    {
        $ends = $cbt->duration_minutes ? now()->addMinutes($cbt->duration_minutes) : null;

        // A hard closing time always wins over the personal timer.
        if ($cbt->closes_at && (! $ends || $cbt->closes_at->lt($ends))) {
            $ends = $cbt->closes_at->copy();
        }

        return $ends;
    }

    /**
     * WHAT IT DOES: Builds what the student's browser receives: the CBT details, the server's
     *    current time and the deadline (so the countdown is right even if the phone's clock is
     *    wrong), the answers already saved, and all the questions. A passage appears once with its
     *    questions inside it. It NEVER contains a correct answer.
     *
     * USED WHEN: Right after startAttempt.
     *
     * GIVES BACK: An array ready to be sent as JSON.
     */
    public function attemptPayload(CbtAttempt $attempt): array
    {
        $cbt = $attempt->cbt;

        $questions = $cbt->tempQuestions()
            ->orderByRaw('question_number is null')
            ->orderBy('question_number')
            ->orderBy('id')
            ->get();
        $groups = $cbt->tempGroups()->get()->keyBy('id');

        // A group appears once, at the place of its first question. The frontend numbers everything.
        $blocks = [];
        $seen = [];
        foreach ($questions as $q) {
            if ($q->group_id === null) {
                $blocks[] = ['kind' => 'question', 'question' => $this->studentQuestion($q)];
                continue;
            }
            if (isset($seen[$q->group_id])) {
                continue;
            }
            $seen[$q->group_id] = true;
            $group = $groups[$q->group_id];

            $blocks[] = [
                'kind' => 'group',
                'group' => [
                    'id' => $group->id,
                    'type' => $group->type,
                    'title' => $group->title,
                    'introduction' => $group->introduction,
                    'content' => $group->content,          // gap passages carry {{n}} tokens
                ],
                'questions' => $questions->where('group_id', $q->group_id)
                    ->sortBy('position')
                    ->map(fn (TempQuestion $c) => $this->studentQuestion($c))
                    ->values()
                    ->all(),
            ];
        }

        $saved = $attempt->answers()->whereNotNull('answer')->pluck('answer', 'temp_question_id');

        return [
            'attempt_id' => $attempt->id,
            'server_time' => now()->toIso8601String(),
            'ends_at' => $attempt->ends_at?->toIso8601String(),
            'cbt' => [
                'id' => $cbt->id,
                'title' => $cbt->title,
                'type' => $cbt->type,
                'duration_minutes' => $cbt->duration_minutes,
            ],
            'saved_answers' => (object) $saved->all(),      // (object) so an empty list is {} not []
            'blocks' => $blocks,
        ];
    }

    /**
     * WHAT IT DOES: Turns one question into the version a student is allowed to see, using a fixed
     *    list of allowed fields. It never includes the correct answers. It only says whether to
     *    draw checkboxes (more than one correct answer) without saying which options are right.
     *
     * USED WHEN: Used by attemptPayload for every question.
     *
     * GIVES BACK: A small array.
     */
    private function studentQuestion(TempQuestion $q): array
    {
        return [
            'id' => $q->id,
            'position' => $q->position,
            'text' => $q->text,
            'options' => $q->options,
            // lets the UI draw checkboxes without revealing which options are right
            'multiple' => $q->options !== null && count($q->correct_answers ?? []) > 1,
            'marks' => $q->marks,
        ];
    }

    /**
     * WHAT IT DOES: Autosave. Stores the answers a student has ticked so far so nothing is lost if
     *    the page closes. It refuses if the attempt is already submitted or the time is well over
     *    (a one-minute allowance covers slow networks).
     *
     * USED WHEN: Every time the student pauses while answering.
     *
     * GIVES BACK: Nothing.
     */
    public function saveAnswers(CbtAttempt $attempt, array $answers): void
    {
        if ($attempt->submitted_at) {
            throw CbtException::alreadySubmitted();
        }
        if ($this->isPastDeadline($attempt)) {
            throw CbtException::timeUp();
        }

        $this->persistAnswers($attempt, $answers);
    }

    /**
     * WHAT IT DOES: Does the real saving for autosave and submit. It ignores any question that is
     *    not part of this CBT, tidies each answer, deletes the answer if the student cleared it,
     *    and saves or updates the rest in one go.
     *
     * USED WHEN: Called by saveAnswers and submit.
     *
     * GIVES BACK: Nothing.
     */
    private function persistAnswers(CbtAttempt $attempt, array $answers): void
    {
        // Only questions that belong to this CBT may be answered.
        $valid = $attempt->cbt->tempQuestions()->pluck('id')->flip();
        $now = now();
        $rows = [];
        $clear = [];

        foreach ($answers as $id => $value) {
            $id = (int) $id;
            if (! $valid->has($id)) {
                continue;
            }
            $clean = $this->cleanAnswer($value);
            if ($clean === null) {
                $clear[] = $id;
                continue;
            }
            $rows[] = [
                'cbt_attempt_id' => $attempt->id,
                'temp_question_id' => $id,
                'answer' => json_encode($clean),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($clear) {
            $attempt->answers()->whereIn('temp_question_id', $clear)->delete();
        }
        if ($rows) {
            CbtAttemptAnswer::upsert($rows, ['cbt_attempt_id', 'temp_question_id'], ['answer', 'updated_at']);
        }
    }

    /**
     * WHAT IT DOES: Tidies one answer: trims spaces, cuts it to a safe length, removes blanks and
     *    repeats. An empty answer becomes null, meaning "no answer".
     *
     * USED WHEN: Used by persistAnswers for every answer.
     *
     * GIVES BACK: A string, a list of strings, or null.
     */
    private function cleanAnswer(mixed $value): string|array|null
    {
        if (is_string($value)) {
            $v = trim($value);

            return $v === '' ? null : mb_substr($v, 0, 2000);
        }

        if (is_array($value)) {
            $items = collect($value)
                ->filter(fn ($v) => is_string($v))
                ->map(fn ($v) => mb_substr(trim($v), 0, 2000))
                ->filter(fn ($v) => $v !== '')
                ->unique()
                ->values()
                ->all();

            return $items ?: null;
        }

        return null;
    }

    /**
     * WHAT IT DOES: Says whether an attempt's time is over, allowing one extra minute for slow
     *    networks.
     *
     * USED WHEN: Before accepting an autosave or a submit.
     *
     * GIVES BACK: True or false.
     */
    private function isPastDeadline(CbtAttempt $attempt): bool
    {
        return $attempt->ends_at !== null
            && now()->gt($attempt->ends_at->copy()->addSeconds(self::GRACE_SECONDS));
    }

    /**
     * WHAT IT DOES: The student finishes. It locks the attempt so a double-click cannot grade it
     *    twice, saves the final answers (only if time is not over; after that only what was
     *    autosaved counts), grades everything and returns the result. If the attempt was already
     *    submitted it just returns the same result again.
     *
     * USED WHEN: When the student presses Submit, or when the timer runs out.
     *
     * GIVES BACK: The result (score, total and the questions they got wrong).
     */
    public function submit(CbtAttempt $attempt, array $answers): array
    {
        return DB::transaction(function () use ($attempt, $answers) {
            // Lock so a double-click or a retry cannot grade twice.
            $attempt = CbtAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            // Idempotent: a retried submit just returns the same result.
            if ($attempt->submitted_at) {
                return $this->resultFor($attempt);
            }

            // Past the deadline only what was autosaved in time counts.
            if (! $this->isPastDeadline($attempt)) {
                $final = [];
                foreach ($answers as $a) {
                    if (isset($a['id'])) {
                        $final[$a['id']] = $a['answer'] ?? null;
                    }
                }
                $this->persistAnswers($attempt, $final);
            }

            $this->grade($attempt);

            return $this->resultFor($attempt->refresh());
        });
    }

    /**
     * WHAT IT DOES: Marks the attempt. For every question it checks the student's answer, adds up
     *    the marks, and saves one row per question (answered or not) saying whether it was right
     *    and how many marks were given. Then it stamps the attempt with the submit time, the score
     *    and the total.
     *
     * USED WHEN: Inside submit.
     *
     * GIVES BACK: Nothing.
     */
    private function grade(CbtAttempt $attempt): void
    {
        $questions = $attempt->cbt->tempQuestions()->get();
        $given = $attempt->answers()->pluck('answer', 'temp_question_id');
        $now = now();

        $rows = [];
        $score = 0;
        $total = 0;

        foreach ($questions as $q) {
            $answer = $given->get($q->id);
            // options === null is what makes a question subjective.
            $correct = $this->isCorrect($answer, $q->options, $q->correct_answers ?? []);
            $marks = $correct ? $q->marks : 0;

            $score += $marks;
            $total += $q->marks;

            // Every question gets a row, answered or not, so the review screen is complete.
            $rows[] = [
                'cbt_attempt_id' => $attempt->id,
                'temp_question_id' => $q->id,
                'answer' => $answer === null ? null : json_encode($answer),
                'is_correct' => $correct,
                'marks_awarded' => $marks,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            CbtAttemptAnswer::upsert(
                $chunk,
                ['cbt_attempt_id', 'temp_question_id'],
                ['answer', 'is_correct', 'marks_awarded', 'updated_at'],
            );
        }

        $attempt->forceFill(['submitted_at' => $now, 'score' => $score, 'total' => $total]  + $this->snapshot($attempt->cbt))->save();
    }



    /** WHAT IT DOES: Copies the CBT's title, and its subject when every question shares one, onto the attempt so they outlive a flush. USED WHEN: Inside grade(). GIVES BACK: ['cbt_title' => ..., 'subject_id' => id|null]. */
private function snapshot(Cbt $cbt): array
{
    $subjects = $cbt->tempQuestions()->distinct()->pluck('subject_id');

    return ['cbt_title' => $cbt->title, 'subject_id' => $subjects->count() === 1 ? $subjects->first() : null];
}

/** WHAT IT DOES: Every submitted attempt by students of one class for one subject, 
 * with the student id. USED WHEN: By GradeService when previewing or pushing. 
 * GIVES BACK: A collection (id, cbt_title, score, total, pushed_at, student_id). */
public function attemptsFor(int $schoolId, int $classId, int $subjectId)
{
    $enrollment = new Enrollment;

    return CbtAttempt::query()
        ->join($enrollment->getTable() . ' as e', function ($j) use ($enrollment) {
            $j->on('e.id', '=', 'cbt_attempts.taker_id')->where('cbt_attempts.taker_type', $enrollment->getMorphClass());
        })
        ->where('e.school_id', $schoolId)->where('e.class_id', $classId)
        ->where('cbt_attempts.subject_id', $subjectId)
        ->whereNotNull('cbt_attempts.submitted_at')
        ->get(['cbt_attempts.id', 'cbt_attempts.cbt_title', 'cbt_attempts.score', 'cbt_attempts.total', 'cbt_attempts.pushed_at', 'e.student_id']);
}

/** WHAT IT DOES: Stamps attempts as pushed to the grades. 
 * USED WHEN: After a successful push. 
 * GIVES BACK: Nothing. */
public function markPushed(array $attemptIds): void
{
    CbtAttempt::whereIn('id', $attemptIds)->update(['pushed_at' => now()]);
}

/** WHAT IT DOES: Deletes attempts that were pushed AND whose CBT is already gone, to free space.
 *  Attempts on a live CBT stay, because they enforce "no retakes".
 *  USED WHEN: From GradeService::compile().
 *  GIVES BACK: How many were deleted. */
public function purgePushedAttempts(int $schoolId, int $classId, int $subjectId): int
{
    $ids = $this->attemptsFor($schoolId, $classId, $subjectId)
        ->filter(fn ($a) => $a->pushed_at !== null)->pluck('id');

    return CbtAttempt::whereIn('id', $ids)->whereNull('cbt_id')->delete();
}

    /**
     * WHAT IT DOES: Builds the result screen: score, total, percentage and the questions the
     *    student got wrong, each with what they chose and the correct answer. This is the only
     *    place a student ever sees correct answers, and only after submitting. If the CBT's
     *    questions have already been deleted it says the review is no longer available.
     *
     * USED WHEN: After submit, or when the student reopens their result.
     *
     * GIVES BACK: An array ready to be sent as JSON.
     */
    public function resultFor(CbtAttempt $attempt): array
    {
        if (! $attempt->submitted_at) {
            throw CbtException::notSubmitted();
        }

        $rows = $attempt->answers()
            ->where('is_correct', false)
            ->with('question.group')
            ->get()
            ->filter(fn (CbtAttemptAnswer $row) => $row->question !== null);

        // Temp questions are flushed after the CBT expires. The score outlives them,
        // but the question-by-question review does not.
        $reviewAvailable = $attempt->answers()->exists();

        $failed = $rows
            ->sortBy(fn ($row) => [$row->question->question_number ?? PHP_INT_MAX, $row->question->id])
            ->map(function (CbtAttemptAnswer $row) {
                $q = $row->question;

                return [
                    'id' => $q->id,
                    'text' => $q->text !== ''
                        ? $q->text
                        : sprintf('Gap %d in “%s”', $q->position, $q->group?->title ?? 'passage'),
                    'chosen' => $row->answer,               // string | string[] | null (decoded by the cast)
                    'correct_answers' => $q->correct_answers,
                ];
            })
            ->values()
            ->all();

        return [
            'score' => $attempt->score,
            'total' => $attempt->total,
            'percentage' => $attempt->total ? (int) round($attempt->score / $attempt->total * 100) : 0,
            'review_available' => $reviewAvailable,
            'failed' => $failed,
        ];
    }

    /**
     * WHAT IT DOES: Decides whether one answer is right. For multiple choice, the chosen option(s)
     *    must exactly match the correct set, in any order. For typed answers, the text must match
     *    one of the accepted answers after cleaning (capital letters, extra spaces and punctuation
     *    at the ends are ignored). All or nothing, no part marks.
     *
     * USED WHEN: While grading.
     *
     * GIVES BACK: True or false.
     */
    public function isCorrect(string|array|null $given, ?array $options, array $accepted): bool
    {
        if ($given === null || $given === '' || $given === []) {
            return false;
        }

        if ($options !== null) {
            return Helper::sameSet(is_array($given) ? $given : [$given], $accepted);
        }

        if (is_array($given)) {
            return false;
        }

        $typed = Helper::normalizeText($given);
        if ($typed === '') {
            return false;
        }

        foreach ($accepted as $candidate) {
            if ($typed === Helper::normalizeText((string) $candidate)) {
                return true;
            }
        }

        return false;
    }

    // ==================================================================
    // 3. QUESTION RULES
    // One set of rules for every way a question enters a CBT: typed by hand, or pulled from the
    // bank into the panel and edited. Nothing is relaxed because it first came from the backend.
    //
    // Input blocks:
    //   ['kind' => 'question', 'question' => {...}]
    //   ['kind' => 'group', 'group' => {type,title,introduction,content}, 'questions' => [{...}]]
    // each question: {text, options|null, correct_answers[], marks?, subject_id, class_id,
    //                 topic_id, difficulty_level?, source_question_id?}
    // ==================================================================

    /**
     * WHAT IT DOES: The gatekeeper for questions. It checks every question that comes in, whether
     *    typed by hand or pulled from the bank and edited, with the same rules, and returns them
     *    cleaned. If anything is wrong it stops with errors that point at the exact field.
     *
     * USED WHEN: Before saving questions (create, update, and editing a bank question).
     *
     * GIVES BACK: The cleaned list of blocks.
     */
    public function validateBlocks(array $blocks): array
    {
        $payload = ['blocks' => $blocks];
        $normalized = [];

        $validator = Validator::make($payload, $this->blockRules($payload));
        $validator->after(function ($v) use ($payload, &$normalized) {
            if ($v->errors()->isNotEmpty()) {
                return; // the shape must be sound before the deeper checks run
            }
            $normalized = $this->normalizeBlocks($payload['blocks'], fn (string $key, string $msg) => $v->errors()->add($key, $msg));
        });
        $validator->validate();

        return $normalized;
    }

    /**
     * WHAT IT DOES: Builds the basic shape checks (is it a list, does every question have text,
     *    options, answers, subject, class and topic) one block at a time.
     *
     * USED WHEN: Inside validateBlocks.
     *
     * GIVES BACK: An array of rules.
     */
    private function blockRules(array $payload): array
    {
        $rules = [
            'blocks' => ['required', 'array', 'min:1', 'max:' . self::MAX_BLOCKS],
            'blocks.*.kind' => ['required', Rule::in(['question', 'group'])],
        ];

        foreach (($payload['blocks'] ?? []) as $i => $block) {
            if (! is_array($block)) {
                continue;
            }
            $kind = $block['kind'] ?? null;

            if ($kind === 'question') {
                $rules["blocks.$i.question"] = ['required', 'array'];
                $rules += $this->questionRules("blocks.$i.question");
            } elseif ($kind === 'group') {
                $rules["blocks.$i.group"] = ['required', 'array'];
                $rules["blocks.$i.group.type"] = ['required', Rule::in(self::GROUP_TYPES)];
                $rules["blocks.$i.group.title"] = ['required', 'string', 'max:255'];
                $rules["blocks.$i.group.introduction"] = ['nullable', 'string', 'max:2000'];
                $rules["blocks.$i.group.content"] = ['required', 'string', 'max:20000'];
                $rules["blocks.$i.group.source_group_id"] = ['nullable', 'integer'];
                $rules["blocks.$i.questions"] = ['required', 'array', 'min:1', 'max:100'];

                foreach (array_keys(is_array($block['questions'] ?? null) ? $block['questions'] : []) as $j) {
                    $rules["blocks.$i.questions.$j"] = ['required', 'array'];
                    $rules += $this->questionRules("blocks.$i.questions.$j");
                }
            }
        }

        return $rules;
    }

    /**
     * WHAT IT DOES: The shape rules for one question: lengths, 2 to 10 options, at least one
     *    correct answer, marks between 1 and 20, and that the subject, class and topic really
     *    exist.
     *
     * USED WHEN: Used by blockRules for every question.
     *
     * GIVES BACK: An array of rules.
     */
    private function questionRules(string $p): array
    {
        return [
            "$p.text" => ['nullable', 'string', 'max:5000'],
            "$p.options" => ['nullable', 'array', 'min:2', 'max:10'],
            "$p.options.*" => ['required', 'string', 'max:2000'],
            "$p.correct_answers" => ['required', 'array', 'min:1', 'max:10'],
            "$p.correct_answers.*" => ['required', 'string', 'max:2000'],
            "$p.marks" => ['sometimes', 'integer', 'min:1', 'max:20'],
            "$p.subject_id" => ['required', 'integer', 'exists:subjects,id'],
            "$p.class_id" => ['required', 'integer', 'exists:my_classes,id'],
            "$p.topic_id" => ['required', 'integer', 'exists:subject_topics,id'],
            "$p.difficulty_level" => ['sometimes', Rule::in(self::DIFFICULTIES)],
            "$p.source_question_id" => ['nullable', 'integer'],   // set when pulled from the bank
        ];
    }

    /**
     * WHAT IT DOES: The deeper checks and clean-up, done after the basic shape is fine. It trims
     *    text, decides objective or subjective from the options, and rejects an answer that is not
     *    one of the options, repeated options, questions where every option is correct, and word-
     *    for-word duplicate questions in the same CBT. It also turns gap markers into numbered
     *    tokens. It needs no database, so it is easy to test.
     *
     * USED WHEN: Inside validateBlocks.
     *
     * GIVES BACK: The cleaned blocks.
     */
    public function normalizeBlocks(array $blocks, callable $fail): array
    {
        $out = [];
        $seen = [];

        foreach ($blocks as $i => $block) {
            $base = "blocks.$i";

            if (($block['kind'] ?? null) === 'group') {
                $out[] = $this->normalizeGroup($block, $base, $fail, $seen);
                continue;
            }

            $q = $this->normalizeQuestion($block['question'], "$base.question", $fail, false, null, null);
            $this->rememberHash($q, "$base.question.text", $fail, $seen);
            $out[] = ['kind' => 'question', 'question' => $q];
        }

        return $out;
    }

    /**
     * WHAT IT DOES: Checks and cleans a passage, diagram or audio group and its questions. For a
     *    gap passage it turns each -- into {{1}}, {{2}}..., makes sure the number of gaps equals
     *    the number of questions, makes sure every gap has plain-text options, and numbers the
     *    questions inside it 1, 2, 3.
     *
     * USED WHEN: Inside normalizeBlocks.
     *
     * GIVES BACK: The cleaned group block.
     */
    private function normalizeGroup(array $block, string $base, callable $fail, array &$seen): array
    {
        $g = $block['group'];
        $isGap = $g['type'] === 'gap';
        $content = trim($g['content']);
        $children = array_values($block['questions']);

        if ($isGap) {
            [$content, $gaps] = $this->tokenizeGaps($content, "$base.group.content", $fail);
            if ($gaps !== count($children)) {
                $fail("$base.questions", sprintf('This passage has %d gap(s) but %d question(s). They must match.', $gaps, count($children)));
            }
        }

        $questions = [];
        foreach ($children as $j => $child) {
            $position = $j + 1;
            $path = "$base.questions.$j";

            // A gap's own text is blank; the passage carries the sentence.
            $q = $this->normalizeQuestion($child, $path, $fail, $isGap, $content, $position);

            if ($isGap) {
                if ($q['options'] === null) {
                    $fail("$path.options", 'A gap needs options to choose from.');
                } elseif ($this->hasMedia($q['options'])) {
                    $fail("$path.options", 'Gap options must be plain text, not images or sound.');
                }
            }

            $this->rememberHash($q, "$path.text", $fail, $seen);
            $q['position'] = $position;
            $questions[] = $q;
        }

        $intro = isset($g['introduction']) ? trim((string) $g['introduction']) : '';

        return [
            'kind' => 'group',
            'group' => [
                'type' => $g['type'],
                'title' => trim($g['title']),
                'introduction' => $intro === '' ? null : $intro,
                'content' => $content,
                'source_group_id' => isset($g['source_group_id']) ? (int) $g['source_group_id'] : null,
            ],
            'questions' => $questions,
        ];
    }

    /**
     * WHAT IT DOES: Checks and cleans one question and gives it a fingerprint (content hash) used
     *    to spot duplicates.
     *
     * USED WHEN: Inside normalizeBlocks and normalizeGroup.
     *
     * GIVES BACK: The cleaned question.
     */
    private function normalizeQuestion(array $q, string $path, callable $fail, bool $textOptional, ?string $groupContent, ?int $position): array
    {
        $text = trim((string) ($q['text'] ?? ''));
        if ($text === '' && ! $textOptional) {
            $fail("$path.text", 'Write the question text.');
        }

        $options = null;
        if (isset($q['options'])) {
            $options = array_values(array_map(fn ($o) => trim((string) $o), $q['options']));
            if (count(array_unique($options)) !== count($options)) {
                $fail("$path.options", 'Each option must be different.');
            }
        }

        $answers = array_values(array_unique(array_map(fn ($a) => trim((string) $a), $q['correct_answers'])));

        if ($options !== null) {
            foreach ($answers as $answer) {
                if (! in_array($answer, $options, true)) {
                    $fail("$path.correct_answers", 'Every correct answer must be one of the options.');
                    break;
                }
            }
            if (count($answers) >= count($options)) {
                $fail("$path.correct_answers", 'At least one option must be wrong.');
            }
        }

        return [
            'position' => null,
            'text' => $text,
            'options' => $options,                         // null = subjective
            'correct_answers' => $answers,
            'type' => $options === null ? 'subjective' : 'objective',
            'marks' => (int) ($q['marks'] ?? 1),
            'subject_id' => (int) $q['subject_id'],
            'class_id' => (int) $q['class_id'],
            'topic_id' => (int) $q['topic_id'],
            'difficulty_level' => $q['difficulty_level'] ?? 'standard',
            'source_question_id' => isset($q['source_question_id']) ? (int) $q['source_question_id'] : null,
            'content_hash' => self::hashFor($text, $options, $answers, $groupContent, $position),
        ];
    }

    /**
     * WHAT IT DOES: Finds the blanks in a gap passage. The teacher may type -- for every blank, or
     *    write {{1}}, {{2}} themselves, but not both. It turns dashes into numbered {{n}} markers,
     *    checks the numbers go 1, 2, 3 in order, and counts the blanks. A run of three dashes is
     *    left alone.
     *
     * USED WHEN: Inside normalizeGroup, for gap passages.
     *
     * GIVES BACK: The passage with numbered markers, and the number of blanks.
     */
    private function tokenizeGaps(string $content, string $path, callable $fail): array
    {
        $tokenCount = preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $content, $m);
        $dashCount = preg_match_all('/(?<!-)--(?!-)/', $content);

        if ($tokenCount && $dashCount) {
            $fail($path, 'Use either -- or {{n}} to mark gaps, not both.');

            return [$content, $tokenCount];
        }

        if ($tokenCount) {
            if (array_map('intval', $m[1]) !== range(1, $tokenCount)) {
                $fail($path, 'Gaps must be numbered 1, 2, 3 in order.');
            }

            return [preg_replace('/\{\{\s*(\d+)\s*\}\}/', '{{$1}}', $content), $tokenCount];
        }

        if ($dashCount === 0) {
            $fail($path, 'Add at least one gap, written as --');

            return [$content, 0];
        }

        $n = 0;
        $tokenised = preg_replace_callback('/(?<!-)--(?!-)/', function () use (&$n) {
            return '{{' . (++$n) . '}}';
        }, $content);

        return [$tokenised, $n];
    }

    /**
     * WHAT IT DOES: Makes the "fingerprint" of a question from its text, options and answers (order
     *    and extra spaces do not matter). Two questions with the same fingerprint are word-for-word
     *    the same. For passage questions the passage and position are included, so two different
     *    passages that happen to share the same options are not duplicates.
     *
     * USED WHEN: When checking for duplicates.
     *
     * GIVES BACK: A 64-character text.
     */
    public static function hashFor(string $text, ?array $options, array $answers, ?string $groupContent = null, ?int $position = null): string
    {
        $sorted = function (array $items): array {
            $items = array_values(array_map(fn ($v) => trim((string) $v), $items));
            sort($items, SORT_STRING);

            return $items;
        };

        return Helper::hashOf([
            $groupContent,
            $position,
            trim($text),
            $options === null ? null : $sorted($options),
            $sorted($answers),
        ]);
    }

    /**
     * WHAT IT DOES: Notes down a question's fingerprint, and complains if the same fingerprint was
     *    already seen in this CBT.
     *
     * USED WHEN: Inside normalizeBlocks and normalizeGroup.
     *
     * GIVES BACK: Nothing.
     */
    private function rememberHash(array $q, string $path, callable $fail, array &$seen): void
    {
        if (isset($seen[$q['content_hash']])) {
            $fail($path, 'This question appears more than once in this CBT.');
        }
        $seen[$q['content_hash']] = true;
    }

    /**
     * WHAT IT DOES: Says whether any option is a picture or sound file (judged by the file ending).
     *
     * USED WHEN: To refuse picture or sound options inside gap passages.
     *
     * GIVES BACK: True or false.
     */
    private function hasMedia(array $options): bool
    {
        foreach ($options as $option) {
            if (preg_match(self::MEDIA, $option)) {
                return true;
            }
        }

        return false;
    }

    // ==================================================================
    // 4. BANK
    // school_id says which school a row came from (null = company), created_by is the author,
    // vetted = true puts it in the shared "Edukittas questions" set. Teachers never write vetted
    // rows; only the admin methods do.
    // ==================================================================

    // ---- teacher: ask for questions (nothing is written; the panel reviews them) ----

    /**
     * WHAT IT DOES: A teacher asks the bank for questions by subject, class, topic, difficulty and
     *    number. "mine" means questions this teacher wrote; "edukittas" means vetted questions from
     *    any school. If difficulty is left out it mixes all levels (topic is still enforced).
     *    Passages come whole and may overshoot the number by a few; if fewer are found it returns
     *    what exists with a message. Nothing is saved here: the panel shows them for review.
     *
     * USED WHEN: When the teacher presses "get questions" in the setting panel. Teachers and staff
     *    only.
     *
     * GIVES BACK: The blocks, how many were asked for and returned, and a message when fewer were
     *    found.
     */
    public function requestQuestions(Model $actor, array $criteria): array
    {
        // Teachers and staff only. A student host can never pull from the bank.
        if (! $actor instanceof User) {
            throw CbtException::notAllowed();
        }

        $data = Validator::make($criteria, [
            'scope' => ['required', Rule::in(['mine', 'edukittas'])],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'class_id' => ['required', 'integer', 'exists:my_classes,id'],
            'topic_id' => ['required', 'integer', 'exists:subject_topics,id'],
            'difficulty_level' => ['nullable', Rule::in(self::DIFFICULTIES)],
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ])->validate();

        $questions = Question::query()
            ->where('subject_id', $data['subject_id'])
            ->where('class_id', $data['class_id'])
            ->where('topic_id', $data['topic_id']);

        if (! empty($data['difficulty_level'])) {
            $questions->where('difficulty_level', $data['difficulty_level']);
        }

        $scope = fn (Builder $query) => $data['scope'] === 'mine'
            ? $query->where('created_by', $actor->getKey())->where('school_hidden', false)
            : $query->where('vetted', true);

        $scope($questions);

        $count = (int) $data['count'];
        $units = $this->pickUnits($questions, $scope, $count * 3 + 20);
        [$blocks, $taken] = $this->selectUnits($units, $count, self::OVERSHOOT);

        return [
            'blocks' => $blocks,
            'requested' => $count,
            'returned' => $taken,
            'message' => $taken < $count
                ? sprintf('Only %d of the %d questions you asked for were found.', $taken, $count)
                : null,
        ];
    }

    // ---- teacher: keep a CBT's questions for later ----

    /**
     * WHAT IT DOES: Copies a CBT's questions into the teacher's school bank so they can be reused
     *    later. Word-for-word duplicates are skipped, and a passage is skipped as a whole if any of
     *    its questions is a duplicate.
     *
     * USED WHEN: When the teacher presses "save to my bank".
     *
     * GIVES BACK: How many were saved and how many were skipped.
     */
    public function saveToMyBank(Cbt $cbt, Model $actor): array
    {
        $isHost = $actor instanceof User
            && $cbt->host_type === $actor->getMorphClass()
            && (int) $cbt->host_id === (int) $actor->getKey()
            && (int) $actor->school_id === (int) $cbt->school_id;
        if (! $isHost) {
            throw CbtException::notAllowed();
        }

        $groups = $cbt->tempGroups()->with('questions')->get();
        $standalone = $cbt->tempQuestions()->whereNull('group_id')->get();

        $hash = fn (TempQuestion $q, ?string $groupContent) => self::hashFor(
            $q->text, $q->options, $q->correct_answers, $groupContent, $groupContent === null ? null : $q->position,
        );

        $hashes = $standalone->map(fn ($q) => $hash($q, null))->all();
        foreach ($groups as $g) {
            foreach ($g->questions as $q) {
                $hashes[] = $hash($q, $g->content);
            }
        }

        $existing = $hashes === [] ? [] : Question::query()
            ->whereIn('content_hash', $hashes)
            ->where(fn ($w) => $w->where('school_id', $cbt->school_id)->orWhere('vetted', true))
            ->pluck('content_hash')->mapWithKeys(fn ($h) => [$h => true])->all();

        return DB::transaction(function () use ($cbt, $actor, $groups, $standalone, $hash, $existing) {
            $saved = 0;
            $skipped = 0;

            foreach ($standalone as $q) {
                $h = $hash($q, null);
                if (isset($existing[$h])) {
                    $skipped++;
                    continue;
                }
                $this->makeBankQuestion($q, $h, $cbt->school_id, $actor->getKey(), false, null, null);
                $saved++;
            }

            foreach ($groups as $g) {
                $children = $g->questions->sortBy('position')->values();
                $hs = $children->map(fn ($q) => $hash($q, $g->content));

                if ($children->isEmpty() || $hs->contains(fn ($h) => isset($existing[$h]))) {
                    $skipped += max($children->count(), 1);
                    continue;
                }

                $group = $this->makeBankGroup($g, $cbt->school_id, $actor->getKey(), false);
                foreach ($children as $i => $q) {
                    $this->makeBankQuestion($q, $hs[$i], $cbt->school_id, $actor->getKey(), false, $group->id, $q->position);
                    $saved++;
                }
            }

            return ['saved' => $saved, 'skipped' => $skipped];
        });
    }

    /**
     * WHAT IT DOES: Removes one standalone question from a school's bank list. A question that
     *    belongs to a passage cannot be removed on its own.
     *
     * USED WHEN: When a teacher deletes a question from their bank.
     *
     * GIVES BACK: "hidden" or "deleted".
     */
    public function removeQuestion(User $actor, Question $question): string
    {
        if ($question->group_id !== null) {
            throw CbtException::notAllowed();               // groups are removed whole, never one question
        }

        return $this->removeBankRow($actor, $question);
    }

    /**
     * WHAT IT DOES: Removes a whole passage, with all its questions, from a school's bank list.
     *
     * USED WHEN: When a teacher deletes a passage from their bank.
     *
     * GIVES BACK: "hidden" or "deleted".
     */
    public function removeGroup(User $actor, QuestionGroup $group): string
    {
        return $this->removeBankRow($actor, $group);
    }

    /**
     * WHAT IT DOES: Shared by removeQuestion and removeGroup. A vetted question already belongs to
     *    the shared set, so it is only hidden from this school's list. Anything else is really
     *    deleted.
     *
     * USED WHEN: Inside removeQuestion and removeGroup.
     *
     * GIVES BACK: "hidden" or "deleted".
     */
    private function removeBankRow(User $actor, Question|QuestionGroup $item): string
    {
        $this->assertOwnsRow($actor, $item);

        if ($item->vetted) {
            $item->forceFill(['school_hidden' => true])->save();

            return 'hidden';
        }

        $item->delete();                                    // a group's questions go with it (cascade)

        return 'deleted';
    }

    /**
     * WHAT IT DOES: Edits one standalone bank question using the same checks as any other question.
     *    A vetted question can be edited by an admin only; any other by its author or their school.
     *    It refuses a word-for-word duplicate. Questions inside a passage are not edited one by
     *    one.
     *
     * USED WHEN: When someone presses Edit on a bank question.
     *
     * GIVES BACK: The updated question.
     */
    public function updateBankQuestion(User $actor, Question $question, array $data): Question
    {
        if ($question->group_id !== null) {
            throw CbtException::notAllowed();
        }
        $this->assertCanEdit($actor, $question);

        $q = $this->validateBlocks([['kind' => 'question', 'question' => $data]])[0]['question'];

        $duplicate = Question::query()
            ->where('content_hash', $q['content_hash'])
            ->where('id', '!=', $question->id)
            ->where(fn ($w) => $w->where('vetted', true)->orWhere('school_id', $question->school_id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['text' => 'This question already exists.']);
        }

        $question->forceFill([
            'text' => $q['text'],
            'type' => $q['type'],
            'marks' => $q['marks'],
            'options' => $q['options'],
            'correct_answers' => $q['correct_answers'],
            'subject_id' => $q['subject_id'],
            'class_id' => $q['class_id'],
            'topic_id' => $q['topic_id'],
            'difficulty_level' => $q['difficulty_level'],
            'content_hash' => $q['content_hash'],
        ])->save();

        return $question;
    }

    /**
     * WHAT IT DOES: Decides who may edit a bank row: an admin for a vetted one, the author or
     *    someone from the owning school for any other. It stops with an error if the person is not
     *    allowed.
     *
     * USED WHEN: Before editing a bank row.
     *
     * GIVES BACK: Nothing (it throws an error when the check fails).
     */
    public function assertCanEdit(User $actor, Question|QuestionGroup $item): void
    {
        if ($item->vetted) {
            $this->assertAdmin($actor);

            return;
        }

        $this->assertOwnsRow($actor, $item);
    }

    // ---- admin: vetting ----

    /**
     * WHAT IT DOES: For the admin: shows questions that are not vetted yet, for one subject and
     *    topic, from any school, ready to review (100 by default).
     *
     * USED WHEN: When the admin opens the vetting screen.
     *
     * GIVES BACK: The blocks and how many were returned.
     */
    public function vettingList(User $admin, array $criteria): array
    {
        $this->assertAdmin($admin);

        $data = Validator::make($criteria, [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'topic_id' => ['required', 'integer', 'exists:subject_topics,id'],
            'class_id' => ['nullable', 'integer', 'exists:my_classes,id'],
            'difficulty_level' => ['nullable', Rule::in(self::DIFFICULTIES)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ])->validate();

        $limit = (int) ($data['limit'] ?? 100);

        $questions = Question::query()->where('vetted', false)
            ->where('subject_id', $data['subject_id'])
            ->where('topic_id', $data['topic_id']);
        if (! empty($data['class_id'])) {
            $questions->where('class_id', $data['class_id']);
        }
        if (! empty($data['difficulty_level'])) {
            $questions->where('difficulty_level', $data['difficulty_level']);
        }

        $units = $this->pickUnits($questions, fn (Builder $groups) => $groups->where('vetted', false), $limit * 2 + 20);
        [$blocks, $taken] = $this->selectUnits($units, $limit, 10);

        return ['blocks' => $blocks, 'returned' => $taken];
    }

    /**
     * WHAT IT DOES: The admin marks questions or whole passages as vetted ("standard"), in place:
     *    the row keeps its school and author but joins the shared Edukittas set. Picking one
     *    question of a passage vets the whole passage. A word-for-word duplicate of something
     *    already vetted is skipped.
     *
     * USED WHEN: When the admin presses Vet on the selected questions.
     *
     * GIVES BACK: How many were vetted and how many were skipped as duplicates.
     */
    public function vet(User $admin, array $questionIds = [], array $groupIds = []): array
    {
        $this->assertAdmin($admin);

        return DB::transaction(function () use ($questionIds, $groupIds) {
            $groupIds = $this->withParentGroups($questionIds, $groupIds);

            $vetted = 0;
            $skipped = 0;

            $single = Question::whereIn('id', $questionIds)->whereNull('group_id')->where('vetted', false)->get();
            foreach ($single as $q) {
                if ($this->vettedDuplicateExists([$q->content_hash], [$q->id])) {
                    $skipped++;
                    continue;
                }
                $q->forceFill(['vetted' => true])->save();
                $vetted++;
            }

            $groups = QuestionGroup::whereIn('id', $groupIds)->where('vetted', false)->with('questions')->get();
            foreach ($groups as $g) {
                $ids = $g->questions->pluck('id')->all();
                if ($this->vettedDuplicateExists($g->questions->pluck('content_hash')->all(), $ids)) {
                    $skipped += count($ids);
                    continue;
                }
                $g->forceFill(['vetted' => true])->save();
                Question::where('group_id', $g->id)->update(['vetted' => true]);
                $vetted += count($ids);
            }

            return ['vetted' => $vetted, 'skipped_duplicates' => $skipped];
        });
    }

    /**
     * WHAT IT DOES: The admin takes questions or whole passages back out of the shared set.
     *
     * USED WHEN: When the admin presses Un-vet.
     *
     * GIVES BACK: How many questions changed.
     */
    public function unvet(User $admin, array $questionIds = [], array $groupIds = []): int
    {
        $this->assertAdmin($admin);

        return DB::transaction(function () use ($questionIds, $groupIds) {
            $groupIds = $this->withParentGroups($questionIds, $groupIds);

            $n = Question::whereIn('id', $questionIds)->whereNull('group_id')->where('vetted', true)->update(['vetted' => false]);
            $n += Question::whereIn('group_id', $groupIds)->where('vetted', true)->update(['vetted' => false]);
            QuestionGroup::whereIn('id', $groupIds)->update(['vetted' => false]);

            return $n;
        });
    }

    /**
     * WHAT IT DOES: The admin copies questions from a CBT's temporary questions into the bank as
     *    vetted, keeping the school and the author. Picking one question of a passage brings the
     *    whole passage. If the same question is already in the bank it is linked (and vetted if it
     *    was not) instead of being copied again. Questions already promoted are ignored.
     *
     * USED WHEN: When the admin presses Promote on temporary questions.
     *
     * GIVES BACK: How many were promoted, how many existing ones got vetted, and how many were
     *    already in the bank.
     */
    public function promoteFromTemp(User $admin, array $tempQuestionIds): array
    {
        $this->assertAdmin($admin);

        return DB::transaction(function () use ($tempQuestionIds) {
            $picked = TempQuestion::with('cbt')->whereIn('id', $tempQuestionIds)->lockForUpdate()->get();
            $groupIds = $picked->pluck('group_id')->filter()->unique()->values();

            $standalone = $picked->whereNull('group_id')->whereNull('promoted_question_id');
            $groups = $groupIds->isEmpty()
                ? collect()
                : TempQuestionGroup::with(['cbt', 'questions'])->whereIn('id', $groupIds)->whereNull('promoted_group_id')->lockForUpdate()->get();
            $counts = ['promoted' => 0, 'vetted_existing' => 0, 'already_in_bank' => 0];

            foreach ($standalone as $tq) {
                $this->promoteStandalone($tq, $counts);
            }
            foreach ($groups as $tg) {
                $this->promoteGroup($tg, $counts);
            }

            return $counts;
        });
    }

    /**
     * WHAT IT DOES: Promotes one standalone temporary question: reuses the identical bank question
     *    if there is one, otherwise creates a new vetted one. It then remembers the link on the
     *    temporary question so it cannot be promoted twice.
     *
     * USED WHEN: Inside promoteFromTemp.
     *
     * GIVES BACK: Nothing (it updates the counts it was given).
     */
    private function promoteStandalone(TempQuestion $tq, array &$counts): void
    {
        $hash = self::hashFor($tq->text, $tq->options, $tq->correct_answers);

        $existing = Question::where('content_hash', $hash)->whereNull('group_id')->orderByDesc('vetted')->first();
        if ($existing) {
            if ($existing->vetted) {
                $counts['already_in_bank']++;
            } else {
                $existing->forceFill(['vetted' => true])->save();
                $counts['vetted_existing']++;
            }
            $tq->forceFill(['promoted_question_id' => $existing->id])->save();

            return;
        }

        $cbt = $tq->cbt;
        $q = $this->makeBankQuestion($tq, $hash, $cbt->school_id, $this->authorOf($cbt), true, null, null);
        $tq->forceFill(['promoted_question_id' => $q->id])->save();
        $counts['promoted']++;
    }

    /**
     * WHAT IT DOES: Promotes a whole temporary passage with its questions. If the same passage is
     *    already in the bank it reuses it (vetting it if needed), otherwise it creates a vetted
     *    copy of the passage and every question. It remembers the links so nothing is promoted
     *    twice.
     *
     * USED WHEN: Inside promoteFromTemp.
     *
     * GIVES BACK: Nothing (it updates the counts it was given).
     */
    private function promoteGroup(TempQuestionGroup $tg, array &$counts): void
    {
        $children = $tg->questions->sortBy('position')->values();
        if ($children->isEmpty()) {
            return;
        }

        $hashes = $children->map(fn (TempQuestion $q) => self::hashFor(
            $q->text, $q->options, $q->correct_answers, $tg->content, $q->position,
        ));

        // The same passage already in the bank? Reuse it rather than duplicate it.
        $match = Question::where('content_hash', $hashes[0])->whereNotNull('group_id')->first();
        if ($match) {
            $group = QuestionGroup::with('questions')->find($match->group_id);

            if ($group->vetted) {
                $counts['already_in_bank'] += $group->questions->count();
            } else {
                $group->forceFill(['vetted' => true])->save();
                Question::where('group_id', $group->id)->update(['vetted' => true]);
                $counts['vetted_existing'] += $group->questions->count();
            }

            foreach ($children as $child) {
                $child->forceFill(['promoted_question_id' => $group->questions->firstWhere('position', $child->position)?->id])->save();
            }
            $tg->forceFill(['promoted_group_id' => $group->id])->save();

            return;
        }

        $cbt = $tg->cbt;
        $author = $this->authorOf($cbt);
        $group = $this->makeBankGroup($tg, $cbt->school_id, $author, true);

        foreach ($children as $i => $child) {
            $q = $this->makeBankQuestion($child, $hashes[$i], $cbt->school_id, $author, true, $group->id, $child->position);
            $child->forceFill(['promoted_question_id' => $q->id])->save();
            $counts['promoted']++;
        }
        $tg->forceFill(['promoted_group_id' => $group->id])->save();
    }

    // ---- bank helpers ----

    /**
     * WHAT IT DOES: Creates one bank question from a temporary question, with its owner school,
     *    author, fingerprint and vetted flag.
     *
     * USED WHEN: Inside saveToMyBank and the promote methods.
     *
     * GIVES BACK: The new bank question.
     */
    private function makeBankQuestion(TempQuestion $q, string $hash, ?int $schoolId, ?int $authorId, bool $vetted, ?int $groupId, ?int $position): Question
    {
        return Question::forceCreate([
            'school_id' => $schoolId,
            'created_by' => $authorId,
            'vetted' => $vetted,
            'school_hidden' => false,
            'content_hash' => $hash,
            'group_id' => $groupId,
            'position' => $position,
            'subject_id' => $q->subject_id,
            'class_id' => $q->class_id,
            'topic_id' => $q->topic_id,
            'difficulty_level' => $q->difficulty_level,
            'text' => $q->text,
            'type' => $q->type,
            'marks' => $q->marks,
            'options' => $q->options,
            'correct_answers' => $q->correct_answers,
        ]);
    }

    /**
     * WHAT IT DOES: Creates one bank passage from a temporary passage, with its owner school,
     *    author and vetted flag.
     *
     * USED WHEN: Inside saveToMyBank and promoteGroup.
     *
     * GIVES BACK: The new bank passage.
     */
    private function makeBankGroup(TempQuestionGroup $g, ?int $schoolId, ?int $authorId, bool $vetted): QuestionGroup
    {
        return QuestionGroup::forceCreate([
            'school_id' => $schoolId,
            'created_by' => $authorId,
            'vetted' => $vetted,
            'school_hidden' => false,
            'type' => $g->type,
            'title' => $g->title,
            'introduction' => $g->introduction,
            'content' => $g->content,
        ]);
    }

    /**
     * WHAT IT DOES: Finds the user id of the teacher who set a CBT up. It gives null when a student
     *    set it up.
     *
     * USED WHEN: When promoting, to record who wrote the question.
     *
     * GIVES BACK: A user id or null.
     */
    private function authorOf(Cbt $cbt): ?int
    {
        return $cbt->host_type === (new User)->getMorphClass() ? (int) $cbt->host_id : null;
    }

    /**
     * WHAT IT DOES: Takes the picked question ids and group ids and returns the group ids plus the
     *    groups those questions belong to, so a passage is always handled as a whole.
     *
     * USED WHEN: Inside vet and unvet.
     *
     * GIVES BACK: A list of group ids.
     */
    private function withParentGroups(array $questionIds, array $groupIds): array
    {
        return array_values(array_unique(array_merge(
            $groupIds,
            Question::whereIn('id', $questionIds)->whereNotNull('group_id')->pluck('group_id')->all(),
        )));
    }

    /**
     * WHAT IT DOES: Checks whether a vetted question with the same fingerprint already exists (not
     *    counting the ones you name).
     *
     * USED WHEN: Inside vet, to avoid vetting the same question twice.
     *
     * GIVES BACK: True or false.
     */
    private function vettedDuplicateExists(array $hashes, array $exceptIds): bool
    {
        return Question::whereIn('content_hash', $hashes)->where('vetted', true)->whereNotIn('id', $exceptIds)->exists();
    }

    /**
     * WHAT IT DOES: Stops with an error unless the user is an admin.
     *
     * USED WHEN: At the start of every admin-only action.
     *
     * GIVES BACK: Nothing (it throws an error when the check fails).
     */
    private function assertAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            throw CbtException::notAdmin();
        }
    }

    /**
     * WHAT IT DOES: Stops with an error unless the person is the author, or belongs to the school
     *    that owns this bank row.
     *
     * USED WHEN: Before removing or editing a bank row.
     *
     * GIVES BACK: Nothing (it throws an error when the check fails).
     */
    private function assertOwnsRow(User $actor, Question|QuestionGroup $item): void
    {
        $owns = ($item->school_id !== null && (int) $item->school_id === (int) $actor->school_id)
            || (int) $item->created_by === (int) $actor->getKey();

        if (! $owns) {
            throw CbtException::notAllowed();
        }
    }

    // ---- bank rows as panel blocks ----

    /**
     * WHAT IT DOES: Turns one bank question into the shape the setting panel uses, correct answers
     *    included (only teachers and admins ever receive this), and remembers which bank question
     *    it came from.
     *
     * USED WHEN: Used by pickUnits.
     *
     * GIVES BACK: A small array.
     */
    private function bankQuestionShape(Question $q): array
    {
        return [
            'text' => $q->text,
            'options' => $q->options,
            'correct_answers' => $q->correct_answers,
            'marks' => $q->marks,
            'subject_id' => $q->subject_id,
            'class_id' => $q->class_id,
            'topic_id' => $q->topic_id,
            'difficulty_level' => $q->difficulty_level,
            'source_question_id' => $q->id,
        ];
    }

    /**
     * WHAT IT DOES: Gathers the questions and whole passages that match a search, in random order.
     *    A passage matches when at least one of its questions matches, and then the whole passage
     *    is taken.
     *
     * USED WHEN: Inside requestQuestions and vettingList.
     *
     * GIVES BACK: A shuffled list of items, each with its size and its block.
     */
    private function pickUnits(Builder $questions, \Closure $groupScope, int $pool): Collection
    {
        $standalone = (clone $questions)->whereNull('group_id')->inRandomOrder()->limit($pool)->get();

        $groupIds = (clone $questions)->whereNotNull('group_id')
            ->inRandomOrder()->limit($pool * 5)->pluck('group_id')->unique()->take($pool)->values();

        $groups = $groupIds->isEmpty()
            ? collect()
            : $groupScope(QuestionGroup::query())->whereIn('id', $groupIds)->with('questions')->get();

        $units = collect();
        foreach ($standalone as $q) {
            $units->push(['size' => 1, 'block' => ['kind' => 'question', 'question' => $this->bankQuestionShape($q)]]);
        }
        foreach ($groups as $g) {
            if ($g->questions->isEmpty()) {
                continue;
            }
            $units->push(['size' => $g->questions->count(), 'block' => [
                'kind' => 'group',
                'group' => [
                    'type' => $g->type,
                    'title' => $g->title,
                    'introduction' => $g->introduction,
                    'content' => $g->content,
                    'source_group_id' => $g->id,
                ],
                'questions' => $g->questions->map(fn (Question $c) => $this->bankQuestionShape($c))->values()->all(),
            ]]);
        }

        return $units->shuffle()->values();
    }

    /**
     * WHAT IT DOES: From the shuffled list, takes items until the wanted number is reached. An item
     *    that would overshoot by more than a few is skipped, so a passage may add a little extra
     *    but never a lot. It needs no database, so it is easy to test.
     *
     * USED WHEN: Inside requestQuestions and vettingList.
     *
     * GIVES BACK: The chosen blocks and how many questions they hold.
     */
    public function selectUnits(Collection $units, int $count, int $overshoot): array
    {
        $blocks = [];
        $taken = 0;

        foreach ($units as $unit) {
            if ($taken >= $count) {
                break;
            }
            if ($taken + $unit['size'] > $count + $overshoot) {
                continue;
            }
            $blocks[] = $unit['block'];
            $taken += $unit['size'];
        }

        return [$blocks, $taken];
    }

    // ==================================================================
    // 5. MAINTENANCE (scheduled; see the schedule snippet)
    // ==================================================================

    /**
     * WHAT IT DOES: A safety net run by the scheduler. It finds attempts whose time ran out without
     *    a submit (browser closed, phone died) and submits them, grading only what was autosaved in
     *    time. Run it before flushExpired so nothing is deleted ungraded.
     *
     * USED WHEN: Every few minutes, by the scheduler.
     *
     * GIVES BACK: How many attempts it submitted.
     */
    public function autoSubmitOverdue(): int
    {
        $done = 0;

        CbtAttempt::query()
            ->whereNull('submitted_at')
            ->whereNotNull('ends_at')
            ->whereNotNull('cbt_id')
            ->where('ends_at', '<', now()->subSeconds(self::GRACE_SECONDS + 30))
            ->chunkById(100, function ($attempts) use (&$done) {
                foreach ($attempts as $attempt) {
                    try {
                        $this->submit($attempt, []);        // past the deadline, so saved answers only
                        $done++;
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });

        return $done;
    }

    /**
     * WHAT IT DOES: Scheduled clean-up. It deletes CBTs that are past their expiry together with
     *    their temporary questions and answers. Scores survive. It skips a CBT that still has an
     *    ungraded attempt.
     *
     * USED WHEN: Every hour, by the scheduler.
     *
     * GIVES BACK: How many CBTs it deleted.
     */
    public function flushExpired(): int
    {
        $removed = 0;

        Cbt::query()
            ->where('expires_at', '<', now())
            ->whereDoesntHave('attempts', fn ($q) => $q->whereNull('submitted_at'))
            ->chunkById(100, function ($cbts) use (&$removed) {
                foreach ($cbts as $cbt) {
                    $cbt->delete();
                    $removed++;
                }
            });

        return $removed;
    }
}
