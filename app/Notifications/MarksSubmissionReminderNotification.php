<?php

namespace App\Notifications;

use App\Notifications\Concerns\SupportsOptionalWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MarksSubmissionReminderNotification extends Notification
{
    use Queueable, SupportsOptionalWebPush;

    public function __construct(private readonly array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalWebPush($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marks_submission_reminder',
            'title' => 'Marks Submission Reminder',
            'session' => $this->payload['session'] ?? null,
            'reminder_date' => $this->payload['reminder_date'] ?? now()->toDateString(),
            'message' => sprintf(
                'Please submit marks for your assigned classes for session %s.',
                $this->payload['session'] ?? '-'
            ),
            'url' => route('teacher.exams.index'),
        ];
    }

    public function toWebPush(object $notifiable, object $notification): mixed
    {
        return $this->buildWebPushMessage($this->toArray($notifiable));
    }
}
