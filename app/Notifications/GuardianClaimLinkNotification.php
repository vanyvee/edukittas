<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class GuardianClaimLinkNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'guardian.claim.show',
            now()->addDays(7),
            ['guardian' => $notifiable->id]
        );

        return (new MailMessage)
            ->subject('Activate your parent account')
            ->line('A student has been registered with this email address as the guardian contact.')
            ->action('Set up your account', $url)
            ->line('This link expires in 7 days. If you already have an account, you can ignore this email.');
    }
}