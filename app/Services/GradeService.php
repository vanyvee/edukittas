<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Supports\Helper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\Rule;
 use App\Services\Cbt\CbtService;
 use App\Models\Enrollment;
/**
 * Recording, locking and reading student scores.
 *
 * Results are not a stored table: they are computed from `grades` (by your stored procedure,
 * to be wired into the result methods at the bottom).
 *
 * `grades` has a composite primary key (school, subject, class, student, term, session) and no
 * single id, so every write goes through DB::table(), never an Eloquent ->save() or ->update().
 *
 * Score columns and their maximums live in MAX_SCORES, the one place to change them.
 */
class GradeService extends Service
{
public function __construct(private readonly CbtService $cbts) {}
/** Each score column and the most it may hold. Together they make 100. */
    public const MAX_SCORES = [
        'first_test' => 15,
        'second_test' => 15,
        'other_test' => 10,   // assignments, notes, research...
        'exam' => 60,
    ];
    private const PUSH_COLUMNS = ['first_test', 'second_test', 'other_test'];
    private const COMPONENTS = ['first_test', 'second_test', 'other_test', 'exam'];
    private const KEY = ['school_id', 'subject_id', 'class_id', 'student_id', 'term', 'session'];
    /** Students with no exam score that compile will tolerate without a warning . */
    private const COMPILE_TOLERANCE = 5;
    // ------------------------------------------------------------------
    // Rules
    // ------------------------------------------------------------------
 
