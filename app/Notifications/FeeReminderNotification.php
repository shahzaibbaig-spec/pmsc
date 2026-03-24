<?php

namespace App\Notifications;

use App\Notifications\Concerns\SupportsOptionalWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FeeReminderNotification extends Notification
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
            'type' => 'fee_defaulter_reminder',
            'title' => (string) ($this->payload['title'] ?? 'Fee Reminder'),
            'message' => (string) ($this->payload['message'] ?? 'Outstanding fee reminder.'),
            'student_id' => (int) ($this->payload['student_id'] ?? 0),
            'student_name' => (string) ($this->payload['student_name'] ?? ''),
            'student_code' => (string) ($this->payload['student_code'] ?? ''),
            'session' => (string) ($this->payload['session'] ?? ''),
            'total_due' => round((float) ($this->payload['total_due'] ?? 0), 2),
            'url' => $this->payload['url'] ?? route('principal.fees.defaulters.index'),
        ];
    }

    public function toWebPush(object $notifiable, object $notification): mixed
    {
        return $this->buildWebPushMessage($this->toArray($notifiable));
    }
}
