<?php

namespace App\Notifications;

use App\Notifications\Concerns\SupportsOptionalWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceCutoffReminderNotification extends Notification
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
        $classNames = collect($this->payload['pending_classes'] ?? [])
            ->pluck('class_name')
            ->filter()
            ->values()
            ->implode(', ');

        return [
            'type' => 'attendance_cutoff_reminder',
            'title' => 'Attendance Cutoff Reminder',
            'attendance_date' => $this->payload['attendance_date'] ?? now()->toDateString(),
            'cutoff_time' => $this->payload['cutoff_time'] ?? null,
            'pending_class_ids' => collect($this->payload['pending_classes'] ?? [])->pluck('class_id')->values()->all(),
            'message' => sprintf(
                'Attendance is pending for %s before cutoff %s.',
                $classNames !== '' ? $classNames : 'your assigned classes',
                $this->payload['cutoff_time'] ?? '-'
            ),
            'url' => route('teacher.attendance.index'),
        ];
    }

    public function toWebPush(object $notifiable, object $notification): mixed
    {
        return $this->buildWebPushMessage($this->toArray($notifiable));
    }
}
