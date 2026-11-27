<?php

namespace App\Services;

use App\Models\{Exam, ResultLock, ResultLockLog, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ExamCorrectionService
{
    public function __construct(private readonly ResultLockService $locks) {}

    private function selectedExam(User $user, string $session, int $classId, int $examId): Exam
    {
        abort_unless($user->hasAnyRole(['Admin', 'Principal']) && $user->can('generate_results'), 403);
        if (! Schema::hasColumn('exams', 'corrections_opened_at')) {
            throw ValidationException::withMessages(['exam_id' => 'The exam correction window database migration must be run before using this feature.']);
        }
        $exam = Exam::whereKey($examId)->where('class_id', $classId)->where('session', $session)->lockForUpdate()->first();
        if (! $exam) {
            throw ValidationException::withMessages(['exam_id' => 'Select an exam belonging to this class and session.']);
        }
        return $exam;
    }

    public function open(User $user, string $session, int $classId, int $examId, string $reason, ?int $minutes): void
    {
        if (trim($reason) === '' || ($minutes !== null && ($minutes < 1 || $minutes > 43200))) {
            throw ValidationException::withMessages(['reason' => 'Provide a reason and a valid correction duration (1–43200 minutes), or leave the duration empty.']);
        }
        DB::transaction(function () use ($user, $session, $classId, $examId, $reason, $minutes) {
            $exam = $this->selectedExam($user, $session, $classId, $examId);
            // Serialize lock changes with the existing result-lock operations.
            $active = ResultLock::where('session', $session)->where('class_id', $classId)
                ->whereNull('unlocked_at')->lockForUpdate()->get();
            if ($active->contains(fn ($lock) => $lock->exam_id === null)) {
                throw ValidationException::withMessages(['exam_id' => 'This class has an active class-wide lock. Unlock the whole-class scope first, then reopen this exam.']);
            }
            if ($active->contains(fn ($lock) => (int) $lock->exam_id === $examId)) {
                $this->locks->unlockResults($session, $classId, $examId, $user->id, $reason);
            }
            $deadline = $minutes !== null ? now()->addMinutes($minutes) : null;
            $exam->forceFill(['corrections_opened_at' => now(), 'corrections_close_at' => $deadline,
                'corrections_opened_by' => $user->id])->save();
            ResultLockLog::create(['action' => 'unlock', 'lock_type' => 'soft', 'session' => $session,
                'class_id' => $classId, 'exam_id' => $examId, 'performed_by' => $user->id,
                'reason' => '[Correction window '.($deadline ? 'until '.$deadline->toIso8601String() : 'until manually locked').'] '.$reason,
                'created_at' => now()]);
        });
    }

    public function close(User $user, string $session, int $classId, int $examId, string $reason): void
    {
        DB::transaction(function () use ($user, $session, $classId, $examId, $reason) {
            $this->selectedExam($user, $session, $classId, $examId);
            $this->locks->lockResults($session, $classId, $examId, ResultLock::TYPE_FINAL, $user->id, $reason);
        });
    }
}
