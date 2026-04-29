<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\CareerCounselingSession;
use App\Services\CareerAssessmentService;
use Illuminate\View\View;

class CareerReportController extends Controller
{
    public function __construct(private readonly CareerAssessmentService $careerAssessmentService) {}

    public function index(): View
    {
        return view('principal.career-reports.index', [
            'gradeSummary' => $this->careerAssessmentService->getGradeWiseSummary(),
            'visibleRecommendations' => CareerCounselingSession::query()
                ->with(['student.classRoom', 'counselor'])
                ->where('visibility', '<>', 'private')
                ->latest('id')
                ->limit(50)
                ->get(),
        ]);
    }

    public function print(): View
    {
        return view('principal.career-reports.print', [
            'gradeSummary' => $this->careerAssessmentService->getGradeWiseSummary(),
            'visibleRecommendations' => CareerCounselingSession::query()
                ->with(['student.classRoom', 'counselor'])
                ->where('visibility', '<>', 'private')
                ->latest('id')
                ->limit(100)
                ->get(),
        ]);
    }
}
