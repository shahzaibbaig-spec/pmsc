<?php

namespace App\Modules\Assessments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CognitiveAssessmentAttempt;
use App\Modules\Assessments\Requests\FilterCognitiveAssessmentReportRequest;
use App\Services\CognitiveAssessmentReportService;
use App\Services\CognitiveAssessmentService;
use Illuminate\View\View;

class PrincipalCognitiveAssessmentReportController extends Controller
{
    public function __construct(
        private readonly CognitiveAssessmentService $service,
        private readonly CognitiveAssessmentReportService $reportService
    ) {
    }

    public function index(FilterCognitiveAssessmentReportRequest $request): View
    {
        return view('principal.assessments.cognitive-skills-level-4-reports.index', $this->reportIndexViewData(
            $request->validated()
        ));
    }

    public function show(CognitiveAssessmentAttempt $attempt): View
    {
        $attempt = $this->prepareAttempt($attempt);

        return view('principal.assessments.cognitive-skills-level-4.reports.show', [
            'attempt' => $attempt,
            'profileReport' => $this->reportService->buildStudentProfileReport((int) $attempt->id),
            'backRouteName' => 'principal.assessments.cognitive-skills-level-4-reports.index',
            'panelTitle' => 'Principal',
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function reportIndexViewData(array $filters): array
    {
        return $this->reportService->buildPrincipalSummary($filters);
    }

    private function prepareAttempt(CognitiveAssessmentAttempt $attempt): CognitiveAssessmentAttempt
    {
        $assessment = $this->service->resolveAssessment();
        if ((int) $attempt->assessment_id !== (int) $assessment->id) {
            abort(404);
        }

        if ($attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS && $attempt->isExpired()) {
            $attempt = $this->service->submitAttempt($attempt, true);
        }

        return $attempt->fresh(['student.classRoom']) ?? $attempt;
    }
}
