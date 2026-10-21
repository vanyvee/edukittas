<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Everything about teachers. A teacher is a User whose `role` is 'teacher'. Registering a staff
 * account is inherited from UserService; this class adds school scoping, the teacher list, and
 * the class and subject assignments.
 *
 * `class_subject_teachers` has a composite primary key (subject, class, user, school) and no
 * single id, so every write goes through DB::table(), never an Eloquent ->save() or ->delete().
 */
class TeacherService extends UserService
{
    private const TABLE = 'class_subject_teachers';

    // ------------------------------------------------------------------
    // Finding and listing
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Finds a teacher by id, but only if the person really is a teacher AND belongs
     *    to the given school. Anything else counts as "not found".
     *
     * USED WHEN: At the start of every single-teacher action (show, update, remove, assignments),
     *    so one school can never touch another school's teacher.
     *
     * GIVES BACK: The teacher (User), or null.
     */
    public function findTeacherInSchool(int $id, int $schoolId): ?User
    {
        $user = $this->findById($id);

        if (! $user || $user->role !== 'teacher' || ! $this->belongsToSchool($user, $schoolId)) {
            return null;
        }

        return $user;
    }

    /**
     * WHAT IT DOES: Checks whether a user is linked to the given school through the departments
     *    link table.
     *
     * USED WHEN: Inside findTeacherInSchool.
     *
     * GIVES BACK: true or false.
     */
    public function belongsToSchool(User $user, int $schoolId): bool
    {
        return $user->departments()->wherePivot('school_id', $schoolId)->exists();
    }

    /**
     * WHAT IT DOES: Lists every teacher in the school, each with a count of how many class and
     *    subject rows they currently have.
     *
     * USED WHEN: GET /teachers, when the admin opens the teachers list or the assign screen.
     *
     * GIVES BACK: A collection of teachers (id, uuid, pix, names, phone, assignments_count).
     */
    public function listForSchool(int $schoolId)
    {
        $pivot = (new User)->departments()->getTable();

        return User::query()
            ->where('role', 'teacher')
            ->whereHas('departments', fn ($q) => $q->where("$pivot.school_id", $schoolId))
            ->addSelect(['assignments_count' => DB::table(self::TABLE)
                ->selectRaw('count(*)')
                ->whereColumn('user_id', 'users.id')
                ->where('school_id', $schoolId)])
            ->orderBy('surname')
            ->get(['id', 'uuid', 'pix', 'surname', 'first_name', 'other_name', 'phone']);
    }

    // ------------------------------------------------------------------
    // Create, update, remove
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Registers a new teacher in the school. If the person already has an account
     *    (same NIN or phone) nothing is created; the answer asks the admin to confirm instead.
     *
     * USED WHEN: POST /teachers, when the admin adds a teacher.
     *
     * GIVES BACK: An array whose `status` is "created", "already_exists" (already in this school)
     *    or "confirmation_required" (with a preview of the existing person).
     */
    public function createTeacher(array $data, int $schoolId): array
    {
        return $this->register($data + ['role' => 'teacher'], $schoolId);
    }

    /**
     * WHAT IT DOES: After the admin confirms, links an existing account to this school. It refuses
     *    if that account is not a teacher, because role is a single column on the user and
     *    confirming cannot change it.
     *
     * USED WHEN: POST /teachers/confirm, after createTeacher answered "confirmation_required".
     *
     * GIVES BACK: The user.
     */
    public function addExistingTeacher(string $uuid, int $schoolId, int $departmentId): User
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        if ($user->role !== 'teacher') {
            throw ValidationException::withMessages(['uuid' => 'This account is not a teacher account.']);
        }

