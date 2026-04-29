<?php

namespace App\Http\Controllers\CareerCounselor;

use App\Http\Controllers\Controller;
use App\Models\CareerAssessment;
use App\Models\Student;
use App\Services\CareerAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerAssessmentController extends Controller
{
    public function __construct(private readonly CareerAssessmentService $careerAssessmentService) {}

    public function index(Request $request): View
    {
        return view('career-counselor.assessments.index', [
            'assessments' => CareerAssessment::query()
                ->with(['student.classRoom', 'counselor'])
                ->where('counselor_id', $request->user()->id)
                ->latest('assessment_date')
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        return view('career-counselor.assessments.form', [
            'student' => $request->integer('student_id') ? Student::query()->with('classRoom')->find($request->integer('student_id')) : null,
            'categories' => CareerAssessmentService::CATEGORIES,
            'assessment' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAssessment($request);
        $student = Student::query()->with('classRoom')->findOrFail((int) $validated['student_id']);
        $assessment = $this->careerAssessmentService->createAssessment($student, $validated, $request->user());

        return redirect()->route('career-counselor.assessments.show', $assessment)->with('success', 'Career assessment saved.');
    }

    public function show(CareerAssessment $assessment): View
    {
        return view('career-counselor.assessments.show', ['assessment' => $assessment->load(['student.classRoom', 'counselor', 'scores'])]);
    }

    public function print(CareerAssessment $assessment): View
    {
        return view('career-counselor.assessments.print', ['assessment' => $assessment->load(['student.classRoom', 'counselor', 'scores'])]);
    }

    private function validateAssessment(Request $request): array
    {
        return $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'assessment_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'overall_summary' => ['nullable', 'string'],
            'recommended_stream' => ['nullable', 'string', 'max:100'],
            'alternative_stream' => ['nullable', 'string', 'max:100'],
            'suggested_subjects' => ['nullable', 'string'],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string'],
        ]);
    }
}
