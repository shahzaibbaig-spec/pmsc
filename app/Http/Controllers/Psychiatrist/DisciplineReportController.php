<?php

namespace App\Http\Controllers\Psychiatrist;

use App\Http\Controllers\Controller;
use App\Models\StudentDisciplineReport;
use App\Services\StudentDisciplineReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DisciplineReportController extends Controller
{
    public function __construct(private readonly StudentDisciplineReportService $disciplineReportService)
    {
    }

    public function index(Request $request): View
    {
        $validated = $this->validatedFilters($request);

        return view('psychiatrist.discipline-reports.index', $this->disciplineReportService->getPrincipalReports($validated));
    }

    public function show(StudentDisciplineReport $report): View
    {
        $report->load([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'subject:id,name',
            'teacher:id,name',
            'acknowledgedBy:id,name',
            'resolvedBy:id,name',
            'psychiatristReviewedBy:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return view('psychiatrist.discipline-reports.show', [
            'report' => $report,
        ]);
    }

    public function feedback(StudentDisciplineReport $report, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'psychiatrist_feedback' => ['nullable', 'string', 'max:2500'],
        ]);

        $this->disciplineReportService->updatePsychiatristFeedback($report, $validated, $request->user());

        return back()->with('success', 'Feedback saved successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'issue_type' => ['nullable', Rule::in(array_keys(StudentDisciplineReport::ISSUE_LABELS))],
            'severity' => ['nullable', Rule::in(StudentDisciplineReport::SEVERITIES)],
            'status' => ['nullable', Rule::in(StudentDisciplineReport::STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);
    }
}

