<?php

namespace App\Http\Controllers\Principal;

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

        return view('principal.discipline-reports.index', $this->disciplineReportService->getPrincipalReports($validated));
    }

    public function daily(Request $request): View
    {
        $validated = $this->validatedFilters($request);
        if (! isset($validated['date'])) {
            $validated['date'] = now()->toDateString();
        }

        return view('principal.discipline-reports.daily', $this->disciplineReportService->getPrincipalReports($validated));
    }

    public function print(Request $request): View
    {
        $validated = $this->validatedFilters($request);
        if (! isset($validated['date'])) {
            $validated['date'] = now()->toDateString();
        }

        $payload = $this->disciplineReportService->getPrincipalReports([
            ...$validated,
            'paginate' => false,
        ]);

        return view('principal.discipline-reports.print', [
            ...$payload,
            'generated_by' => $request->user(),
            'generated_at' => now(),
        ]);
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
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return view('principal.discipline-reports.show', [
            'report' => $report,
            'issueOptions' => StudentDisciplineReport::ISSUE_LABELS,
            'severityOptions' => StudentDisciplineReport::SEVERITIES,
            'statusOptions' => StudentDisciplineReport::STATUSES,
        ]);
    }

    public function acknowledge(StudentDisciplineReport $report, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'principal_remarks' => ['nullable', 'string', 'max:1200'],
        ]);

        if (($validated['principal_remarks'] ?? null) !== null && trim((string) $validated['principal_remarks']) !== '') {
            $report->forceFill([
                'principal_remarks' => trim((string) $validated['principal_remarks']),
                'updated_by' => (int) $request->user()->id,
            ])->save();
        }

        $this->disciplineReportService->markAcknowledged($report, $request->user());

        return back()->with('success', 'Discipline report marked as acknowledged.');
    }

    public function resolve(StudentDisciplineReport $report, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'principal_remarks' => ['nullable', 'string', 'max:1200'],
        ]);

        $this->disciplineReportService->markResolved($report, $validated, $request->user());

        return back()->with('success', 'Discipline report marked as resolved.');
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

