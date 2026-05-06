<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kcat\StoreKcatAssignmentRequest;
use App\Models\KcatAssignment;
use App\Models\KcatTest;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Kcat\KcatAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KcatAssignmentController extends Controller
{
    public function __construct(private readonly KcatAssignmentService $assignmentService) {}

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

        $assignment = $data['assigned_to_type'] === 'student'
            ? $this->assignmentService->assignToStudent($test, Student::query()->findOrFail((int) $data['student_id']), $request->user(), $data)
            : $this->assignmentService->assignToClass($test, SchoolClass::query()->findOrFail((int) $data['class_id']), $request->user(), $data);

        return redirect()->route('career-counselor.kcat.assignments.show', $assignment)->with('success', 'KCAT assigned.');
    }

    public function show(KcatAssignment $assignment): View
    {
        return view('career-counselor.kcat.assignments.index', ['assignments' => KcatAssignment::query()->whereKey($assignment->id)->with(['test', 'student.classRoom', 'classRoom', 'assignedBy'])->paginate(1)]);
    }
}
