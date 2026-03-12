<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResultsPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'results_published',
            'title' => 'Results Published',
            'message' => sprintf(
                'Results published for %s (%s - %s).',
                $this->payload['class_name'] ?? 'selected class',
                $this->payload['session'] ?? '-',
                $this->payload['exam_type_label'] ?? '-'
            ),
            'class_id' => $this->payload['class_id'] ?? null,
            'class_name' => $this->payload['class_name'] ?? null,
            'session' => $this->payload['session'] ?? null,
            'exam_type' => $this->payload['exam_type'] ?? null,
            'exam_type_label' => $this->payload['exam_type_label'] ?? null,
            'published_by' => $this->payload['published_by'] ?? null,
            'published_at' => $this->payload['published_at'] ?? now()->toDateTimeString(),
            'url' => $this->payload['url'] ?? route('dashboard'),
        ];
    }
}

