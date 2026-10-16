<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One class for both grade recording and result retrieval — results are
 * not a stored table, they're computed on the fly from `grades` (per your
 * stored procedure), so there's no separate Result model/service to split
 * this across.
 *
 * `grades` has a composite primary key (school_id, subject_id, class_id,
 * student_id, term, session) — writes go through DB::table(), not an
 * Eloquent model's instance ->update(), same reasoning as my_class_users:
 * Eloquent needs a single PK to build its WHERE clause on save/update,
 * which a composite key doesn't give it.
 */
class GradeService extends Service
{
    /**
     * Upsert one student's score for one subject/class/term/session.
     * Blocked once compiled — recording after compile means either
     * reopen() first (deliberate) or it was meant for next term instead.
     */
    public function recordScore(array $data): void
    {
        $key = [
            'school_id'  => $data['school_id'] ?? \App\Support\Helper::schoolId(),
            'subject_id' => $data['subject_id'],
            'class_id'   => $data['class_id'],
            'student_id' => $data['student_id'],
            'term'       => $data['term'],
            'session'    => $data['session'],
        ];

        $existing = DB::table('grades')->where($key)->first();

        if ($existing && $existing->compiled) {
            throw new \LogicException('This grade has already been compiled and can no longer be edited — reopen it first.');
        }

        DB::table('grades')->updateOrInsert($key, [
            ...$key,
            'sub_type'    => $data['sub_type'] ?? $existing->sub_type ?? 'b',
            'first_test'  => $data['first_test'] ?? $existing->first_test ?? null,
            'second_test' => $data['second_test'] ?? $existing->second_test ?? null,
            'exam'        => $data['exam'] ?? $existing->exam ?? null,
            'assignment'  => $data['assignment'] ?? $existing->assignment ?? null,
            'compiled'    => false,
            'created_at'  => $existing->created_at ?? now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Locks every grade matching this class/subject/term/session so
     * recordScore() refuses further edits. Returns rows affected.
     */
    public function compile(int $classId, int $subjectId, string $term, string $session): int
    {
        return DB::table('grades')
            ->where(['class_id' => $classId, 'subject_id' => $subjectId, 'term' => $term, 'session' => $session])
            ->update(['compiled' => true, 'updated_at' => now()]);
    }

    /**
     * Admin override — unlocks a compiled batch for correction.
     * Deliberately separate from compile() rather than a toggle, so it
     * reads as the exceptional action it is.
     */
    public function reopen(int $classId, int $subjectId, string $term, string $session): int
    {
        return DB::table('grades')
            ->where(['class_id' => $classId, 'subject_id' => $subjectId, 'term' => $term, 'session' => $session])
            ->update(['compiled' => false, 'updated_at' => now()]);
    }

    /**
     * One student's row for one subject/term/session. Read-only — do not
     * call ->update() on the returned object, it's a plain stdClass from
     * DB::table(), not an Eloquent model (no single PK to update against).
     */
    public function forStudentSubject(int $studentId, int $subjectId, string $term, string $session): ?object
    {
        return DB::table('grades')
            ->where(compact('studentId', 'subjectId', 'term', 'session'))
            ->first();
    }

    /**
     * Null-safe sum across whichever score components are filled in.
     */
    public function total(object $grade): int
    {
        return (int) $grade->first_test + (int) $grade->second_test + (int) $grade->exam + (int) $grade->assignment;
    }

    // ------------------------------------------------------------------
    // Result retrieval — still stubs, per your stored procedure comparison.

    /**
     * Result data for every class in the current school, for a given
     * term/session.
     */
    public function resultsForAllClasses(string $term, string $session): Collection
    {
        // TODO
    }

    /**
     * Result data for one specific class.
     */
    public function resultsForClass(int $classId, string $term, string $session): Collection
    {
        // TODO
    }

    /**
     * Result data for a specific set of classes (e.g. a selected group,
     * not literally all classes and not just one).
     */
    public function resultsForClasses(array $classIds, string $term, string $session): Collection
    {
        // TODO
    }

    /**
     * Result data for one specific student.
     */
    public function resultsForStudent(Student $student, string $term, string $session): Collection
    {
        // TODO
    }
}