<?php

namespace App\Http\Controllers;

use App\Exceptions\CbtException;
use App\Models\Cbt;
use App\Models\CbtAttempt;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Student;
use App\Models\User;
use App\Services\Cbt\CbtService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Every CBT endpoint. Each method does three things: work out who is asking, hand the request to
 * CbtService, return JSON. The rules all live in the service.
 */
class CbtController extends Controller
{
    private const META = ['title', 'type', 'duration_minutes', 'opens_at', 'closes_at', 'expires_at', 'audience'];

    /**
     * WHAT IT DOES: Receives the CBT service. All the real work lives there; this controller only
     *    passes requests along.
     *
     * USED WHEN: Laravel creates the controller automatically for every request.
     *
     * GIVES BACK: Nothing.
     */
    public function __construct(private readonly CbtService $cbts)
    {
    }

    // ------------------------------------------------------------------
    // Taking (students, and teachers or staff taking a test)
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Lists the CBTs the person can take, with their own status on each one.
     *
     * USED WHEN: GET /cbts/available, when a student opens their list of tests.
     *
     * GIVES BACK: JSON list.
     */
    public function available(Request $request): JsonResponse
    {
        return response()->json($this->cbts->availableTo($this->actor($request)));
    }

    /**
     * WHAT IT DOES: Starts the test, or continues an unfinished one, and returns everything the
     *    exam screen needs (never the correct answers).
     *
     * USED WHEN: POST /cbts/{cbt}/attempts, when a student presses Start.
     *
     * GIVES BACK: JSON with the attempt, the timer information, saved answers and questions.
     */
    public function start(Request $request, Cbt $cbt): JsonResponse
    {
        $attempt = $this->cbts->startAttempt($cbt, $this->actor($request));

        return response()->json($this->cbts->attemptPayload($attempt));
    }

    /**
     * WHAT IT DOES: Autosaves the answers ticked so far. First it checks the attempt really belongs
     *    to this person.
     *
     * USED WHEN: PUT /attempts/{attempt}/answers, every time the student pauses.
     *
     * GIVES BACK: JSON {ok: true}.
     */
    public function saveAnswers(Request $request, CbtAttempt $attempt): JsonResponse
    {
        $this->ownAttempt($request, $attempt);

        $data = $request->validate(['answers' => ['present', 'array', 'max:500']]);
        $this->cbts->saveAnswers($attempt, $data['answers']);

        return response()->json(['ok' => true]);
    }

    /**
     * WHAT IT DOES: Takes the student's final answers, checks the attempt is theirs, and has the
     *    service grade it.
     *
     * USED WHEN: POST /attempts/{attempt}/submit, when the student presses Submit or time runs out.
     *
     * GIVES BACK: JSON result (score, total, failed questions).
     */
    public function submit(Request $request, CbtAttempt $attempt): JsonResponse
    {
        $this->ownAttempt($request, $attempt);

        $data = $request->validate([
            'answers' => ['present', 'array', 'max:500'],
            'answers.*.id' => ['required', 'integer'],
            'answers.*.answer' => ['nullable'],
        ]);

        return response()->json($this->cbts->submit($attempt, $data['answers']));
    }

    /**
     * WHAT IT DOES: Shows the result of an attempt that was already submitted. Only the owner may
     *    see it.
     *
     * USED WHEN: GET /attempts/{attempt}/result.
     *
     * GIVES BACK: JSON result.
     */
    public function result(Request $request, CbtAttempt $attempt): JsonResponse
    {
        $this->ownAttempt($request, $attempt);

        return response()->json($this->cbts->resultFor($attempt));
    }

