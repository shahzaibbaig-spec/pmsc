<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\TeacherRankingRequest;
use App\Services\TeacherRankingService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Illuminate\View\View;

class TeacherRankingController extends Controller
{
    public function __construct(private readonly TeacherRankingService $rankingService)
    {
    }

    public function index(TeacherRankingRequest $request): View
    {
        $validated = $request->validated();
        $session = $this->rankingService->resolveSession(isset($validated['session']) ? (string) $validated['session'] : null);
        $selectedExamType = $this->rankingService->examTypeSelectionValue(
            isset($validated['exam_type']) ? (string) $validated['exam_type'] : 'overall'
        );
        $snapshot = $this->rankingService->snapshot($session, $selectedExamType);

        return view('principal.analytics.teacher-rankings.index', [
            'sessions' => $this->rankingService->sessionOptions(),
            'examTypes' => $this->rankingService->examTypeOptions(),
            'selectedSession' => $session,
            'selectedExamType' => $selectedExamType,
            'selectedExamLabel' => $this->rankingService->examTypeLabel($selectedExamType),
            'overallRankings' => $snapshot['overall'],
            'classwiseRankings' => $snapshot['classwise'],
            'summary' => $snapshot['summary'],
            'schemaReady' => (bool) ($snapshot['schema_ready'] ?? true),
            'schemaMessage' => $snapshot['schema_message'] ?? null,
            'previewMode' => (bool) ($snapshot['preview_mode'] ?? false),
            'dataSource' => (string) ($snapshot['data_source'] ?? 'snapshot'),
        ]);
    }

    public function regenerate(TeacherRankingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $session = $this->rankingService->resolveSession(isset($validated['session']) ? (string) $validated['session'] : null);
        $selectedExamType = $this->rankingService->examTypeSelectionValue(
            isset($validated['exam_type']) ? (string) $validated['exam_type'] : 'overall'
        );

        try {
            $this->rankingService->storeTeacherCgpaRankings($session, $selectedExamType);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('principal.analytics.teacher-rankings.index', [
                    'session' => $session,
                    'exam_type' => $selectedExamType,
                ])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.analytics.teacher-rankings.index', [
                'session' => $session,
                'exam_type' => $selectedExamType,
            ])
            ->with(
                'success',
                'Teacher CGPA rankings regenerated for '.$session.' ('.$this->rankingService->examTypeLabel($selectedExamType).').'
            );
    }
}
