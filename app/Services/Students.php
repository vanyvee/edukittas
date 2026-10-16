<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;

/**
 * Student = identity (no school_id/status/class_id — those moved to
 * Enrollment). This service owns three separate concerns that used to be
 * tangled into one table:
 *   1. Registration — guardian lookup-or-confirm, student dedupe, and the
 *      first enrollment, all in one transaction.
 *   2. Enrollment lifecycle — status/class changes now act on Enrollment,
 *      not Student, since that's where those columns live.
 *   3. Cross-school visibility — history() and the dedupe lookups
 *      deliberately search across every school, unlike Enrollment's own
 *      locked 'school' global scope.
 */
class StudentService extends Service
{
    protected function resolveGuardian(array $guardianData): Guardian
{
    $guardianId = session('guardian_id');
    $confirmed  = session('guardian_confirmed');

    if (! $guardianId && ! $confirmed) {
        $guardian = Guardian::create($guardianData);
        $this->sendGuardianClaimLink($guardian);
        return $guardian;
    }

    if ($guardianId && $confirmed) {
        return Guardian::findOrFail($guardianId);
    }

    throw new RegistrationPaused(['status' => 'there is a mixup or unconfirmed guardian']);
}

protected function resolveStudent(array $studentData, int $guardianId): Student
{
    $studentId = session('student_id');
    $confirmed = session('student_confirmed');

    if (! $studentId && ! $confirmed) {
        return Student::create([...$studentData, 'guardian_id' => $guardianId]);
    }

    if ($studentId && $confirmed) {
        return Student::findOrFail($studentId);
    }

    throw new RegistrationPaused(['status' => 'there is a mixup or unconfirmed student status']);
}





public function register(array $data): array
{
    return $this->transaction(function () use ($data) {
        try {
            $guardian = $this->resolveGuardian($data['guardian']);
            $student  = $this->resolveStudent($data['student'], $guardian->id);
        } catch (RegistrationPaused $pause) {
            return $pause->payload;
        }

        $enrollment = $this->enroll($student, $data['enrollment']);

        session()->forget(['guardian_id', 'guardian_confirmed', 'student_id', 'student_confirmed']);

        return [
            'status'     => 'registered',
            'guardian'   => $guardian,
            'student'    => $student,
            'enrollment' => $enrollment,
        ];
    });
}

    /**
     * A signed, expiring URL (same idea as Laravel's password-reset tokens)
     * the parent opens to set a password whenever they choose.
     */
    protected function sendGuardianClaimLink(Guardian $guardian): void
    {
        $guardian->notify(new \App\Notifications\GuardianClaimLinkNotification());
    }

   

    /**
     * Attach a new enrollment to an existing student identity — the
     * "transferred to a new school" or "returned after withdrawing" case.
     * Always a NEW row, never a reused one (see the earlier discussion on
     * why: a clean timeline, no ambiguity about re-entry class-level).
     * school_id is auto-set from session by Enrollment::creating() if not
     * passed explicitly.
     */
   public function enroll(Student $student, array $enrollmentData): Enrollment
{
    return $this->transaction(function () use ($student, $enrollmentData) {
        // Never allow two active enrollments at once 
        Enrollment::withoutGlobalScope('school')
            ->where('student_id', $student->id)
            ->where('status', '1')
            ->update(['status' => '0']);

        $enrollment = $student->enrollments()->create($enrollmentData);

        event(new \App\Events\StudentEnrolled($enrollment));

        return $enrollment;
    });
}


    /**
     * Every enrollment this student has ever had, at any school, regardless
     * of status — the "academic movement" history from the original design
     * conversation. Deliberately lifts Enrollment's locked 'school' scope,
     * same escape-hatch pattern as the User dedupe check.
     */
    public function history(Student $student): Collection
    {
        return Enrollment::query()
            ->withoutGlobalScope('school')
            ->status('all')
            ->where('student_id', $student->id)
            ->with('school', 'myClass')
            ->get();
    }

    // ------------------------------------------------------------------
    // Enrollment lifecycle — these now act on Enrollment, not Student,
    // since status/class_id/arm live there. Signatures otherwise match
    // what StudentService had before the split.

    public function graduate(Enrollment $enrollment): Enrollment
    {
        $enrollment->update(['status' => '2', 'graduation_date' => now()->toDateString()]);

        return $enrollment->fresh();
    }

    public function withdraw(Enrollment $enrollment): Enrollment
    {
        $enrollment->update(['status' => '0']);

        return $enrollment->fresh();
    }

    public function reEnroll(Enrollment $enrollment): Enrollment
    {
        $enrollment->update(['status' => '1']);
        return $enrollment->fresh();
    }


    public function deactivate(Enrollment $enrollment): Enrollment
    {
        $enrollment->update(['status' => '0']);
        return $enrollment->fresh();

    }
    public function activate(Enrollment $enrollment): Enrollment
    {
        $enrollment->update(['status' => '1']);
        return $enrollment->fresh();

    }


    public function promote(Enrollment $enrollment, int $newClassId): Enrollment
    {
        $enrollment->update(['class_id' => $newClassId]);

        return $enrollment->fresh();
    }

    public function transferClass(Enrollment $enrollment, int $newClassId, ?string $reason = null): Enrollment
    {
        $enrollment->update(['class_id' => $newClassId]);

        return $enrollment->fresh();
    }

    public function changeArm(Enrollment $enrollment, string $arm): Enrollment
    {
        $enrollment->update(['arm' => $arm]);

        return $enrollment->fresh();
    }

    // ------------------------------------------------------------------
    // Guardian link — stays on Student, since guardian_id is identity-level
    // (doesn't change per enrollment). guardian_id is nullable now, so
    // unlinkGuardian is a plain null-out, no constraint conflict.

    public function linkGuardian(Student $student, int $guardianId): Student
    {
        $student->update(['guardian_id' => $guardianId]);

        return $student->fresh();
    }

    public function unlinkGuardian(Student $student): Student
    {
        $student->update(['guardian_id' => null]);

        return $student->fresh();
    }

    // ------------------------------------------------------------------
    // Listings — forClass/forSchool now query Enrollment (that's where
    // class_id/school_id/status live); forGuardian stays on Student.

    public function forClass(int $classId, string $status = 'active'): Collection
    {
        return Enrollment::status($status)->where('class_id', $classId)->with('student')->get();
    }

    public function forGuardian(int $guardianId): Collection
    {
        return Student::where('guardian_id', $guardianId)->get();
    }

    /**
     * Current session's school (locked by Enrollment's 'school' global scope).
     */
    public function forSchool(string $status = 'active'): Collection
    {
        return Enrollment::status($status)->with('student')->get();
    }
}