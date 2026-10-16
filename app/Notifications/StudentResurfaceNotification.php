<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database channel only — pure SPA frontend polls/reads Laravel's built-in
 * notifications table, no mail/blade involved.
 */
class StudentResurfacedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Enrollment $newEnrollment,
        protected Enrollment $priorEnrollment
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message'             => 'A student who withdrew from your school has enrolled elsewhere.',
            'student_id'          => $this->newEnrollment->student_id,
            'new_enrollment_id'   => $this->newEnrollment->id,
            'prior_enrollment_id' => $this->priorEnrollment->id,
        ];
    }
}