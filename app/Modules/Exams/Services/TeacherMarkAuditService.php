<?php

namespace App\Modules\Exams\Services;

use App\Models\Mark;
use App\Models\MarkEditLog;
use App\Models\Teacher;
use App\Models\User;
use App\Modules\Exams\Enums\ExamType;
use App\Notifications\MarkEntryModifiedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class TeacherMarkAuditService
{
    public function resolveTeacher(int $userId): ?Teacher
    {
        return Teacher::query()->where('user_id', $userId)->first();
    }

    public function resolveTeacherOrFail(int $userId): Teacher
    {
        $teacher = $this->resolveTeacher($userId);
        if (! $teacher) {
            throw new RuntimeException('Teacher profile not found.');
        }

        return $teacher;
    }

    public function canEdit(Mark $mark): bool
    {
        if (! $mark->created_at) {
            return false;
        }

        return $mark->created_at->gte(now()->subDays(7));
    }

    public function updateMarkEntry(int $userId, Mark $mark, int $newMarks, string $reason): void
    {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->assertOwnership($teacher, $mark);

        if (! $this->canEdit($mark)) {
            throw new RuntimeException('Editing window has expired. You can edit marks only within 7 days of entry.');
        }

        $totalMarks = (int) $mark->total_marks;
        if ($newMarks < 0 || $newMarks > $totalMarks) {
            throw new RuntimeException('Obtained marks must be between 0 and total marks.');
        }

        $mark->loadMissing([
            'teacher.user:id,name',
            'student:id,name',
            'exam:id,class_id,subject_id,exam_type',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        $oldMarks = (int) $mark->obtained_marks;
        $editedAt = now();

        DB::transaction(function () use ($mark, $newMarks, $userId, $reason, $oldMarks, $editedAt): void {
            $mark->forceFill([
                'obtained_marks' => $newMarks,
            ])->save();

            MarkEditLog::query()->create([
                'mark_id' => $mark->id,
                'old_marks' => $oldMarks,
                'new_marks' => $newMarks,
                'edited_by' => $userId,
                'edit_reason' => $reason,
                'action_type' => 'edit',
                'edited_at' => $editedAt,
            ]);
        });

        $mark->refresh()->loadMissing([
            'teacher.user:id,name',
            'student:id,name',
            'exam:id,class_id,subject_id,exam_type',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        $this->notifyPrincipals(
            $this->buildNotificationPayload($mark, 'edit', $oldMarks, $newMarks, $editedAt)
        );
    }

    public function deleteMarkEntry(int $userId, Mark $mark, string $reason): void
    {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->assertOwnership($teacher, $mark);

        $mark->loadMissing([
            'teacher.user:id,name',
            'student:id,name',
            'exam:id,class_id,subject_id,exam_type',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        $oldMarks = (int) $mark->obtained_marks;
        $editedAt = now();
        $notificationPayload = $this->buildNotificationPayload($mark, 'delete', $oldMarks, null, $editedAt);

        DB::transaction(function () use ($mark, $userId, $reason, $oldMarks, $editedAt): void {
            MarkEditLog::query()->create([
                'mark_id' => $mark->id,
                'old_marks' => $oldMarks,
                'new_marks' => null,
                'edited_by' => $userId,
                'edit_reason' => $reason,
                'action_type' => 'delete',
                'edited_at' => $editedAt,
            ]);

            $mark->delete();
        });

        $this->notifyPrincipals($notificationPayload);
    }

    private function assertOwnership(Teacher $teacher, Mark $mark): void
    {
        if ((int) $mark->teacher_id !== (int) $teacher->id) {
            throw new AuthorizationException('You can modify only your own mark entries.');
        }
    }

    private function notifyPrincipals(array $payload): void
    {
        $recipients = User::role('Principal')
            ->where(function ($query): void {
                $query->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->get(['id', 'name', 'email']);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new MarkEntryModifiedNotification($payload));
    }

    private function buildNotificationPayload(
        Mark $mark,
        string $actionType,
        int $oldMarks,
        ?int $newMarks,
        Carbon $editedAt
    ): array {
        return [
            'action_type' => $actionType,
            'teacher_name' => (string) ($mark->teacher?->user?->name ?? 'Teacher'),
            'student_name' => (string) ($mark->student?->name ?? 'Student'),
            'class_name' => trim((string) (($mark->exam?->classRoom?->name ?? 'Class').' '.($mark->exam?->classRoom?->section ?? ''))),
            'subject_name' => (string) ($mark->exam?->subject?->name ?? 'Subject'),
            'exam_type' => $this->examTypeLabel($mark->exam?->exam_type),
            'old_marks' => $oldMarks,
            'new_marks' => $newMarks,
            'changed_at' => $editedAt->toDateTimeString(),
            'url' => route('notifications.index'),
        ];
    }

    private function examTypeLabel(mixed $examType): string
    {
        if ($examType instanceof ExamType) {
            return $examType->label();
        }

        $raw = (string) $examType;
        $type = ExamType::tryFrom($raw);
        if ($type) {
            return $type->label();
        }

        return str_replace('_', ' ', ucfirst($raw));
    }
}