        return $this->assignExistingUserDepartment($uuid, $schoolId, $departmentId);
    }

    /**
     * WHAT IT DOES: Saves changes to a teacher's basic details (names, phone).
     *
     * USED WHEN: PUT /teachers/{teacher}.
     *
     * GIVES BACK: The updated teacher.
     */
    public function updateTeacher(User $teacher, array $data): User
    {
        $teacher->update($data);

        return $teacher;
    }

    /**
     * WHAT IT DOES: Takes the teacher out of THIS school only: deletes their class and subject
     *    rows here and removes their department link here. The account itself stays, because it
     *    may belong to other schools. Both steps happen together or not at all.
     *
     * USED WHEN: DELETE /teachers/{teacher}.
     *
     * GIVES BACK: Nothing.
     */
    public function removeFromSchool(User $teacher, int $schoolId): void
    {
        $this->transaction(function () use ($teacher, $schoolId) {
            DB::table(self::TABLE)->where(['user_id' => $teacher->id, 'school_id' => $schoolId])->delete();
            $teacher->departments()->wherePivot('school_id', $schoolId)->detach();
        });
    }

    // ------------------------------------------------------------------
    // Assignments
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Reads the teacher's current class and subject rows in this school and groups
     *    the subjects under their class.
     *
     * USED WHEN: When a teacher is selected in the panel, and after every save, so the screen
     *    always shows what is really in the database.
     *
     * GIVES BACK: A list like [{class_id, class_name, subjects: [{id, name, sub_type}]}].
     */
    public function assignmentsFor(User $teacher, int $schoolId): array
    {
        $rows = DB::table(self::TABLE . ' as cst')
            ->join('my_classes as c', 'c.id', '=', 'cst.class_id')
            ->join('subjects as s', 's.id', '=', 'cst.subject_id')
            ->where('cst.user_id', $teacher->id)
            ->where('cst.school_id', $schoolId)
            ->orderBy('c.name')->orderBy('s.name')
            ->get(['cst.class_id', 'c.name as class_name', 'cst.subject_id', 's.name as subject_name', 'cst.sub_type']);

        return $rows->groupBy('class_id')->map(fn ($group) => [
            'class_id' => $group->first()->class_id,
            'class_name' => $group->first()->class_name,
            'subjects' => $group->map(fn ($r) => [
                'id' => $r->subject_id,
                'name' => $r->subject_name,
                'sub_type' => $r->sub_type,
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * WHAT IT DOES: A dry run. For every proposed class and subject it says whether the row is
     *    new, already this teacher's, or also taught by someone else. "Shared" is allowed, so it
     *    is only a notice. Nothing is saved.
     *
     * USED WHEN: POST /teachers/{teacher}/assignments/check, while the admin is choosing, so
     *    the panel can flag a conflict straight away.
     *
     * GIVES BACK: A list of {class_id, subject_id, status, also_taught_by[]}, where status is
     *    "new", "already_assigned" or "shared".
     */
    public function checkAssignments(User $teacher, int $schoolId, array $items): array
    {
        $pairs = $this->flatten($items);
        if (! $pairs) {
            return [];
        }

        $classIds = array_unique(array_column($pairs, 'class_id'));
        $subjectIds = array_unique(array_column($pairs, 'subject_id'));

        $mine = $this->existingKeys($teacher->id, $schoolId, $classIds, $subjectIds);

        $others = DB::table(self::TABLE . ' as cst')
            ->join('users as u', 'u.id', '=', 'cst.user_id')
            ->where('cst.school_id', $schoolId)
            ->where('cst.user_id', '!=', $teacher->id)
            ->whereIn('cst.class_id', $classIds)
            ->whereIn('cst.subject_id', $subjectIds)
            ->get(['cst.class_id', 'cst.subject_id', 'u.surname', 'u.first_name'])
            ->groupBy(fn ($r) => $r->class_id . '-' . $r->subject_id);

        return array_values(array_map(function ($p) use ($mine, $others) {
            $key = $p['class_id'] . '-' . $p['subject_id'];
            $names = isset($others[$key])
                ? $others[$key]->map(fn ($r) => trim($r->surname . ' ' . $r->first_name))->all()
                : [];

            return [
                'class_id' => $p['class_id'],
                'subject_id' => $p['subject_id'],
                'status' => isset($mine[$key]) ? 'already_assigned' : ($names ? 'shared' : 'new'),
                'also_taught_by' => $names,
            ];
        }, $pairs));
    }

    /**
     * WHAT IT DOES: Saves the teacher's classes and subjects in one bulk upsert. A missing row is
     *    inserted with a fresh uuid. An existing row is kept and only touched, so its fee and
     *    uuid are never overwritten. `sub_type` changes only when the request names one. All of
     *    it happens together or not at all.
     *
     * USED WHEN: POST /teachers/{teacher}/assignments, when the admin presses Save.
     *
     * GIVES BACK: {added, updated}: how many rows were new and how many already existed.
     */
    public function assign(User $teacher, int $schoolId, array $items): array
    {
        $pairs = $this->flatten($items);
        if (! $pairs) {
            return ['added' => 0, 'updated' => 0];
        }

        return $this->transaction(function () use ($teacher, $schoolId, $pairs) {
            $now = now();
            $existing = $this->existingKeys(
                $teacher->id,
                $schoolId,
                array_unique(array_column($pairs, 'class_id')),
                array_unique(array_column($pairs, 'subject_id')),
            );

            $typed = [];
            $plain = [];
            foreach ($pairs as $p) {
                $row = [
                    'user_id' => $teacher->id,
                    'class_id' => $p['class_id'],
                    'subject_id' => $p['subject_id'],
                    'school_id' => $schoolId,
                    'uuid' => (string) Str::uuid(), // ignored by the database when the row already exists
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($p['sub_type'] !== null) {
                    $typed[] = $row + ['sub_type' => $p['sub_type']];
                } else {
                    $plain[] = $row;
                }
            }

            $keys = ['subject_id', 'class_id', 'user_id', 'school_id'];
            if ($plain) {
                DB::table(self::TABLE)->upsert($plain, $keys, ['updated_at']);
            }
            if ($typed) {
                DB::table(self::TABLE)->upsert($typed, $keys, ['sub_type', 'updated_at']);
            }

            $updated = count(array_intersect_key($pairs, $existing));

            return ['added' => count($pairs) - $updated, 'updated' => $updated];
        });
    }

    /**
     * WHAT IT DOES: Deletes class and subject rows from this teacher, inside this school only.
     *    Takes one removal or many.
     *
     * USED WHEN: POST /teachers/{teacher}/assignments/deassign, as soon as the admin removes a
     *    row on screen.
     *
     * GIVES BACK: How many rows were actually deleted.
     */
    public function deassign(User $teacher, int $schoolId, array $removals): int
    {
        return $this->transaction(function () use ($teacher, $schoolId, $removals) {
            $removed = 0;

            foreach ($removals as $r) {
                $removed += DB::table(self::TABLE)->where([
                    'user_id' => $teacher->id,
                    'school_id' => $schoolId,
                    'class_id' => $r['class_id'],
                    'subject_id' => $r['subject_id'],
                ])->delete();
            }

            return $removed;
        });
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Turns the panel's grouped list (one class with many subjects) into one flat
     *    entry per class and subject, removing duplicates.
     *
     * USED WHEN: Inside checkAssignments and assign.
     *
     * GIVES BACK: An array keyed "classId-subjectId", each with class_id, subject_id, sub_type.
     */
    private function flatten(array $items): array
    {
        $pairs = [];

        foreach ($items as $item) {
            foreach ($item['subject_ids'] as $subjectId) {
                $pairs[$item['class_id'] . '-' . $subjectId] = [
                    'class_id' => (int) $item['class_id'],
                    'subject_id' => (int) $subjectId,
                    'sub_type' => $item['sub_type'] ?? null,
                ];
            }
        }

        return $pairs;
    }

    /**
     * WHAT IT DOES: Finds which of the given classes and subjects this teacher already has in the
     *    school.
     *
     * USED WHEN: Inside checkAssignments and assign, to tell new rows from existing ones.
     *
     * GIVES BACK: An array whose keys are "classId-subjectId" for every row already there.
     */
    private function existingKeys(int $teacherId, int $schoolId, array $classIds, array $subjectIds): array
    {
        return DB::table(self::TABLE)
            ->where('user_id', $teacherId)
            ->where('school_id', $schoolId)
            ->whereIn('class_id', $classIds)
            ->whereIn('subject_id', $subjectIds)
            ->get(['class_id', 'subject_id'])
            ->mapWithKeys(fn ($r) => [$r->class_id . '-' . $r->subject_id => true])
            ->all();
    }
}
