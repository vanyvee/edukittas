<?php

namespace App\Listeners;

use App\Events\StudentEnrolled;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\StudentResurfacedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Fires on every enrollment, checks itself whether there's anything worth
 * notifying — keeps this logic out of StudentService entirely.
 */
class NotifyPreviousSchoolOfWithdrawal
{
    public function handle(StudentEnrolled $event): void
    {
        $enrollment = $event->enrollment;

        $priorWithdrawn = Enrollment::withoutGlobalScope('school')
            ->status('inactive')
            ->where('student_id', $enrollment->student_id)
            ->where('school_id', '!=', $enrollment->school_id)
            ->get();

        foreach ($priorWithdrawn as $prior) {
            $admins = User::withoutGlobalScope('school')
                ->status('active')
                ->forSchool($prior->school_id)
                ->whereIn('role', ['director', 'ictAdmin', 'principal'])
                ->get();

            Notification::send($admins, new StudentResurfacedNotification($enrollment, $prior));
        }
    }
}