    // ------------------------------------------------------------------
    // Authoring (the person who sets a CBT up)
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Lists the CBTs this person has set up.
     *
     * USED WHEN: GET /cbts, when a teacher opens their list.
     *
     * GIVES BACK: JSON list.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->cbts->hostedBy($this->actor($request)));
    }

    /**
     * WHAT IT DOES: Creates a new CBT, optionally with its questions. The settings are checked
     *    first, and the audience (classes or students) is saved with it.
     *
     * USED WHEN: POST /cbts, when a teacher saves a new CBT.
     *
     * GIVES BACK: JSON with the new CBT as the editing panel needs it (status 201).
     */
    public function store(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate($this->cbts->settingsRules(true, $request->all()));

        $cbt = $this->cbts->create($actor, Arr::only($data, self::META), $request->has('blocks') ? $request->input('blocks') : null);

        return response()->json($this->cbts->authorPayload($cbt), 201);
    }

    /**
     * WHAT IT DOES: Returns a CBT for editing, correct answers included. Only the person who set it
     *    up may see this.
     *
     * USED WHEN: GET /cbts/{cbt}, when a teacher opens a CBT to edit.
     *
     * GIVES BACK: JSON for the editing panel.
     */
    public function show(Request $request, Cbt $cbt): JsonResponse
    {
        $this->ownCbt($request, $cbt);

        return response()->json($this->cbts->authorPayload($cbt));
    }

    /**
     * WHAT IT DOES: Changes a CBT's settings and/or replaces its questions.
     *
     * USED WHEN: PUT /cbts/{cbt}, when the teacher saves changes.
     *
     * GIVES BACK: JSON for the editing panel.
     */
    public function update(Request $request, Cbt $cbt): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate($this->cbts->settingsRules(false, $request->all()));

        $cbt = $this->cbts->update($cbt, $actor, Arr::only($data, self::META), $request->has('blocks') ? $request->input('blocks') : null);

        return response()->json($this->cbts->authorPayload($cbt));
    }

    /**
     * WHAT IT DOES: Deletes a CBT (the service refuses while someone is taking it). Finished
     *    attempts keep their scores.
     *
     * USED WHEN: DELETE /cbts/{cbt}.
     *
     * GIVES BACK: JSON {ok: true}.
     */
    public function destroy(Request $request, Cbt $cbt): JsonResponse
    {
        $this->cbts->destroy($cbt, $this->actor($request));

        return response()->json(['ok' => true]);
    }

    /**
     * WHAT IT DOES: Copies this CBT's questions into the teacher's school bank for later reuse.
     *
     * USED WHEN: POST /cbts/{cbt}/save-to-bank.
     *
     * GIVES BACK: JSON with how many were saved and skipped.
     */
    public function saveToBank(Request $request, Cbt $cbt): JsonResponse
    {
        return response()->json($this->cbts->saveToMyBank($cbt, $this->actor($request)));
    }

    // ------------------------------------------------------------------
    // Question bank (teachers and staff)
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: A teacher asks the bank for questions. Nothing is saved; the panel shows them
     *    for review.
     *
     * USED WHEN: POST /cbt-bank/request.
     *
     * GIVES BACK: JSON with the questions and a message if fewer were found.
     */
    public function requestQuestions(Request $request): JsonResponse
    {
        return response()->json($this->cbts->requestQuestions($this->actor($request), $request->all()));
    }

    /**
     * WHAT IT DOES: Edits one bank question (the service decides who is allowed).
     *
     * USED WHEN: PUT /cbt-bank/questions/{question}.
     *
     * GIVES BACK: JSON with the question id and its new fingerprint.
     */
    public function updateQuestion(Request $request, Question $question): JsonResponse
    {
        $q = $this->cbts->updateBankQuestion($this->user($request), $question, $request->all());

        return response()->json(['id' => $q->id, 'content_hash' => $q->content_hash]);
    }

    /**
     * WHAT IT DOES: Removes one standalone question from the school's bank list.
     *
     * USED WHEN: DELETE /cbt-bank/questions/{question}.
     *
     * GIVES BACK: JSON {result: "hidden" or "deleted"}.
     */
    public function removeQuestion(Request $request, Question $question): JsonResponse
    {
        return response()->json(['result' => $this->cbts->removeQuestion($this->user($request), $question)]);
    }

    /**
     * WHAT IT DOES: Removes a whole passage and its questions from the school's bank list.
     *
     * USED WHEN: DELETE /cbt-bank/groups/{group}.
     *
     * GIVES BACK: JSON {result: "hidden" or "deleted"}.
     */
    public function removeGroup(Request $request, QuestionGroup $group): JsonResponse
    {
        return response()->json(['result' => $this->cbts->removeGroup($this->user($request), $group)]);
    }

    // ------------------------------------------------------------------
    // Admin (the service refuses anyone who is not an admin)
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: For the admin: lists unvetted questions of a subject and topic to review.
     *
     * USED WHEN: GET /cbt-admin/vetting.
     *
     * GIVES BACK: JSON with the questions.
     */
    public function vettingList(Request $request): JsonResponse
    {
        return response()->json($this->cbts->vettingList($this->user($request), $request->query()));
    }

    /**
     * WHAT IT DOES: The admin marks the chosen questions or passages as vetted.
     *
     * USED WHEN: POST /cbt-admin/vet.
     *
     * GIVES BACK: JSON with how many were vetted and skipped.
     */
    public function vet(Request $request): JsonResponse
    {
        [$questions, $groups] = $this->idLists($request);

        return response()->json($this->cbts->vet($this->user($request), $questions, $groups));
    }

    /**
     * WHAT IT DOES: The admin takes the chosen questions or passages out of the shared set.
     *
     * USED WHEN: POST /cbt-admin/unvet.
     *
     * GIVES BACK: JSON {changed: number}.
     */
    public function unvet(Request $request): JsonResponse
    {
        [$questions, $groups] = $this->idLists($request);

        return response()->json(['changed' => $this->cbts->unvet($this->user($request), $questions, $groups)]);
    }

    /**
     * WHAT IT DOES: The admin copies chosen temporary questions into the bank as vetted.
     *
     * USED WHEN: POST /cbt-admin/promote.
     *
     * GIVES BACK: JSON with the counts.
     */
    public function promote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'temp_question_ids' => ['required', 'array', 'min:1', 'max:500'],
            'temp_question_ids.*' => ['integer'],
        ]);

        return response()->json($this->cbts->promoteFromTemp($this->user($request), $data['temp_question_ids']));
    }

    // ------------------------------------------------------------------
    // Who is asking
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Works out who is asking. A student acts through their current enrollment;
     *    teachers and staff act as themselves. If a student has no active enrollment it stops with
     *    a 403.
     *
     * USED WHEN: At the start of most actions.
     *
     * GIVES BACK: The enrollment or user.
     */
    private function actor(Request $request): Model
    {
        $user = $request->user();
        $actor = $user instanceof Student ? $user->currentEnrollment() : $user;

        return $actor ?? abort(403, 'No active enrollment.');
    }

    /**
     * WHAT IT DOES: For actions only teachers, staff and admins may do: returns the logged-in user,
     *    or stops with a "not allowed" error if a student is asking.
     *
     * USED WHEN: At the start of bank and admin actions.
     *
     * GIVES BACK: The user.
     */
    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw CbtException::notAllowed();
    }

    /**
     * WHAT IT DOES: Makes sure an attempt belongs to the person asking, otherwise stops with a 403.
     *
     * USED WHEN: Before autosave, submit and result.
     *
     * GIVES BACK: Nothing (it stops the request when the check fails).
     */
    private function ownAttempt(Request $request, CbtAttempt $attempt): void
    {
        abort_unless($attempt->isOwnedBy($this->actor($request)), 403);
    }

    /**
     * WHAT IT DOES: Makes sure the person asking is the one who set the CBT up, because only they
     *    may read its answer key.
     *
     * USED WHEN: Before showing a CBT for editing.
     *
     * GIVES BACK: Nothing (it stops the request when the check fails).
     */
    private function ownCbt(Request $request, Cbt $cbt): void
    {
        $actor = $this->actor($request);

        abort_unless(
            $cbt->host_type === $actor->getMorphClass() && (int) $cbt->host_id === (int) $actor->getKey(),
            403,
        );
    }

    /**
     * WHAT IT DOES: Reads and checks the lists of question ids and group ids sent by the admin.
     *
     * USED WHEN: Inside vet and unvet.
     *
     * GIVES BACK: Two lists: question ids and group ids.
     */
    private function idLists(Request $request): array
    {
        $data = $request->validate([
            'question_ids' => ['sometimes', 'array', 'max:500'],
            'question_ids.*' => ['integer'],
            'group_ids' => ['sometimes', 'array', 'max:500'],
            'group_ids.*' => ['integer'],
        ]);

        return [$data['question_ids'] ?? [], $data['group_ids'] ?? []];
    }
}
