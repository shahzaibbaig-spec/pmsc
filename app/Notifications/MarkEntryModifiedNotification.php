<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MarkEntryModifiedNotification extends Notification
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
        $actionType = (string) ($this->payload['action_type'] ?? 'edit');
        $isDelete = $actionType === 'delete';
        $title = $isDelete ? 'Mark Entry Deleted' : 'Mark Entry Updated';
        $oldMarks = $this->payload['old_marks'] ?? null;
        $newMarks = $this->payload['new_marks'] ?? null;

        return [
            'type' => 'mark_entry_modified',
            'title' => $title,
            'message' => sprintf(
                '%s %s marks for %s (%s, %s, %s): %s -> %s at %s.',
                $this->payload['teacher_name'] ?? 'Teacher',
                $isDelete ? 'deleted' : 'updated',
                $this->payload['student_name'] ?? 'student',
                $this->payload['class_name'] ?? 'class',
                $this->payload['subject_name'] ?? 'subject',
                $this->payload['exam_type'] ?? 'exam',
                $oldMarks === null ? '-' : (string) $oldMarks,
                $newMarks === null ? 'deleted' : (string) $newMarks,
                $this->payload['changed_at'] ?? now()->toDateTimeString()
            ),
            'action_type' => $actionType,
            'teacher_name' => $this->payload['teacher_name'] ?? null,
            'student_name' => $this->payload['student_name'] ?? null,
            'class_name' => $this->payload['class_name'] ?? null,
            'subject_name' => $this->payload['subject_name'] ?? null,
            'exam_type' => $this->payload['exam_type'] ?? null,
            'old_marks' => $oldMarks,
            'new_marks' => $newMarks,
            'changed_at' => $this->payload['changed_at'] ?? null,
            'url' => $this->payload['url'] ?? route('notifications.index'),
        ];
    }
}
