<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Every grade endpoint. Each method does three things: work out who is asking, hand the request to
 * GradeService, return JSON. The rules all live in the service. Put compile and reopen behind your
 * admin middleware.
 */
class GradeController extends Controller
{
    /**
     * WHAT IT DOES: Receives the grade service. All the real work lives there; this controller
     *    only passes requests along.
     *
     * USED WHEN: Laravel creates the controller automatically for every request.
     *
     * GIVES BACK: Nothing.
     */
    public function __construct(private readonly GradeService $grades)
    {
    }

    /**
     * WHAT IT DOES: Saves the scores typed on the score sheet for one class, subject, term and
     *    session. Each score is checked against its maximum. Compiled rows are left alone and
     *    reported back.
     *
     * USED WHEN: POST /grades/scores, when the teacher presses Save.
     *
     * GIVES BACK: JSON {saved, locked}.
     */
    public function record(Request $request): JsonResponse
    {
        $data = $request->validate($this->grades->scoreRules());

        return response()->json(
            $this->grades->recordScores($this->user($request), $data, $data['scores'])
        );
    }

    /**
     * WHAT IT DOES: Returns one student's scores and totals for a term and session.
     *
     * USED WHEN: GET /grades/students/{student}?term=1&session=2026.
     *
     * GIVES BACK: JSON {subjects, total, max_total}.
     */
    public function forStudent(Request $request, int $student): JsonResponse
    {
        $data = $request->validate([
            'term' => ['required', Rule::in(['1', '2', '3'])],
            'session' => ['required', 'integer', 'digits:4'],
        ]);

        return response()->json($this->grades->forStudent($student, (string) $data['term'], (int) $data['session']));
    }

    /**
 * WHAT IT DOES: Locks all grades of a class, subject, term and session. If too many students have
 *    no exam score it answers 409 with the count, and the screen can ask the admin to confirm
 *    and send the same request again with force = true.
 *
 * USED WHEN: POST /grades/compile.
 *
 * GIVES BACK: JSON {compiled, blocked, missing_exam, tolerance}.
 */
public function compile(Request $request): JsonResponse
{
    $d = $this->batchData($request);

    $result = $this->grades->compile(
        $d['class_id'], $d['subject_id'], (string) $d['term'], (int) $d['session'], $request->boolean('force')
    );

    return response()->json($result, $result['blocked'] ? 409 : 200);
}

    /**
     * WHAT IT DOES: Admin override: unlocks a compiled batch so a mistake can be corrected.
     *
     * USED WHEN: POST /grades/reopen.
     *
     * GIVES BACK: JSON {reopened: number of rows unlocked}.
     */
    public function reopen(Request $request): JsonResponse
    {
        $d = $this->batchData($request);

        return response()->json(['reopened' => $this->grades->reopen($d['class_id'], $d['subject_id'], (string) $d['term'], (int) $d['session'])]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Returns the logged-in staff member, or stops with a 403 if a student is
     *    asking, because students never record scores.
     *
     * USED WHEN: Before recording scores.
     *
     * GIVES BACK: The user.
     */
    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : abort(403);
    }

    /**
     * WHAT IT DOES: Checks the four fields that identify one batch of grades.
     *
     * USED WHEN: Inside compile and reopen.
     *
     * GIVES BACK: The checked class_id, subject_id, term and session.
     */
    private function batchData(Request $request): array
    {
        return $request->validate([
            'class_id' => ['required', 'integer', 'exists:my_classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'term' => ['required', Rule::in(['1', '2', '3'])],
            'session' => ['required', 'integer', 'digits:4'],
        ]);
    }


    /**
 * WHAT IT DOES: Lists the CBT titles a teacher can push for a class and subject, and marks the
 *    ones that would be averaged.
 *
 * USED WHEN: GET /grades/pushable?class_id=&subject_id=, when the teacher opens the push screen.
 *
 * GIVES BACK: JSON list of {title, key, students, max_per_student, averaged, pushed}.
 */
public function pushable(Request $request): JsonResponse
{
    $data = $request->validate([
        'class_id' => ['required', 'integer', 'exists:my_classes,id'],
        'subject_id' => ['required', 'integer', 'exists:subjects,id'],
    ]);

    return response()->json($this->grades->pushableTitles((int) $data['class_id'], (int) $data['subject_id']));
}

/**
 * WHAT IT DOES: Shows what a push would write for each student, with the averaging notice and
 *    the current value in the column. Nothing is saved.
 *
 * USED WHEN: POST /grades/push/preview, before the teacher confirms.
 *
 * GIVES BACK: JSON {column, out_of, averaged, notice, rows}.
 */
public function previewPush(Request $request): JsonResponse
{
    $this->user($request);

    return response()->json($this->grades->previewPush($request->validate($this->grades->pushRules())));
}

/**
 * WHAT IT DOES: Pushes the worked-out marks into the chosen column. Compiled rows are skipped and
 *    reported. Pushing again gives the same result.
 *
 * USED WHEN: POST /grades/push, when the teacher confirms the preview.
 *
 * GIVES BACK: JSON {saved, locked}.
 */
public function push(Request $request): JsonResponse
{
    $data = $request->validate($this->grades->pushRules());

    return response()->json($this->grades->pushScores($this->user($request), $data));
}



/** WHAT IT DOES: Returns the score sheet (roster plus current scores and limits)
 *  for a class, subject, term and session. 
 * USED WHEN: GET /grades/sheet. 
 * GIVES BACK: JSON sheet. */

public function sheet(Request $request): JsonResponse
{
    $d = $this->batchData($request);

    return response()->json($this->grades->sheet(
        $this->user($request), $d['class_id'], $d['subject_id'], (string) $d['term'], (int) $d['session']
    ));
}
}