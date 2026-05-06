<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kcat\StoreKcatAssignmentRequest;
use App\Models\KcatAssignment;
use App\Models\KcatTest;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\CareerCounselorService;
use App\Services\Kcat\KcatAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KcatAssignmentController extends Controller
{
    public function __construct(
        private readonly KcatAssignmentService $assignmentService,
        private readonly CareerCounselorService $careerCounselorService
    ) {}

    public function index(Request $request): View
    {
        return view('career-counselor.kcat.assignments.index', ['assignments' => $this->assignmentService->getAssignments($request->only(['session', 'status']))]);
    }

    public function create(): View
    {
        return view('career-counselor.kcat.assignments.create', [
            'tests' => KcatTest::query()
                ->where('status', 'active')
                ->whereHas('questions', fn ($query) => $query->where('is_active', true)->whereNull('retired_at'))
                ->withCount([
                    'questions as active_questions_count' => fn ($query) => $query->where('is_active', true)->whereNull('retired_at'),
                ])
                ->orderBy('title')
                ->get(),
            'classes' => SchoolClass::query()->orderBy('name')->orderBy('section')->get(),
        ]);
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->careerCounselorService->searchStudents((string) ($validated['term'] ?? $validated['q'] ?? '')),
        ]);
    }

    public function store(StoreKcatAssignmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $test = KcatTest::query()->findOrFail((int) $data['kcat_test_id']);
        $hasActiveQuestions = $test->questions()
            ->where('is_active', true)
            ->whereNull('retired_at')
            ->exists();

        if (! $hasActiveQuestions) {
            return back()
                ->withInput()
                ->with('error', 'Selected KCAT test has no active questions. Add questions first, then assign.');
        }

        if ($data['assigned_to_type'] === 'student') {
            $student = Student::query()->with('classRoom:id,name,section')->findOrFail((int) $data['student_id']);
            if (! $this->isGradeSevenToTwelve($student)) {
                return back()
                    ->withInput()
                    ->with('error', 'KCAT can only be assigned to Grade 7 to Grade 12 students.');
            }

            $assignment = $this->assignmentService->assignToStudent($test, $student, $request->user(), $data);
        } else {
            $assignment = $this->assignmentService->assignToClass($test, SchoolClass::query()->findOrFail((int) $data['class_id']), $request->user(), $data);
        }

        return redirect()->route('career-counselor.kcat.assignments.show', $assignment)->with('success', 'KCAT assigned.');
    }

    public function show(KcatAssignment $assignment): View
    {
        return view('career-counselor.kcat.assignments.index', ['assignments' => KcatAssignment::query()->whereKey($assignment->id)->with(['test', 'student.classRoom', 'classRoom', 'assignedBy'])->paginate(1)]);
    }

    private function isGradeSevenToTwelve(Student $student): bool
    {
        $classLabel = trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? ''));

        return (bool) preg_match('/(^|[^0-9])0?(7|8|9|10|11|12)([^0-9]|$)/i', $classLabel);
    }
}
