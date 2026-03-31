<?php

namespace App\Modules\Assessments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CognitiveAssessment;
use App\Models\CognitiveAssessmentAttempt;
use App\Models\Student;
use App\Modules\Assessments\Requests\EnableStudentCognitiveAssessmentRequest;
use App\Modules\Assessments\Requests\FilterStudentCognitiveAssessmentAccessRequest;
use App\Modules\Assessments\Requests\ResetStudentCognitiveAssessmentRequest;
use App\Services\CognitiveAssessmentReportService;
use App\Services\CognitiveAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class PrincipalCognitiveAssessmentAccessController extends Controller
{
    public function __construct(
        private readonly CognitiveAssessmentService $service,
        private readonly CognitiveAssessmentReportService $reportService
    ) {
    }

    public function index(FilterStudentCognitiveAssessmentAccessRequest $request): View
    {
        return view('principal.assessments.cognitive-skills-level-4.students.index', $this->service->buildStudentAccessManagement(
            $request->validated()
        ));
    }

    public function enableStudent(
        EnableStudentCognitiveAssessmentRequest $request,
        CognitiveAssessment $assessment,
        Student $student
    ): RedirectResponse {
        $this->ensureLevelFourAssessment($assessment);

        try {
            $this->service->enableAssessmentForStudent(
                (int) $assessment->id,
                (int) $student->id,
                (int) $request->user()->id,
                $request->validated('principal_note')
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['assessment_access' => $exception->getMessage()]);
        }

        return back()->with('status', 'Cognitive Skills Assessment Test Level 4 has been enabled for '.$student->name.'.');
    }

    public function disableStudent(
        EnableStudentCognitiveAssessmentRequest $request,
        CognitiveAssessment $assessment,
        Student $student
    ): RedirectResponse {
        $this->ensureLevelFourAssessment($assessment);

        $this->service->disableAssessmentForStudent(
            (int) $assessment->id,
            (int) $student->id,
            (int) $request->user()->id,
            $request->validated('principal_note')
        );

        return back()->with('status', 'Cognitive Skills Assessment Test Level 4 has been disabled for '.$student->name.'.');
    }

    public function resetStudent(
        ResetStudentCognitiveAssessmentRequest $request,
        CognitiveAssessment $assessment,
        Student $student
    ): RedirectResponse {
        $this->ensureLevelFourAssessment($assessment);

        try {
            $this->service->resetStudentAssessment(
                (int) $assessment->id,
                (int) $student->id,
                (int) $request->user()->id,
                $request->validated('reason')
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['assessment_access' => $exception->getMessage()]);
        }

        return back()->with('status', 'The latest assessment attempt for '.$student->name.' has been reset and the retake remains enabled.');
    }

    public function report(CognitiveAssessmentAttempt $attempt): View
    {
        $this->ensureAttemptMatchesLevelFour($attempt);

        return view('principal.assessments.cognitive-skills-level-4.reports.show', [
            'attempt' => $attempt->fresh(['assessment', 'student.classRoom']) ?? $attempt,
            'profileReport' => $this->reportService->buildStudentProfileReport((int) $attempt->id),
            'backRouteName' => 'principal.assessments.cognitive-skills-level-4.students.index',
            'panelTitle' => 'Principal',
        ]);
    }

    private function ensureLevelFourAssessment(CognitiveAssessment $assessment): void
    {
        $levelFourAssessment = $this->service->resolveAssessment();
        if ((int) $assessment->id !== (int) $levelFourAssessment->id) {
            abort(404);
        }
    }

    private function ensureAttemptMatchesLevelFour(CognitiveAssessmentAttempt $attempt): void
    {
        $assessment = $this->service->resolveAssessment();
        if ((int) $attempt->assessment_id !== (int) $assessment->id) {
            abort(404);
        }
    }
}
