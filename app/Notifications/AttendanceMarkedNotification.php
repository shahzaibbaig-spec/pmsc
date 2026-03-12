<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceMarkedNotification extends Notification
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
            'type' => 'attendance_marked',
            'title' => 'Attendance Marked',
            'message' => sprintf(
                '%s marked attendance for %s on %s.',
                $this->payload['marked_by'] ?? 'Teacher',
                $this->payload['class_name'] ?? 'class',
                $this->payload['date'] ?? now()->toDateString()
            ),
            'class_id' => $this->payload['class_id'] ?? null,
            'class_name' => $this->payload['class_name'] ?? null,
            'date' => $this->payload['date'] ?? null,
            'marked_by' => $this->payload['marked_by'] ?? null,
            'present' => $this->payload['present'] ?? 0,
            'absent' => $this->payload['absent'] ?? 0,
            'leave' => $this->payload['leave'] ?? 0,
            'url' => $this->payload['url'] ?? route('dashboard'),
        ];
    }
}

