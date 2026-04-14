<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\TeacherAcrBulkPrintRequest;
use App\Http\Requests\Principal\GenerateTeacherAcrRequest;
use App\Http\Requests\Principal\TeacherAcrIndexRequest;
use App\Http\Requests\Principal\UpdateTeacherAcrRequest;
use App\Models\TeacherAcr;
use App\Services\TeacherAcrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;
use RuntimeException;

class TeacherAcrController extends Controller
{
    public function __construct(private readonly TeacherAcrService $acrService)
    {
    }

    public function index(TeacherAcrIndexRequest $request): View
    {
        $validated = $request->validated();
        $session = $this->acrService->resolveSession(isset($validated['session']) ? (string) $validated['session'] : null);
        $search = trim((string) ($validated['search'] ?? ''));
        $status = isset($validated['status']) ? (string) $validated['status'] : null;
        $perPage = (int) ($validated['per_page'] ?? 15);

        $acrs = TeacherAcr::query()
            ->with([
                'teacher:id,teacher_id,user_id,designation,employee_code',
                'teacher.user:id,name',
            ])
            ->where('session', $session)
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('teacher', function ($teacherQuery) use ($search): void {
                    $teacherQuery->where('teacher_id', 'like', '%'.$search.'%')
                        ->orWhere('employee_code', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderByRaw("
                CASE status
                    WHEN 'draft' THEN 1
                    WHEN 'reviewed' THEN 2
                    WHEN 'finalized' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc('total_score')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('principal.acr.index', [
            'acrs' => $acrs,
            'sessions' => $this->acrService->sessionOptions(),
            'selectedSession' => $session,
            'selectedStatus' => $status,
            'search' => $search,
            'teacherOptions' => $this->acrService->teacherOptionsForSession($session),
        ]);
    }

    public function generate(GenerateTeacherAcrRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $session = $this->acrService->resolveSession((string) $validated['session']);

        try {
            if (isset($validated['teacher_id']) && $validated['teacher_id'] !== null) {
                $result = $this->acrService->generateDraftAcr((int) $validated['teacher_id'], $session);

                $message = ($result['skipped_reason'] ?? null) === 'finalized'
                    ? 'The selected teacher ACR is already finalized and was left unchanged.'
                    : 'Teacher ACR draft prepared successfully for '.$result['teacher_name'].'.';
            } else {
                $summary = $this->acrService->generateDraftAcrsForSession($session);
                $message = sprintf(
                    'ACR draft generation complete for %s. Created: %d, Updated: %d, Finalized kept unchanged: %d.',
                    $session,
                    (int) ($summary['created'] ?? 0),
                    (int) ($summary['updated'] ?? 0),
                    (int) ($summary['skipped_finalized'] ?? 0)
                );
            }
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('principal.acr.index', ['session' => $session])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.acr.index', ['session' => $session])
            ->with('success', $message);
    }

    public function show(TeacherAcr $acr): View
    {
        return view('principal.acr.show', [
            'payload' => $this->acrService->buildPrintableAcr((int) $acr->id),
        ]);
    }

    public function update(UpdateTeacherAcrRequest $request, TeacherAcr $acr): RedirectResponse
    {
        try {
            $this->acrService->savePrincipalReview(
                (int) $acr->id,
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('principal.acr.show', $acr)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.acr.show', $acr)
            ->with('success', 'Principal review saved successfully.');
    }

    public function finalize(TeacherAcr $acr): RedirectResponse
    {
        try {
            $this->acrService->finalizeAcr((int) $acr->id, (int) auth()->id());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('principal.acr.show', $acr)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.acr.show', $acr)
            ->with('success', 'Teacher ACR finalized successfully.');
    }

    public function refresh(TeacherAcr $acr): RedirectResponse
    {
        try {
            $this->acrService->manualRefreshFinalizedAcr((int) $acr->id, (int) auth()->id());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('principal.acr.show', $acr)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.acr.show', $acr)
            ->with('success', 'ACR refreshed from latest academic results successfully.');
    }

    public function print(TeacherAcr $acr): View
    {
        return view('principal.acr.print', [
            'payload' => $this->acrService->buildPrintableAcr((int) $acr->id),
        ]);
    }

    public function bulkPrintForm(Request $request): View
    {
        $selectedSession = $this->acrService->resolveSession($request->query('session'));
        $selectedStatus = strtolower(trim((string) $request->query('status', 'all')));
        if (! in_array($selectedStatus, ['all', 'draft', 'reviewed', 'finalized'], true)) {
            $selectedStatus = 'all';
        }

        return view('principal.acr.bulk-print-form', [
            'sessions' => $this->acrService->sessionOptions(),
            'selectedSession' => $selectedSession,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function bulkPrint(TeacherAcrBulkPrintRequest $request): Response|RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->acrService->buildBulkPrintableAcrs(
            (string) $validated['session'],
            (string) ($validated['status'] ?? 'all')
        );

        if ((int) ($payload['total'] ?? 0) <= 0) {
            return redirect()
                ->route('principal.acr.bulk-print.form', [
                    'session' => $validated['session'],
                    'status' => $validated['status'] ?? 'all',
                ])
                ->with('error', 'No ACR records matched the selected session/status filter.');
        }

        $filename = sprintf(
            'teacher-acrs-%s-%s.pdf',
            (string) ($payload['session'] ?? 'session'),
            (string) ($payload['status'] ?? 'all')
        );

        return Pdf::loadView('principal.acr.bulk-print-pdf', [
            'payload' => $payload,
        ])
            ->setPaper('a4')
            ->download($filename);
    }
}
