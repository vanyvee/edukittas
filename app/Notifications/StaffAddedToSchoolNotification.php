<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * For someone who already has a login: no link, no new account. They are told which school added them.
 */
class StaffAddedToSchoolNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ?string $schoolName = null)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $school = $this->schoolName ?: 'a new school';

        return (new MailMessage)
            ->subject("You have been added to {$school}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line("{$school} has registered you as a staff member.")
            ->line('You do not need a new account. Log in with your usual username and password, then choose this school from your list of schools.')
            ->action('Log in', url('/login'))
            ->line('If you were not expecting this, please contact the school.');
    }
}
