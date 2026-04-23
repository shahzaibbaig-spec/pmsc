<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\ResultLockIndexRequest;
use App\Http\Requests\Principal\ResultLockStoreRequest;
use App\Http\Requests\Principal\ResultUnlockRequest;
use App\Services\ResultLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ResultLockController extends Controller
{
    public function __construct(private readonly ResultLockService $resultLockService)
    {
    }

    public function index(ResultLockIndexRequest $request): View
    {
        $filters = $request->validated();
        $session = $this->resultLockService->resolveSession($filters['session'] ?? null);
        $classId = isset($filters['class_id']) ? (int) $filters['class_id'] : null;
        $examId = isset($filters['exam_id']) ? (int) $filters['exam_id'] : null;

        $status = null;
        if ($classId !== null) {
            $status = $this->resultLockService->statusForScope($session, $classId, $examId);
        }

        return view('principal.result-locks.index', [
            'filters' => [
                'session' => $session,
                'class_id' => $classId,
                'exam_id' => $examId,
            ],
            'sessions' => $this->resultLockService->sessionOptions(),
            'classes' => $this->resultLockService->classOptions(),
            'exams' => $this->resultLockService->examOptions($session, $classId),
            'activeLocks' => $this->resultLockService->activeLocks([
                'session' => $session,
                'class_id' => $classId,
            ]),
            'recentLogs' => $this->resultLockService->recentLogs([
                'session' => $session,
                'class_id' => $classId,
            ]),
            'status' => $status,
            'examLabelResolver' => fn ($exam) => $this->resultLockService->examLabel($exam),
        ]);
    }

    public function lock(ResultLockStoreRequest $request): RedirectResponse
    {
        try {
            $lock = $this->resultLockService->lockResults(
                $request->string('session')->toString(),
                (int) $request->input('class_id'),
                $request->filled('exam_id') ? (int) $request->input('exam_id') : null,
                $request->string('lock_type')->toString(),
                (int) $request->user()->id,
                $request->input('reason')
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $message = $lock->lock_type === 'final'
            ? 'Final lock applied. Results are now permanently frozen until an explicit unlock is recorded.'
            : 'Soft lock applied. Teachers now have view-only access for this result scope.';

        return redirect()
            ->route('principal.result-locks.index', [
                'session' => $request->string('session')->toString(),
                'class_id' => (int) $request->input('class_id'),
                'exam_id' => $request->filled('exam_id') ? (int) $request->input('exam_id') : null,
            ])
            ->with('status', $message);
    }

    public function unlock(ResultUnlockRequest $request): RedirectResponse
    {
        $this->resultLockService->unlockResults(
            $request->string('session')->toString(),
            (int) $request->input('class_id'),
            $request->filled('exam_id') ? (int) $request->input('exam_id') : null,
            (int) $request->user()->id,
            $request->string('reason')->toString()
        );

        return redirect()
            ->route('principal.result-locks.index', [
                'session' => $request->string('session')->toString(),
                'class_id' => (int) $request->input('class_id'),
                'exam_id' => $request->filled('exam_id') ? (int) $request->input('exam_id') : null,
            ])
            ->with('status', 'Result scope unlocked successfully. Audit log recorded.');
    }
}
