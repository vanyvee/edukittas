<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Tells a student that a CBT (exam, test, practice or assignment) has been set for them.
 * Goes out in the app (database notification) and by email, through the queue.
 * Carries plain values, not the CBT model, so it still works if the CBT is deleted before the job runs.
 */
class CbtAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $afterCommit = true;

    public function __construct(
        public int $cbtId,
        public string $title,
        public string $type,
        public ?string $opensAt,
        public ?string $closesAt,
        public ?int $durationMinutes,
    ) {
    }

    /** Which channels to use. Add 'broadcast' or a push channel here when the mobile app arrives. */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = ucfirst($this->type);

        $mail = (new MailMessage)
            ->subject("New {$label}: {$this->title}")
            ->line("You have been given a new {$this->type}: {$this->title}.");

        if ($this->opensAt) {
            $mail->line('Opens: ' . $this->when($this->opensAt));
        }
        if ($this->closesAt) {
            $mail->line('Deadline: ' . $this->when($this->closesAt));
        }
        if ($this->durationMinutes) {
            $mail->line("Once you start, you will have {$this->durationMinutes} minutes.");
        }

        return $mail->action('Open Edukittas', url('/'));
    }

    /** What is stored for the in-app notification list. */
    public function toArray(object $notifiable): array
    {
        return [
            'cbt_id' => $this->cbtId,
            'title' => $this->title,
            'type' => $this->type,
            'opens_at' => $this->opensAt,
            'closes_at' => $this->closesAt,
            'message' => "New {$this->type}: {$this->title}",
        ];
    }

    private function when(string $iso): string
    {
        return Carbon::parse($iso)->format('D, j M Y, g:ia');
    }
}
