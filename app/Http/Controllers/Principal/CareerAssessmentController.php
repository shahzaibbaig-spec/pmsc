<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\CareerAssessment;
use Illuminate\View\View;

class CareerAssessmentController extends Controller
{
    public function index(): View
    {
        return view('principal.career-assessments.index', [
            'assessments' => CareerAssessment::query()->with(['student.classRoom', 'counselor'])->latest('assessment_date')->paginate(20),
        ]);
    }

    public function show(CareerAssessment $assessment): View
    {
        return view('career-counselor.assessments.show', ['assessment' => $assessment->load(['student.classRoom', 'counselor', 'scores'])]);
    }

    public function print(CareerAssessment $assessment): View
    {
        return view('career-counselor.assessments.print', ['assessment' => $assessment->load(['student.classRoom', 'counselor', 'scores'])]);
    }
}
