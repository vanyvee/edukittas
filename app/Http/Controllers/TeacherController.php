<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TeacherService;
use App\Support\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Every teacher endpoint. Each method does three things: work out the school, hand the request to
 * TeacherService, return JSON. The rules all live in the service. Put your admin middleware or
 * policy on the route group.
 */
class TeacherController extends Controller
{
    /**
     * WHAT IT DOES: Receives the teacher service. All the real work lives there; this controller
     *    only passes requests along.
     *
     * USED WHEN: Laravel creates the controller automatically for every request.
     *
     * GIVES BACK: Nothing.
     */
    public function __construct(private readonly TeacherService $teachers)
    {
    }

    // ------------------------------------------------------------------
    // Teachers
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Lists every teacher in the school with a count of their assignments.
     *
     * USED WHEN: GET /teachers, when the admin opens the teachers list.
     *
     * GIVES BACK: JSON list.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->teachers->listForSchool(Helper::schoolId()));
    }

    /**
     * WHAT IT DOES: Checks the details and creates a teacher. If the person already has an account
     *    (same NIN or phone) nothing is created; the answer asks for confirmation instead.
     *
     * USED WHEN: POST /teachers, when the admin saves a new teacher.
     *
     * GIVES BACK: JSON with a `status` of "created" (201), "already_exists" or
     *    "confirmation_required" (200, with a preview of the existing person).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'nin' => ['nullable', 'string', 'max:20'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        $result = $this->teachers->createTeacher($data, Helper::schoolId());

        return response()->json($result, $result['status'] === 'created' ? 201 : 200);
    }

    /**
     * WHAT IT DOES: The admin confirms that an existing account should join this school as a
     *    teacher. The service refuses if that account is not a teacher.
     *
     * USED WHEN: POST /teachers/confirm, after store answered "confirmation_required".
     *
     * GIVES BACK: JSON with the user's id and uuid.
     */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uuid' => ['required', 'string'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        $user = $this->teachers->addExistingTeacher($data['uuid'], Helper::schoolId(), $data['department_id']);

        return response()->json(['id' => $user->id, 'uuid' => $user->uuid]);
    }

    /**
     * WHAT IT DOES: Returns one teacher together with their current classes and subjects.
     *
     * USED WHEN: GET /teachers/{teacher}, when the admin selects a teacher in the assign panel.
     *
     * GIVES BACK: JSON {teacher, assignments}.
     */
    public function show(int $teacher): JsonResponse
    {
        $t = $this->teacher($teacher);

        return response()->json([
            'teacher' => $t->only(['id', 'uuid', 'pix', 'surname', 'first_name', 'other_name', 'phone']),
            'assignments' => $this->teachers->assignmentsFor($t, Helper::schoolId()),
        ]);
    }

    /**
     * WHAT IT DOES: Changes a teacher's basic details. A phone number already used by someone
     *    else is refused.
     *
     * USED WHEN: PUT /teachers/{teacher}, when the admin edits a teacher.
     *
     * GIVES BACK: JSON with the updated details.
     */
    public function update(Request $request, int $teacher): JsonResponse
    {
        $t = $this->teacher($teacher);

        $data = $request->validate([
            'surname' => ['sometimes', 'string', 'max:100'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($t->id)],
        ]);

        $t = $this->teachers->updateTeacher($t, $data);

        return response()->json($t->only(['id', 'uuid', 'surname', 'first_name', 'other_name', 'phone']));
    }

    /**
     * WHAT IT DOES: Takes the teacher out of this school: their assignments here and their
     *    department link here. The account itself stays, since it may belong to other schools.
     *
     * USED WHEN: DELETE /teachers/{teacher}.
     *
     * GIVES BACK: JSON {ok: true}.
     */
    public function destroy(int $teacher): JsonResponse
    {
        $this->teachers->removeFromSchool($this->teacher($teacher), Helper::schoolId());

        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // Assignments
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Returns only the teacher's current classes and subjects, grouped by class.
     *
     * USED WHEN: GET /teachers/{teacher}/assignments, when the panel needs to refresh the list
     *    without reloading the teacher.
     *
     * GIVES BACK: JSON list.
     */
    public function assignments(int $teacher): JsonResponse
    {
        return response()->json($this->teachers->assignmentsFor($this->teacher($teacher), Helper::schoolId()));
    }

    /**
     * WHAT IT DOES: A dry run. Marks each proposed class and subject as new, already assigned, or
     *    shared with another teacher. Nothing is saved.
     *
     * USED WHEN: POST /teachers/{teacher}/assignments/check, while the admin is choosing, so
     *    conflicts show straight away.
     *
     * GIVES BACK: JSON list of {class_id, subject_id, status, also_taught_by}.
     */
    public function check(Request $request, int $teacher): JsonResponse
    {
        $items = $this->assignmentItems($request);

        return response()->json($this->teachers->checkAssignments($this->teacher($teacher), Helper::schoolId(), $items));
    }

    /**
     * WHAT IT DOES: Saves the teacher's classes and subjects as one bulk upsert: missing rows are
     *    inserted, existing ones are kept.
     *
     * USED WHEN: POST /teachers/{teacher}/assignments, when the admin presses Save.
     *
     * GIVES BACK: JSON {added, updated, assignments}, with the fresh list so the screen matches
     *    the database.
     */
    public function assign(Request $request, int $teacher): JsonResponse
    {
        $items = $this->assignmentItems($request);
        $t = $this->teacher($teacher);

        $result = $this->teachers->assign($t, Helper::schoolId(), $items);

        return response()->json($result + [
            'assignments' => $this->teachers->assignmentsFor($t, Helper::schoolId()),
        ]);
    }

    /**
     * WHAT IT DOES: Removes one or many class and subject rows from the teacher.
     *
     * USED WHEN: POST /teachers/{teacher}/assignments/deassign, the moment the admin removes a row
     *    on screen.
     *
     * GIVES BACK: JSON {removed: number}.
     */
    public function deassign(Request $request, int $teacher): JsonResponse
    {
        $data = $request->validate([
            'removals' => ['required', 'array', 'min:1', 'max:200'],
            'removals.*.class_id' => ['required', 'integer'],
            'removals.*.subject_id' => ['required', 'integer'],
        ]);

        $removed = $this->teachers->deassign($this->teacher($teacher), Helper::schoolId(), $data['removals']);

        return response()->json(['removed' => $removed]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Finds the teacher, but only if they belong to this school. Otherwise it stops
     *    with a 404.
     *
     * USED WHEN: At the start of every action that works on one teacher.
     *
     * GIVES BACK: The teacher.
     */
    private function teacher(int $id): User
    {
        return $this->teachers->findTeacherInSchool($id, Helper::schoolId()) ?? abort(404, 'Teacher not found.');
    }

    /**
     * WHAT IT DOES: Checks the list of classes and subjects sent by the panel: every class and
     *    subject must exist, and sub_type must be s, a or b.
     *
     * USED WHEN: Inside check and assign.
     *
     * GIVES BACK: The checked list: [{class_id, subject_ids[], sub_type?}].
     */
    private function assignmentItems(Request $request): array
    {
        return $request->validate([
            'assignments' => ['required', 'array', 'min:1', 'max:100'],
            'assignments.*.class_id' => ['required', 'integer', 'exists:my_classes,id'],
            'assignments.*.subject_ids' => ['required', 'array', 'min:1', 'max:50'],
            'assignments.*.subject_ids.*' => ['integer', 'exists:subjects,id'],
            'assignments.*.sub_type' => ['nullable', Rule::in(['s', 'a', 'b'])],
        ])['assignments'];
    }
}
