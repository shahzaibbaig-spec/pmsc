<?php

namespace App\Modules\Exams\Services;

use App\Models\Mark;
use App\Models\MarkEditLog;
use App\Models\Teacher;
use App\Models\TeacherResultEntryLog;
use App\Models\User;
use App\Services\AssessmentMarkingModeService;
use App\Services\ClassAssessmentModeService;
use App\Services\ResultLockService;
use App\Services\TeacherPerformanceSyncService;
use App\Modules\Exams\Enums\ExamType;
use App\Notifications\MarkEntryModifiedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Illuminate\Validation\ValidationException;

class TeacherMarkAuditService
{
    public function __construct(
        private readonly AssessmentMarkingModeService $markingModeService,
        private readonly ClassAssessmentModeService $assessmentModeService,
        private readonly TeacherPerformanceSyncService $teacherPerformanceSyncService,
        private readonly ResultLockService $resultLockService
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

    /**
     * @return array{can_edit:bool,is_locked:bool,lock_type:?string,message:?string}
     */
    public function lockStateForMark(Mark $mark, ?User $user = null): array
    {
        $mark->loadMissing('exam:id,class_id');

        $examId = $mark->exam_id ? (int) $mark->exam_id : null;
        $classId = (int) ($mark->exam?->class_id ?? 0);
        $session = (string) $mark->session;
        $status = $classId > 0 && $session !== ''
            ? $this->resultLockService->statusForScope($session, $classId, $examId)
            : [
                'is_locked' => false,
                'lock_type' => null,
                'message' => null,
            ];

        $canEditByWindow = $this->canEdit($mark);
        $canEditByLock = true;
        if ($user instanceof User && $classId > 0 && $session !== '') {
            $canEditByLock = $this->resultLockService->canEditResult($user, $session, $classId, $examId);
        } elseif (($status['is_locked'] ?? false) === true) {
            $canEditByLock = false;
        }

        $message = $status['message'] ?? null;
        if (! $message && ! $canEditByWindow) {
            $message = 'Editing window has expired. You can edit entries only within 7 days of entry.';
        }

        return [
            'can_edit' => $canEditByWindow && $canEditByLock,
            'is_locked' => (bool) ($status['is_locked'] ?? false),
            'lock_type' => $status['lock_type'] ?? null,
            'message' => $message,
        ];
    }

    public function updateMarkEntry(int $userId, Mark $mark, ?int $newMarks, ?string $newGrade, string $reason): void
    {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->assertOwnership($teacher, $mark);
        $actor = User::query()->findOrFail($userId);

        $lockState = $this->lockStateForMark($mark, $actor);
        if (! $lockState['can_edit']) {
            throw ValidationException::withMessages([
                'error' => $lockState['message'] ?? 'Results are locked and cannot be modified.',
            ]);
        }

        $mark->loadMissing([
            'teacher.user:id,name',
            'student:id,name',
            'exam:id,class_id,subject_id,exam_type,total_marks,marking_mode',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        $markingMode = $this->markingModeService->resolveMarkingMode(
            $mark->exam,
            $mark->exam?->classRoom ?? $mark->exam?->class_id
        );
        $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;
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

                TeacherResultEntryLog::query()->create([
                    'teacher_id' => (int) $mark->teacher_id,
                    'student_id' => (int) $mark->student_id,
                    'class_id' => (int) ($mark->exam?->class_id ?? 0),
                    'subject_id' => (int) ($mark->exam?->subject_id ?? 0),
                    'session' => (string) $mark->session,
                    'exam_type' => (string) $this->examTypeValue($mark),
                    'old_marks' => $oldMarks,
                    'new_marks' => null,
                    'old_grade' => $oldGrade,
                    'new_grade' => $normalizedGrade,
                    'action_type' => 'updated',
                    'action_at' => $editedAt,
                    'acted_by' => $userId,
                    'remarks' => $reason,
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

            TeacherResultEntryLog::query()->create([
                'teacher_id' => (int) $mark->teacher_id,
                'student_id' => (int) $mark->student_id,
                'class_id' => (int) ($mark->exam?->class_id ?? 0),
                'subject_id' => (int) ($mark->exam?->subject_id ?? 0),
                'session' => (string) $mark->session,
                'exam_type' => (string) $this->examTypeValue($mark),
                'old_marks' => $oldMarks,
                'new_marks' => $newMarks,
                'old_grade' => null,
                'new_grade' => null,
                'action_type' => 'updated',
                'action_at' => $editedAt,
                'acted_by' => $userId,
                'remarks' => $reason,
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
        $actor = User::query()->findOrFail($userId);
        $lockState = $this->lockStateForMark($mark, $actor);
        if (! $lockState['can_edit']) {
            throw ValidationException::withMessages([
                'error' => $lockState['message'] ?? 'Results are locked and cannot be modified.',
            ]);
        }

        $mark->loadMissing([
            'teacher.user:id,name',
            'student:id,name',
            'exam:id,class_id,subject_id,exam_type,total_marks,marking_mode',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        $markingMode = $this->markingModeService->resolveMarkingMode(
            $mark->exam,
            $mark->exam?->classRoom ?? $mark->exam?->class_id
        );
        $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;
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

            TeacherResultEntryLog::query()->create([
                'teacher_id' => (int) $mark->teacher_id,
                'student_id' => (int) $mark->student_id,
                'class_id' => (int) ($mark->exam?->class_id ?? 0),
                'subject_id' => (int) ($mark->exam?->subject_id ?? 0),
                'session' => (string) $mark->session,
                'exam_type' => (string) $this->examTypeValue($mark),
                'old_marks' => $oldMarks,
                'new_marks' => null,
                'old_grade' => $oldGrade,
                'new_grade' => null,
                'action_type' => 'deleted',
                'action_at' => $editedAt,
                'acted_by' => $userId,
                'remarks' => $reason,
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
            'exam:id,class_id,subject_id,exam_type,total_marks,marking_mode',
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
