<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ResultLock;
use App\Models\ResultLockLog;
use App\Models\SchoolClass;
use App\Models\StudentResult;
use App\Models\User;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultLockService
{
    public function __construct(private readonly DailyDiaryService $dailyDiaryService)
    {
    }

    /**
     * @return array<int, string>
     */
    public function sessionOptions(): array
    {
        return $this->dailyDiaryService->sessionOptions();
    }

    public function resolveSession(?string $requestedSession): string
    {
        $session = trim((string) $requestedSession);
        $options = $this->sessionOptions();

        if ($session !== '' && in_array($session, $options, true)) {
            return $session;
        }

        return $options[0] ?? $this->dailyDiaryService->resolveSession(null);
    }

    public function lockResults(string $session, int $classId, ?int $examId, string $type, int $userId, ?string $reason = null): ResultLock
    {
        $this->assertValidLockType($type);

        return DB::transaction(function () use ($session, $classId, $examId, $type, $userId, $reason): ResultLock {
            $this->ensureExamBelongsToClass($classId, $examId);

            $lock = ResultLock::query()
                ->where('session', $session)
                ->where('class_id', $classId)
                ->where('lock_type', $type)
                ->where(function ($query) use ($examId): void {
                    if ($examId === null) {
                        $query->whereNull('exam_id');
                    } else {
                        $query->where('exam_id', $examId);
                    }
                })
                ->lockForUpdate()
                ->first();

            $now = now();
            if (! $lock) {
                $lock = ResultLock::query()->create([
                    'session' => $session,
                    'class_id' => $classId,
                    'exam_id' => $examId,
                    'lock_type' => $type,
                    'locked_by' => $userId,
                    'locked_at' => $now,
                    'unlocked_at' => null,
                    'unlocked_by' => null,
                    'reason' => $reason,
                ]);
            } else {
                $lock->forceFill([
                    'locked_by' => $userId,
                    'locked_at' => $now,
                    'unlocked_at' => null,
                    'unlocked_by' => null,
                    'reason' => $reason,
                ])->save();
            }

            if ($type === ResultLock::TYPE_FINAL) {
                $this->setStudentResultLockedFlag($session, $classId, $examId, true);
            }

            $this->storeLog('lock', $type, $session, $classId, $examId, $userId, $reason);

            return $lock->fresh(['classRoom:id,name,section', 'exam.subject:id,name', 'locker:id,name']);
        });
    }

    public function unlockResults(string $session, int $classId, ?int $examId, int $userId, string $reason): void
    {
        $user = User::query()->findOrFail($userId);
        if (! $user->hasAnyRole(['Admin', 'Principal'])) {
            throw ValidationException::withMessages([
                'error' => 'Only Principal or Admin can unlock results.',
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Unlock reason is required.',
            ]);
        }

        DB::transaction(function () use ($session, $classId, $examId, $userId, $reason): void {
            $this->ensureExamBelongsToClass($classId, $examId);

            $locks = ResultLock::query()
                ->where('session', $session)
                ->where('class_id', $classId)
                ->whereNull('unlocked_at')
                ->where(function ($query) use ($examId): void {
                    if ($examId === null) {
                        $query->whereNull('exam_id');
                    } else {
                        $query->where('exam_id', $examId);
                    }
                })
                ->lockForUpdate()
                ->get();

            if ($locks->isEmpty()) {
                throw ValidationException::withMessages([
                    'error' => 'No active lock was found for the selected result scope.',
                ]);
            }

            foreach ($locks as $lock) {
                $lock->forceFill([
                    'unlocked_at' => now(),
                    'unlocked_by' => $userId,
                    'reason' => $reason,
                ])->save();

                $this->storeLog(
                    'unlock',
                    (string) $lock->lock_type,
                    $session,
                    $classId,
                    $examId,
                    $userId,
                    $reason
                );
            }

            $this->setStudentResultLockedFlag($session, $classId, $examId, false);
            $this->reapplyStudentResultFinalLocks($session, $classId, $examId);
        });
    }

    public function isLocked(string $session, int $classId, ?int $examId): bool
    {
        return $this->activeLockForScope($session, $classId, $examId) !== null;
    }

    public function canEditResult(User $user, string $session, int $classId, ?int $examId): bool
    {
        $lock = $this->activeLockForScope($session, $classId, $examId);
        if (! $lock) {
            return $user->hasAnyRole(['Admin', 'Principal', 'Teacher']);
        }

        if ($lock->lock_type === ResultLock::TYPE_FINAL) {
            return false;
        }

        return $user->hasAnyRole(['Admin', 'Principal']);
    }

    /**
     * @return array{
     *   is_locked:bool,
     *   lock_type:?string,
     *   lock_level:string,
     *   message:?string,
     *   lock:ResultLock|null
     * }
     */
    public function statusForScope(string $session, int $classId, ?int $examId): array
    {
        $lock = $this->activeLockForScope($session, $classId, $examId);
        if (! $lock) {
            return [
                'is_locked' => false,
                'lock_type' => null,
                'lock_level' => 'none',
                'message' => null,
                'lock' => null,
            ];
        }

        $examScoped = $examId !== null && (int) $lock->exam_id === $examId;
        $message = $lock->lock_type === ResultLock::TYPE_FINAL
            ? 'Results have been finalized and cannot be edited.'
            : 'Results are under review and are view-only for teachers.';

        return [
            'is_locked' => true,
            'lock_type' => (string) $lock->lock_type,
            'lock_level' => $examScoped ? 'exam' : 'class',
            'message' => $message,
            'lock' => $lock,
        ];
    }

    public function activeLockForScope(string $session, int $classId, ?int $examId): ?ResultLock
    {
        $baseQuery = ResultLock::query()
            ->with([
                'classRoom:id,name,section',
                'exam:id,class_id,subject_id,exam_type,session',
                'exam.subject:id,name',
                'locker:id,name',
            ])
            ->where('session', $session)
            ->where('class_id', $classId)
            ->whereNull('unlocked_at');

        if ($examId !== null) {
            $exactFinal = (clone $baseQuery)
                ->where('exam_id', $examId)
                ->where('lock_type', ResultLock::TYPE_FINAL)
                ->latest('locked_at')
                ->first();
            if ($exactFinal) {
                return $exactFinal;
            }
        }

        $classFinal = (clone $baseQuery)
            ->whereNull('exam_id')
            ->where('lock_type', ResultLock::TYPE_FINAL)
            ->latest('locked_at')
            ->first();
        if ($classFinal) {
            return $classFinal;
        }

        if ($examId !== null) {
            $exactSoft = (clone $baseQuery)
                ->where('exam_id', $examId)
                ->where('lock_type', ResultLock::TYPE_SOFT)
                ->latest('locked_at')
                ->first();
            if ($exactSoft) {
                return $exactSoft;
            }
        }

        return (clone $baseQuery)
            ->whereNull('exam_id')
            ->where('lock_type', ResultLock::TYPE_SOFT)
            ->latest('locked_at')
            ->first();
    }

    /**
     * @return EloquentCollection<int, ResultLock>
     */
    public function activeLocks(array $filters = []): EloquentCollection
    {
        return ResultLock::query()
            ->with([
                'classRoom:id,name,section',
                'exam:id,class_id,subject_id,exam_type,session',
                'exam.subject:id,name',
                'locker:id,name',
            ])
            ->whereNull('unlocked_at')
            ->when(($filters['session'] ?? null) !== null, fn ($query) => $query->where('session', (string) $filters['session']))
            ->when(($filters['class_id'] ?? null) !== null, fn ($query) => $query->where('class_id', (int) $filters['class_id']))
            ->when(($filters['exam_id'] ?? null) !== null, fn ($query) => $query->where('exam_id', (int) $filters['exam_id']))
            ->orderByDesc('locked_at')
            ->get();
    }

    /**
     * @return EloquentCollection<int, ResultLockLog>
     */
    public function recentLogs(array $filters = [], int $limit = 20): EloquentCollection
    {
        return ResultLockLog::query()
            ->with([
                'classRoom:id,name,section',
                'exam:id,class_id,subject_id,exam_type,session',
                'exam.subject:id,name',
                'performer:id,name',
            ])
            ->when(($filters['session'] ?? null) !== null, fn ($query) => $query->where('session', (string) $filters['session']))
            ->when(($filters['class_id'] ?? null) !== null, fn ($query) => $query->where('class_id', (int) $filters['class_id']))
            ->when(($filters['exam_id'] ?? null) !== null, fn ($query) => $query->where('exam_id', (int) $filters['exam_id']))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return EloquentCollection<int, Exam>
     */
    public function examOptions(string $session, ?int $classId = null): EloquentCollection
    {
        return Exam::query()
            ->with(['subject:id,name'])
            ->where('session', $session)
            ->when($classId !== null, fn ($query) => $query->where('class_id', $classId))
            ->orderBy('exam_type')
            ->orderBy('subject_id')
            ->get(['id', 'class_id', 'subject_id', 'exam_type', 'session']);
    }

    /**
     * @return EloquentCollection<int, SchoolClass>
     */
    public function classOptions(): EloquentCollection
    {
        return SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);
    }

    public function examLabel(?Exam $exam): string
    {
        if (! $exam) {
            return 'Whole class result scope';
        }

        $examType = $exam->exam_type instanceof ExamType
            ? $exam->exam_type->label()
            : str_replace('_', ' ', ucfirst((string) $exam->exam_type));

        return trim(($exam->subject?->name ?? 'Subject').' | '.$examType);
    }

    private function assertValidLockType(string $type): void
    {
        if (! in_array($type, [ResultLock::TYPE_SOFT, ResultLock::TYPE_FINAL], true)) {
            throw ValidationException::withMessages([
                'lock_type' => 'Invalid lock type selected.',
            ]);
        }
    }

    private function ensureExamBelongsToClass(int $classId, ?int $examId): void
    {
        if ($examId === null) {
            return;
        }

        $belongsToClass = Exam::query()
            ->where('id', $examId)
            ->where('class_id', $classId)
            ->exists();

        if (! $belongsToClass) {
            throw ValidationException::withMessages([
                'exam_id' => 'Selected exam does not belong to the chosen class.',
            ]);
        }
    }

    private function storeLog(
        string $action,
        string $lockType,
        string $session,
        int $classId,
        ?int $examId,
        int $performedBy,
        ?string $reason
    ): void {
        ResultLockLog::query()->create([
            'action' => $action,
            'lock_type' => $lockType,
            'session' => $session,
            'class_id' => $classId,
            'exam_id' => $examId,
            'performed_by' => $performedBy,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function setStudentResultLockedFlag(string $session, int $classId, ?int $examId, bool $locked): void
    {
        if (! \Schema::hasColumn('student_results', 'is_locked')) {
            return;
        }

        StudentResult::query()
            ->where('session', $session)
            ->where('class_id', $classId)
            ->when($examId !== null, fn ($query) => $query->where('exam_id', $examId))
            ->update(['is_locked' => $locked]);
    }

    private function reapplyStudentResultFinalLocks(string $session, int $classId, ?int $examId): void
    {
        $activeFinalLocks = ResultLock::query()
            ->where('session', $session)
            ->where('class_id', $classId)
            ->where('lock_type', ResultLock::TYPE_FINAL)
            ->whereNull('unlocked_at')
            ->when($examId !== null, function ($query) use ($examId): void {
                $query->where(function ($nested) use ($examId): void {
                    $nested->whereNull('exam_id')
                        ->orWhere('exam_id', $examId);
                });
            })
            ->get(['exam_id']);

        if ($activeFinalLocks->isEmpty()) {
            return;
        }

        foreach ($activeFinalLocks as $lock) {
            $this->setStudentResultLockedFlag(
                $session,
                $classId,
                $lock->exam_id !== null ? (int) $lock->exam_id : null,
                true
            );
        }
    }
}
