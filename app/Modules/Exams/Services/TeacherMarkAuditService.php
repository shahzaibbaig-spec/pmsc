<?php

namespace App\Modules\Exams\Services;

use App\Models\Mark;
use App\Models\MarkEditLog;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ClassAssessmentModeService;
use App\Services\TeacherPerformanceSyncService;
use App\Modules\Exams\Enums\ExamType;
use App\Notifications\MarkEntryModifiedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class TeacherMarkAuditService
{
    public function __construct(
        private readonly ClassAssessmentModeService $assessmentModeService,
        private readonly TeacherPerformanceSyncService $teacherPerformanceSyncService
    ) {
    }

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

    public function updateMarkEntry(int $userId, Mark $mark, ?int $newMarks, ?string $newGrade, string $reason): void
    {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->assertOwnership($teacher, $mark);

        if (! $this->canEdit($mark)) {
            throw new RuntimeException('Editing window has expired. You can edit entries only within 7 days of entry.');
        }

        $mark->loadMissing([
            'teacher.user:id,name',
            'student:id,name',
            'exam:id,class_id,subject_id,exam_type,total_marks',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        $usesGradeSystem = $this->assessmentModeService->classUsesGradeSystem($mark->exam?->classRoom);
        $oldMarks = $mark->obtained_marks !== null ? (int) $mark->obtained_marks : null;
        $oldGrade = $this->assessmentModeService->normalizeGrade($mark->grade);
        $editedAt = now();

        if ($usesGradeSystem) {
            $normalizedGrade = $this->assessmentModeService->normalizeGrade($newGrade);
            if ($normalizedGrade === null || ! $this->assessmentModeService->isValidGrade($normalizedGrade)) {
                throw new RuntimeException('A valid grade is required for this class.');
            }

            DB::transaction(function () use ($mark, $normalizedGrade, $userId, $reason, $oldMarks, $oldGrade, $editedAt): void {
                $mark->forceFill([
                    'obtained_marks' => null,
                    'total_marks' => null,
                    'grade' => $normalizedGrade,
                ])->save();

                MarkEditLog::query()->create([
                    'mark_id' => $mark->id,
                    'old_marks' => $oldMarks,
                    'new_marks' => null,
                    'old_grade' => $oldGrade,
                    'new_grade' => $normalizedGrade,
                    'edited_by' => $userId,
                    'edit_reason' => $reason,
                    'action_type' => 'edit',
                    'edited_at' => $editedAt,
                ]);
            });

            $this->teacherPerformanceSyncService->syncAfterMarksChange(
                (int) $teacher->id,
                (string) $mark->session,
                $this->examTypeValue($mark)
            );

            $this->dispatchUpdatedNotification($mark, 'grade', $oldMarks, null, $oldGrade, $normalizedGrade, $editedAt);

            return;
        }

        $totalMarks = (int) ($mark->total_marks ?? $mark->exam?->total_marks ?? 0);
        if ($newMarks === null || $newMarks < 0 || $newMarks > $totalMarks) {
            throw new RuntimeException('Obtained marks must be between 0 and total marks.');
        }

        DB::transaction(function () use ($mark, $newMarks, $userId, $reason, $oldMarks, $editedAt): void {
            $mark->forceFill([
                'obtained_marks' => $newMarks,
                'grade' => null,
            ])->save();

            MarkEditLog::query()->create([
                'mark_id' => $mark->id,
                'old_marks' => $oldMarks,
                'new_marks' => $newMarks,
                'old_grade' => null,
                'new_grade' => null,
                'edited_by' => $userId,
                'edit_reason' => $reason,
                'action_type' => 'edit',
                'edited_at' => $editedAt,
            ]);
        });

        $this->teacherPerformanceSyncService->syncAfterMarksChange(
            (int) $teacher->id,
            (string) $mark->session,
            $this->examTypeValue($mark)
        );

        $this->dispatchUpdatedNotification($mark, 'marks', $oldMarks, $newMarks, null, null, $editedAt);
    }

    public function deleteMarkEntry(int $userId, Mark $mark, string $reason): void
    {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->assertOwnership($teacher, $mark);

        $mark->loadMissing([
            'teacher.user:id,name',
            'student:id,name',
            'exam:id,class_id,subject_id,exam_type,total_marks',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        $usesGradeSystem = $this->assessmentModeService->classUsesGradeSystem($mark->exam?->classRoom);
        $oldMarks = $mark->obtained_marks !== null ? (int) $mark->obtained_marks : null;
        $oldGrade = $this->assessmentModeService->normalizeGrade($mark->grade);
        $editedAt = now();
        $notificationPayload = $this->buildNotificationPayload(
            $mark,
            'delete',
            $usesGradeSystem ? 'grade' : 'marks',
            $oldMarks,
            null,
            $oldGrade,
            null,
            $editedAt
        );

        DB::transaction(function () use ($mark, $userId, $reason, $oldMarks, $oldGrade, $editedAt): void {
            MarkEditLog::query()->create([
                'mark_id' => $mark->id,
                'old_marks' => $oldMarks,
                'new_marks' => null,
                'old_grade' => $oldGrade,
                'new_grade' => null,
                'edited_by' => $userId,
                'edit_reason' => $reason,
                'action_type' => 'delete',
                'edited_at' => $editedAt,
            ]);

            $mark->delete();
        });

        $this->teacherPerformanceSyncService->syncAfterMarksChange(
            (int) $teacher->id,
            (string) $mark->session,
            $this->examTypeValue($mark)
        );

        $this->notifyPrincipals($notificationPayload);
    }

    private function dispatchUpdatedNotification(
        Mark $mark,
        string $entryMode,
        ?int $oldMarks,
        ?int $newMarks,
        ?string $oldGrade,
        ?string $newGrade,
        Carbon $editedAt
    ): void {
        $mark->refresh()->loadMissing([
            'teacher.user:id,name',
            'student:id,name',
            'exam:id,class_id,subject_id,exam_type,total_marks',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        $this->notifyPrincipals(
            $this->buildNotificationPayload(
                $mark,
                'edit',
                $entryMode,
                $oldMarks,
                $newMarks,
                $oldGrade,
                $newGrade,
                $editedAt
            )
        );
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
        string $entryMode,
        ?int $oldMarks,
        ?int $newMarks,
        ?string $oldGrade,
        ?string $newGrade,
        Carbon $editedAt
    ): array {
        $oldValue = $entryMode === 'grade'
            ? ($oldGrade ?? '-')
            : ($oldMarks === null ? '-' : (string) $oldMarks);
        $newValue = $entryMode === 'grade'
            ? ($newGrade ?? 'deleted')
            : ($newMarks === null ? 'deleted' : (string) $newMarks);

        return [
            'action_type' => $actionType,
            'entry_mode' => $entryMode,
            'teacher_name' => (string) ($mark->teacher?->user?->name ?? 'Teacher'),
            'student_name' => (string) ($mark->student?->name ?? 'Student'),
            'class_name' => trim((string) (($mark->exam?->classRoom?->name ?? 'Class').' '.($mark->exam?->classRoom?->section ?? ''))),
            'subject_name' => (string) ($mark->exam?->subject?->name ?? 'Subject'),
            'exam_type' => $this->examTypeLabel($mark->exam?->exam_type),
            'old_marks' => $oldMarks,
            'new_marks' => $newMarks,
            'old_grade' => $oldGrade,
            'new_grade' => $newGrade,
            'old_value' => $oldValue,
            'new_value' => $newValue,
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

    private function examTypeValue(Mark $mark): ?string
    {
        $examType = $mark->exam?->exam_type;

        if ($examType instanceof ExamType) {
            return $examType->value;
        }

        if (is_string($examType)) {
            return $examType;
        }

        return null;
    }
}