    /**
     * WHAT IT DOES: Builds the validation rules for a score-entry request. The maximum of each
     *    score column comes from MAX_SCORES, so the limits are written in one place only.
     *
     * USED WHEN: By the controller, before recording scores.
     *
     * GIVES BACK: An array of Laravel validation rules.
     */
    public function scoreRules(): array
    {
        $rules = [
            'class_id' => ['required', 'integer', 'exists:my_classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'term' => ['required', Rule::in(['1', '2', '3'])],
            'session' => ['required', 'integer', 'digits:4'],
            'scores' => ['required', 'array', 'min:1', 'max:200'],
            'scores.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'scores.*.sub_type' => ['sometimes', Rule::in(['s', 'a', 'b'])],
        ];
 
        foreach (self::MAX_SCORES as $column => $max) {
            $rules["scores.*.$column"] = ['sometimes', 'nullable', 'integer', 'min:0', "max:$max"];
        }
 
        return $rules;
    }



    // ------------------------------------------------------------------
    // Recording
    // ------------------------------------------------------------------
 
    /**
     * WHAT IT DOES: Saves scores for many students of one class, subject, term and session in one
     *    bulk upsert. A score that is left out is not touched; a score sent as null is cleared.
     *    Rows that are already compiled are skipped and reported back, never changed.
     *    A teacher may only record for a class and subject they are assigned to.
     *
     * USED WHEN: POST /grades/scores, when a teacher presses Save on the score sheet.
     *
     * GIVES BACK: {saved: number, locked: [student ids that were skipped because compiled]}.
     */
    public function recordScores(User $user, array $meta, array $rows): array
    {
        $schoolId = Helper::schoolId();
        $this->assertMayRecord($user, $schoolId, (int) $meta['class_id'], (int) $meta['subject_id']);
 


// Only students actively enrolled in this class of this school may be scored.
$ids = array_map(fn ($r) => (int) $r['student_id'], $rows);
$enrolled = Enrollment::where('class_id', (int) $meta['class_id'])
    ->whereIn('student_id', $ids)
    ->pluck('student_id')->map(fn ($id) => (int) $id)->all();

$notEnrolled = array_values(array_diff($ids, $enrolled));
$rows = array_values(array_filter($rows, fn ($r) => in_array((int) $r['student_id'], $enrolled, true)));

        $scope = [
            'school_id' => $schoolId,
            'subject_id' => (int) $meta['subject_id'],
            'class_id' => (int) $meta['class_id'],
            'term' => (string) $meta['term'],
            'session' => (int) $meta['session'],
        ];
 
        return $this->transaction(function () use ($scope, $rows) {
            $existing = DB::table('grades')
                ->where($scope)
                ->whereIn('student_id', array_column($rows, 'student_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('student_id');
 
            $now = now();
            $write = [];
            $locked = [];
 
            foreach ($rows as $row) {
                $studentId = (int) $row['student_id'];
                $old = $existing->get($studentId);
 
                if ($old && $old->compiled) {
                    $locked[] = $studentId;
                    continue;
                }
 
                $line = $scope + [
                    'student_id' => $studentId,
                    'sub_type' => $row['sub_type'] ?? $old->sub_type ?? 'b',
                    'compiled' => false,
                    'created_at' => $old->created_at ?? $now,
                    'updated_at' => $now,
                ];
 
                // Every row carries every score column, so one upsert can write the whole batch.
                foreach (self::COMPONENTS as $column) {
                    $line[$column] = array_key_exists($column, $row) ? $row[$column] : ($old->$column ?? null);
                }
 
                $write[] = $line;
            }
 
            foreach (array_chunk($write, 500) as $chunk) {
                DB::table('grades')->upsert(
                    $chunk,
                    self::KEY,
                    [...self::COMPONENTS, 'sub_type', 'updated_at'],
                );
            }
 
            return ['saved' => count($write), 'locked' => $locked];
        });
    }
 
    
    /**
     * WHAT IT DOES: Makes sure the person may record scores for this class and subject. A teacher
     *    must have a row in class_subject_teachers for it; other staff roles are controlled by
     *    the route's middleware.
     *
     * USED WHEN: At the start of recordScores.
     *
     * GIVES BACK: Nothing (it throws an authorization error, shown as 403, when the check fails).
     */
    private function assertMayRecord(User $user, int $schoolId, int $classId, int $subjectId): void
    {
        if ($user->role !== 'teacher') {
            return;
        }
 
        $assigned = DB::table('class_subject_teachers')->where([
            'user_id' => $user->id,
            'school_id' => $schoolId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
        ])->exists();
 
        if (! $assigned) {
            throw new AuthorizationException('You are not assigned to this class and subject.');
        }
    }
 
    // ------------------------------------------------------------------
    // Locking
    // ------------------------------------------------------------------
 

/**
 * WHAT IT DOES: Locks every grade of a class, subject, term and session so recordScores refuses
 *    further edits. First it counts the class's active students who still have no exam score. If
 *    more than the tolerance (5) are missing, it locks nothing and says so, unless $force is true.
 *    After locking, it deletes attempts that were already pushed and whose CBT is gone, to free space.
 *
 * USED WHEN: POST /grades/compile, when the scores are final.
 *
 * GIVES BACK: {compiled: rows locked, blocked: true|false, missing_exam: number, tolerance: number}.
 */
public function compile(int $classId, int $subjectId, string $term, int $session, bool $force = false): array
{
    $scope = $this->batchScope($classId, $subjectId, $term, $session);

    // Enrollment already limits this to active students of the current school.
    $roster = Enrollment::where('class_id', $classId)->pluck('student_id');

    $scored = DB::table('grades')
        ->where($scope)
        ->whereIn('student_id', $roster)
        ->whereNotNull('exam')
        ->count();

    $missing = $roster->count() - $scored;

    if ($missing > self::COMPILE_TOLERANCE && ! $force) {
        return ['compiled' => 0, 'blocked' => true, 'missing_exam' => $missing, 'tolerance' => self::COMPILE_TOLERANCE];
    }

    $locked = DB::table('grades')->where($scope)->update(['compiled' => true, 'updated_at' => now()]);

    $this->cbts->purgePushedAttempts(Helper::schoolId(), $classId, $subjectId);

    return ['compiled' => $locked, 'blocked' => false, 'missing_exam' => $missing, 'tolerance' => self::COMPILE_TOLERANCE];
}
    /**
     * WHAT IT DOES: Admin override that unlocks a compiled batch for correction. It is a separate
     *    method from compile on purpose, so it reads as the exceptional action it is.
     *
     * USED WHEN: POST /grades/reopen.
     *
     * GIVES BACK: How many rows were unlocked.
     */
    public function reopen(int $classId, int $subjectId, string $term, int $session): int
    {
        return DB::table('grades')
            ->where($this->batchScope($classId, $subjectId, $term, $session))
            ->update(['compiled' => false, 'updated_at' => now()]);
    }
 
    /**
     * WHAT IT DOES: Builds the "this class, subject, term, session in the current school" filter
     *    shared by compile and reopen.
     *
     * USED WHEN: Inside compile and reopen.
     *
     * GIVES BACK: An array of column conditions.
     */
    private function batchScope(int $classId, int $subjectId, string $term, int $session): array
    {
        return [
            'school_id' => Helper::schoolId(),
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'term' => $term,
            'session' => $session,
        ];
    }
 
    // ------------------------------------------------------------------
    // Reading
    // ------------------------------------------------------------------
 
    /**
     * WHAT IT DOES: One student's subject rows for a term and session, each with its total, plus
     *    the overall total.
     *
     * USED WHEN: GET /grades/students/{student}, for a student's score view.
     *
     * GIVES BACK: {subjects: [...], total: number, max_total: number}.
     */
    public function forStudent(int $studentId, string $term, int $session): array
    {
        $rows = DB::table('grades as g')
            ->join('subjects as s', 's.id', '=', 'g.subject_id')
            ->where('g.school_id', Helper::schoolId())
            ->where('g.student_id', $studentId)
            ->where('g.term', $term)
            ->where('g.session', $session)
            ->orderBy('s.name')
            ->get(['g.subject_id', 's.name as subject_name', 'g.sub_type', 'g.first_test', 'g.second_test', 'g.other_test', 'g.exam', 'g.compiled']);
 
        $subjects = $rows->map(fn ($r) => (array) $r + ['total' => $this->total($r)])->all();
 
        return [
            'subjects' => $subjects,
            'total' => array_sum(array_column($subjects, 'total')),
            'max_total' => array_sum(self::MAX_SCORES) * count($subjects),
        ];
    }
 
    /**
     * WHAT IT DOES: Reads one student's single row for one subject, term and session.
     *
     * USED WHEN: When one row is needed, for example by the compile or result code.
     *
     * GIVES BACK: A plain row object (not an Eloquent model, so never call ->update() on it), or null.
     */
    public function forStudentSubject(int $studentId, int $subjectId, string $term, int $session): ?object
    {
        return DB::table('grades')
            ->where([
                'school_id' => Helper::schoolId(),
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'term' => $term,
                'session' => $session,
            ])
            ->first();
    }
 
    /**
     * WHAT IT DOES: Adds up whichever score columns are filled in for one grade row.
     *
     * USED WHEN: Inside forStudent, and anywhere a row total is needed.
     *
     * GIVES BACK: The total as a whole number (empty scores count as 0).
     */
    public function total(object $grade): int
    {
        return array_sum(array_map(fn ($c) => (int) ($grade->$c ?? 0), self::COMPONENTS));
    }
 
    // ------------------------------------------------------------------
    // Result retrieval: waiting for the stored procedure
    // ------------------------------------------------------------------
 
    /**
     * WHAT IT DOES: Result data for every class in the school for a term and session.
     *
     * USED WHEN: Not wired yet. It will call the stored procedure once it is added.
     *
     * GIVES BACK: A collection of results.
     */
    public function resultsForAllClasses(string $term, int $session): Collection
    {
        $this->notWiredYet();
    }
 
    /**
     * WHAT IT DOES: Result data for one class.
     *
     * USED WHEN: Not wired yet.
     *
     * GIVES BACK: A collection of results.
     */
    public function resultsForClass(int $classId, string $term, int $session): Collection
    {
        $this->notWiredYet();
    }
 
    /**
     * WHAT IT DOES: Result data for a chosen set of classes.
     *
     * USED WHEN: Not wired yet.
     *
     * GIVES BACK: A collection of results.
     */
    public function resultsForClasses(array $classIds, string $term, int $session): Collection
    {
        $this->notWiredYet();
    }
 
    /**
     * WHAT IT DOES: Result data for one student.
     *
     * USED WHEN: Not wired yet.
     *
     * GIVES BACK: A collection of results.
     */
    public function resultsForStudent(int $studentId, string $term, int $session): Collection
    {
        $this->notWiredYet();
    }
 
    /**
     * WHAT IT DOES: Fails loudly instead of returning nothing, so an unfinished result method
     *    cannot be mistaken for "no results".
     *
     * USED WHEN: Inside the four result methods above, until the stored procedure is wired in.
     *
     * GIVES BACK: Nothing (it always throws).
     */
    private function notWiredYet(): never
    {
        throw new \BadMethodCallException('Result retrieval is not wired to the stored procedure yet.');
    }







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


/** WHAT IT DOES: The score sheet for one class, subject, term and session: every active student 
 * in the class with their current scores, the column maximums, and whether 
 * the batch is compiled. A teacher must be assigned to the class and subject.
 * 
 *  USED WHEN: GET /grades/sheet, when a teacher opens the score screen.
 * 
 *  GIVES BACK: {max, compiled, students: [{id, surname, first_name, other_name, pix, 
 * first_test, second_test, other_test, exam, total, compiled}]}. */


public function sheet(User $user, int $classId, int $subjectId, string $term, int $session): array
{
    $this->assertMayRecord($user, Helper::schoolId(), $classId, $subjectId);

    $grades = DB::table('grades')
        ->where($this->batchScope($classId, $subjectId, $term, $session))
        ->get()->keyBy('student_id');

    $students = Enrollment::where('class_id', $classId)
        ->with('student:id,surname,first_name,other_name,pix')
        ->get()
        ->filter(fn ($e) => $e->student)
        ->sortBy(fn ($e) => $e->student->surname)
        ->map(function ($e) use ($grades) {
            $g = $grades->get($e->student_id);

            return [
                'id' => $e->student_id,
                'surname' => $e->student->surname,
                'first_name' => $e->student->first_name,
                'other_name' => $e->student->other_name,
                'pix' => $e->student->pix,
                'first_test' => $g->first_test ?? null,
                'second_test' => $g->second_test ?? null,
                'other_test' => $g->other_test ?? null,
                'exam' => $g->exam ?? null,
                'total' => $g ? $this->total($g) : null,
                'compiled' => (bool) ($g->compiled ?? false),
            ];
        })->values()->all();

    return [
        'max' => self::MAX_SCORES,
        'compiled' => $students !== [] && collect($students)->every(fn ($s) => $s['compiled']),
        'students' => $students,
    ];
}
/** WHAT IT DOES: Validation rules for preview and push.
 *  USED WHEN: By the controller. 
 * GIVES BACK: Rules array. */

public function pushRules(): array
{
    return [
        'class_id' => ['required', 'integer', 'exists:my_classes,id'],
        'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        'term' => ['required', Rule::in(['1', '2', '3'])],
        'session' => ['required', 'integer', 'digits:4'],
        'column' => ['required', Rule::in(self::PUSH_COLUMNS)],
        'titles' => ['required', 'array', 'min:1'],
        'titles.*' => ['string'],
        'student_ids' => ['sometimes', 'array'],
        'student_ids.*' => ['integer'],
    ];
}

/** WHAT IT DOES: Lists the CBT titles available to push for a class and subject,
 *  saying which ones would be averaged.
 *  USED WHEN: GET /grades/pushable.
 *  GIVES BACK: [{title, key, students, max_per_student, averaged, pushed}]. */

public function pushableTitles(int $classId, int $subjectId): array
{
    return $this->cbts->attemptsFor(Helper::schoolId(), $classId, $subjectId)
        ->groupBy(fn ($a) => $this->titleKey($a->cbt_title))
        ->map(function ($rows, $key) {
            $perStudent = $rows->countBy('student_id');

            return [
                'title' => $rows->first()->cbt_title,
                'key' => $key,
                'students' => $perStudent->count(),
                'max_per_student' => $perStudent->max(),
                'averaged' => $perStudent->max() > 1,
                'pushed' => $rows->every(fn ($a) => $a->pushed_at !== null),
            ];
        })->values()->all();
}

/** WHAT IT DOES: Shows what a push would write, per student, without saving. 
 * USED WHEN: POST /grades/push/preview.
 *  GIVES BACK: {column, out_of, averaged, notice, rows[]}. */

public function previewPush(array $in): array
{
    $marks = $this->marksFromCbts($in);
    $max = self::MAX_SCORES[$in['column']];

    $current = DB::table('grades')
        ->where($this->batchScope((int) $in['class_id'], (int) $in['subject_id'], (string) $in['term'], (int) $in['session']))
        ->whereIn('student_id', array_keys($marks))->get()->keyBy('student_id');

    $mostPer = max(array_column($marks, 'attempts') ?: [0]);

    return [
        'column' => $in['column'],
        'out_of' => $max,
        'averaged' => $mostPer > 1,
        'notice' => $mostPer > 1 ? "Some students have $mostPer CBTs under these titles. Their scores will be averaged to $max." : null,
        'rows' => collect($marks)->map(fn ($m, $sid) => [
            'student_id' => $sid,
            'attempts' => $m['attempts'],
            'average_percent' => $m['average'],
            'mark' => $m['mark'],
            'current' => $current->get($sid)->{$in['column']} ?? null,
            'locked' => (bool) ($current->get($sid)->compiled ?? false),
        ])->values()->all(),
    ];
}

/** WHAT IT DOES: Writes the worked-out marks into the chosen column through
 *  recordScores (so maximums, the compile lock and the teacher check all apply),
 *  then stamps the attempts as pushed. Safe to repeat.
 *  USED WHEN: POST /grades/push. 
 * GIVES BACK: {saved, locked}. */

public function pushScores(User $user, array $in): array
{
    $marks = $this->marksFromCbts($in);
    $rows = collect($marks)->map(fn ($m, $sid) => ['student_id' => $sid, $in['column'] => $m['mark']])->values()->all();

    if (! $rows) {
        return ['saved' => 0, 'locked' => []];
    }

    $result = $this->recordScores($user, $in, $rows);

  $pushedAttempts = collect($marks)->except([...$result['locked'], ...$result['not_enrolled']])->pluck('attempt_ids')->flatten()->all();
    $this->cbts->markPushed($pushedAttempts);

    return $result;
    
}

/** WHAT IT DOES: Per student, averages the percentages of the selected titles' 
 * attempts and scales to the column's maximum, rounded to a whole 
 * mark. Students with no matching attempt are left out, so they are never zeroed. 
 * USED WHEN: Inside previewPush and pushScores.
 *  GIVES BACK: [studentId => {attempts, average, mark, attempt_ids}]. */

private function marksFromCbts(array $in): array
{
    $keys = array_map([$this, 'titleKey'], $in['titles']);
    $max = self::MAX_SCORES[$in['column']];

    return $this->cbts->attemptsFor(Helper::schoolId(), (int) $in['class_id'], (int) $in['subject_id'])
        ->filter(fn ($a) => in_array($this->titleKey($a->cbt_title), $keys, true))
        ->when(! empty($in['student_ids']), fn ($c) => $c->whereIn('student_id', $in['student_ids']))
        ->groupBy('student_id')
        ->map(function ($rows) use ($max) {
            $percent = $rows->filter(fn ($a) => $a->total > 0)->map(fn ($a) => $a->score / $a->total);
            if ($percent->isEmpty()) {
                return null;
            }
            $avg = $percent->avg();

            return ['attempts' => $rows->count(), 'average' => round($avg * 100, 1), 'mark' => (int) round($avg * $max), 'attempt_ids' => $rows->pluck('id')->all()];
        })->filter()->all();
}

/** WHAT IT DOES: Makes "CAT 1", "cat1" and "Cat1" the same title.
 *  USED WHEN: When grouping and matching titles. 
 * GIVES BACK: A lowercase string without spaces. */

private function titleKey(?string $title): string
{
    return mb_strtolower(preg_replace('/\s+/', '', (string) $title));
}
}