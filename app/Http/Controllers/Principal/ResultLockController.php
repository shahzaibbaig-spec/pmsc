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
        $subjectId = $filters['subject_id'] ?? null;
        $exams = $this->resultLockService->examOptions($session, $classId, $subjectId);
        if ($examId !== null && ! $exams->contains('id', $examId)) {
            $examId = null;
        }
        $recentUnlockIds = \App\Models\ResultLockLog::where('action', 'unlock')->whereNotNull('exam_id')
            ->where('session', $session)
            ->when($classId !== null, fn ($query) => $query->where('class_id', $classId))
            ->when($subjectId !== null, fn ($query) => $query->whereHas('exam', fn ($exam) => $exam->where('subject_id', $subjectId)))
            ->selectRaw('MAX(id)')->groupBy('exam_id');
        $recentUnlocks = \App\Models\ResultLockLog::with(['exam.subject', 'exam.classRoom', 'performer'])
            ->whereIn('id', $recentUnlockIds)->orderByDesc('created_at')->orderByDesc('id')->limit(25)->get()
            ->filter(fn ($log) => $log->exam !== null)
            ->map(fn ($log) => ['log' => $log, 'status' => $this->resultLockService->statusForScope($log->session, $log->class_id, $log->exam_id)]);

        $status = null;
        if ($classId !== null && ($subjectId === null || $examId !== null)) {
            $status = $this->resultLockService->statusForScope($session, $classId, $examId);
        }

        return view('principal.result-locks.index', [
            'recentUnlocks' => $recentUnlocks,
            'subjects' => \App\Models\Subject::whereIn('id', \App\Models\Exam::where('session', $session)
                ->when($classId !== null, fn ($query) => $query->where('class_id', $classId))->select('subject_id'))->orderBy('name')->get(['id', 'name']),
            'correctionExam' => $examId !== null ? \App\Models\Exam::whereKey($examId)->where('class_id', $classId)->where('session', $session)->first() : null,
            'filters' => [
                'session' => $session,
                'class_id' => $classId,
                'exam_id' => $examId,
                'subject_id' => $subjectId,
            ],
            'sessions' => $this->resultLockService->sessionOptions(),
            'classes' => $this->resultLockService->classOptions(),
            'exams' => $exams,
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
                'subject_id' => $request->filled('subject_id') ? (int) $request->input('subject_id') : null,
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
                'subject_id' => $request->filled('subject_id') ? (int) $request->input('subject_id') : null,
            ])
            ->with('status', 'Result scope unlocked successfully. Audit log recorded.');
    }

    public function openCorrections(ResultUnlockRequest $request, \App\Services\ExamCorrectionService $service): RedirectResponse
    {
        $request->validate(['exam_id' => ['required', 'integer', 'exists:exams,id']]);
        $service->open($request->user(), $request->input('session'), (int) $request->input('class_id'),
            (int) $request->input('exam_id'), $request->input('reason'),
            $request->filled('correction_minutes') ? (int) $request->input('correction_minutes') : null);
        return redirect()->route('principal.result-locks.index', $request->only(['session', 'class_id', 'exam_id', 'subject_id']))
            ->with('status', 'Exam reopened for corrections. The timer, if set, is enforced automatically by the server.');
    }

    public function closeCorrections(ResultUnlockRequest $request, \App\Services\ExamCorrectionService $service): RedirectResponse
    {
        $request->validate(['exam_id' => ['required', 'integer', 'exists:exams,id']]);
        $service->close($request->user(), $request->input('session'), (int) $request->input('class_id'),
            (int) $request->input('exam_id'), $request->input('reason'));
        return redirect()->route('principal.result-locks.index', $request->only(['session', 'class_id', 'exam_id', 'subject_id']))
            ->with('status', 'Exam locked again. Correction access has ended.');
    }
}
