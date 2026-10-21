<?php

namespace App\Services\Cbt;

use App\Exceptions\CbtException;
use App\Models\Cbt;
use App\Models\CbtAttempt;
use App\Models\CbtAttemptAnswer;
use App\Models\TempQuestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The timed-attempt lifecycle for exam, test and practice CBTs:
 *   start (or resume) -> autosave answers -> submit -> grade -> result.
 *
 * Live multiplayer quizzes (Reverb) are a different flow and belong in a QuizService.
 *
 * Hard rule: correct answers leave the server only inside resultFor(), after submission.
 * attemptPayload() builds each question from an explicit whitelist for that reason.
 */
class CbtService
{
    /** Seconds past ends_at that a submit or autosave is still accepted (network lag, clock drift). */
    private const GRACE_SECONDS = 60;

    public function __construct(private readonly AnswerGrader $grader)
    {
    }

    // ------------------------------------------------------------------
    // Start / resume
    // ------------------------------------------------------------------

    /**
     * @param  Model  $taker  an Enrollment (student) or a User (teacher/staff)
     */
    public function startAttempt(Cbt $cbt, Model $taker): CbtAttempt
    {
        if ($cbt->type === 'quiz') {
            throw CbtException::wrongMode();
        }
        if (! $cbt->canBeTakenBy($taker)) {          // same school, assigned class, etc.
            throw CbtException::notAllowed();
        }
        if (! $cbt->isOpen()) {                      // inside its window and not expired
            throw CbtException::closed();
        }

        return DB::transaction(function () use ($cbt, $taker) {
            $takerScope = fn ($q) => $q
                ->where('cbt_id', $cbt->id)
                ->where('taker_type', $taker->getMorphClass())
                ->where('taker_id', $taker->getKey());

            // Resume an unfinished attempt: a refresh must never reset the clock.
            $open = $takerScope(CbtAttempt::query())
                ->whereNull('submitted_at')
                ->lockForUpdate()
                ->latest('id')
                ->first();
            if ($open) {
                return $open;
            }

            if ($takerScope(CbtAttempt::query())->whereNotNull('submitted_at')->exists()) {
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

    private function deadlineFor(Cbt $cbt): ?\Illuminate\Support\Carbon
    {
        $ends = $cbt->duration_minutes ? now()->addMinutes($cbt->duration_minutes) : null;

        // A hard closing time (closes_at) always wins over the personal timer.
        if ($cbt->closes_at && (! $ends || $cbt->closes_at->lt($ends))) {
            $ends = $cbt->closes_at->copy();
        }

        return $ends;
    }

    // ------------------------------------------------------------------
    // What the browser receives (never any answer key)
    // ------------------------------------------------------------------

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
                $blocks[] = ['kind' => 'question', 'question' => $this->publicQuestion($q)];
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
                    'content' => $group->content,         // gap passages carry {{n}} tokens
                ],
                'questions' => $questions->where('group_id', $q->group_id)
                    ->sortBy('position')
                    ->map(fn (TempQuestion $c) => $this->publicQuestion($c))
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
            'saved_answers' => (object) $saved->all(),
            'blocks' => $blocks,
        ];
    }

    /** Whitelist, not blacklist. Never add correct_answers here. */
    private function publicQuestion(TempQuestion $q): array
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

    // ------------------------------------------------------------------
    // Autosave
    // ------------------------------------------------------------------

    /** @param array<int|string, string|array<int,string>|null> $answers  questionId => answer */
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

    /** Accepts a string or a list of strings; anything else counts as "no answer". */
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

    private function isPastDeadline(CbtAttempt $attempt): bool
    {
        return $attempt->ends_at !== null
            && now()->gt($attempt->ends_at->copy()->addSeconds(self::GRACE_SECONDS));
    }

    // ------------------------------------------------------------------
    // Submit and grade
    // ------------------------------------------------------------------

    /** @param array<int, array{id:int, answer:mixed}> $answers  the final list from the browser */
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
            $correct = $this->grader->isCorrect($answer, $q->options, $q->correct_answers ?? []);
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

        $attempt->forceFill(['submitted_at' => $now, 'score' => $score, 'total' => $total])->save();
    }

    // ------------------------------------------------------------------
    // Result (the only place correct answers are sent)
    // ------------------------------------------------------------------

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
                    'chosen' => $row->answer,                 // string | string[] | null (decoded by the cast)
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
}
