<?php

namespace App\Modules\Assessments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CognitiveAssessmentAttempt;
use App\Modules\Assessments\Requests\FilterCognitiveAssessmentReportRequest;
use App\Services\CognitiveAssessmentReportService;
use App\Services\CognitiveAssessmentService;
use Illuminate\View\View;

class AdminCognitiveAssessmentReportController extends Controller
{
    public function __construct(
        private readonly CognitiveAssessmentService $service,
        private readonly CognitiveAssessmentReportService $reportService
    ) {
    }

    public function index(FilterCognitiveAssessmentReportRequest $request): View
    {
        return view('admin.assessments.cognitive-skills-level-4-reports.index', $this->reportService->buildPrincipalSummary(
            $request->validated()
        ));
    }

    public function show(CognitiveAssessmentAttempt $attempt): View
    {
        $attempt = $this->prepareAttempt($attempt);

        return view('admin.assessments.cognitive-skills-level-4.reports.show', [
            'attempt' => $attempt,
            'profileReport' => $this->reportService->buildStudentProfileReport((int) $attempt->id),
            'backRouteName' => 'admin.assessments.cognitive-skills-level-4-reports.index',
            'panelTitle' => 'Admin',
        ]);
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
