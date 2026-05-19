<?php

namespace App\Http\Controllers\Teacher;

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
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'issue_type' => ['nullable', Rule::in(array_keys(StudentDisciplineReport::ISSUE_LABELS))],
            'severity' => ['nullable', Rule::in(StudentDisciplineReport::SEVERITIES)],
            'status' => ['nullable', Rule::in(StudentDisciplineReport::STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);

        return view('teacher.discipline-reports.index', $this->disciplineReportService->getTeacherReports(
            $request->user(),
            $validated
        ));
    }

    public function create(Request $request): View
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
        ]);

        return view('teacher.discipline-reports.create', $this->disciplineReportService->getTeacherReports(
            $request->user(),
            [
                'session' => $validated['session'] ?? null,
                'date' => $validated['date'] ?? null,
                'per_page' => 10,
            ]
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['nullable', 'integer', 'exists:students,id', 'required_without:student_ids'],
            'student_ids' => ['nullable', 'array', 'min:1', 'required_without:student_id'],
            'student_ids.*' => ['required', 'integer', 'exists:students,id', 'distinct'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'session' => ['nullable', 'string', 'max:20'],
            'report_date' => ['required', 'date'],
            'issue_type' => ['required', Rule::in(array_keys(StudentDisciplineReport::ISSUE_LABELS))],
            'severity' => ['required', Rule::in(StudentDisciplineReport::SEVERITIES)],
            'description' => ['nullable', 'string', 'max:1200'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ]);

        $reports = $this->disciplineReportService->createReports($validated, $request->user());
        $createdCount = $reports->count();
        $firstReport = $reports->first();

        if ($createdCount === 1 && $firstReport instanceof StudentDisciplineReport) {
            return redirect()
                ->route('teacher.discipline-reports.show', $firstReport)
                ->with('success', 'Discipline report submitted and notifications sent successfully.');
        }

        return redirect()
            ->route('teacher.discipline-reports.index')
            ->with('success', $createdCount.' discipline reports submitted and notifications sent successfully.');
    }

    public function show(StudentDisciplineReport $report, Request $request): View
    {
        if ((int) $report->teacher_id !== (int) $request->user()->id) {
            abort(403, 'You are not allowed to view this discipline report.');
        }

        $report->load([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'subject:id,name',
            'teacher:id,name',
            'acknowledgedBy:id,name',
            'resolvedBy:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return view('teacher.discipline-reports.show', [
            'report' => $report,
            'issueOptions' => StudentDisciplineReport::ISSUE_LABELS,
            'severityOptions' => StudentDisciplineReport::SEVERITIES,
            'statusOptions' => StudentDisciplineReport::STATUSES,
        ]);
    }
}
