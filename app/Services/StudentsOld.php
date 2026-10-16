<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudentService
{
    /**
     * Register-style listing. Tenancy comes from the model's global
     * scope, so this only handles the filters.
     *
     * $filters: status (all|active|inactive|graduated), class_id, arm, search
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Student::query()
            ->with('myClass')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->status($status))
            ->when($filters['class_id'] ?? null, fn ($q, $id) => $q->where('class_id', $id))
            ->when($filters['arm'] ?? null, fn ($q, $arm) => $q->where('arm', $arm))
            ->when($filters['search'] ?? null, function ($q, $term) {
                $like = '%'.trim($term).'%';
                $q->where(fn ($w) => $w
                    ->where('surname', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('username', 'like', $like));
            })
            ->orderBy('surname')
            ->orderBy('first_name')
            ->paginate($perPage);
    }

    /**
     * Enrol a student, optionally creating the guardian in the same
     * transaction so you never end up with a guardian and no ward (or
     * the reverse). For an existing guardian, put guardian_id in
     * $student and leave $guardian null.
     *
     * $schoolId is only for the director-enrolling-into-another-school
     * case; otherwise the current school is stamped automatically.
     */
    public function enroll(array $student, ?array $guardian = null, ?int $schoolId = null): Student
    {
        return DB::transaction(function () use ($student, $guardian, $schoolId) {
            if ($guardian) {
                $student['guardian_id'] = Guardian::create($guardian)->id;
            }

            if ($schoolId) {
                $student['school_id'] = $schoolId;
            }

            return Student::create($student);
        });
    }

    /** Mark students graduated and stamp the date. Returns rows affected. */
    public function graduate(array $studentIds, ?Carbon $on = null): int
    {
        return Student::whereIn('id', $studentIds)->update([
            'status'          => '2',
            'graduation_date' => ($on ?? now())->toDateString(),
        ]);
    }

    /** Move students to another class (and arm) at promotion time. */
    public function promote(array $studentIds, int $toClassId, ?string $arm = null): int
    {
        $changes = ['class_id' => $toClassId];

        if ($arm !== null) {
            $changes['arm'] = $arm;
        }

        return Student::whereIn('id', $studentIds)->update($changes);
    }
} */