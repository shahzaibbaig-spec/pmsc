<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentDisciplineReport;
use App\Services\StudentDisciplineReportService;
use Illuminate\Database\Eloquent\Builder;
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
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'issue_type' => ['nullable', Rule::in(array_keys(StudentDisciplineReport::ISSUE_LABELS))],
            'severity' => ['nullable', Rule::in(StudentDisciplineReport::SEVERITIES)],
            'status' => ['nullable', Rule::in(StudentDisciplineReport::STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);

        return view('warden.class-discipline-reports.index', $this->disciplineReportService->getWardenReports(
            $request->user(),
            $validated
        ));
    }

    public function show(StudentDisciplineReport $report, Request $request): View
    {
        $allowedClassIds = Student::query()
            ->forWarden($request->user())
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        $allowed = StudentDisciplineReport::query()
            ->whereKey((int) $report->id)
            ->when(
                $allowedClassIds !== [],
                fn (Builder $reportQuery): Builder => $reportQuery
                    ->whereHas('student', fn (Builder $studentQuery) => $studentQuery->forWarden($request->user()))
            )
            ->exists();

        if (! $allowed) {
            abort(403, 'You are not allowed to view this report.');
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

        return view('warden.class-discipline-reports.show', [
            'report' => $report,
            'issueOptions' => StudentDisciplineReport::ISSUE_LABELS,
            'severityOptions' => StudentDisciplineReport::SEVERITIES,
            'statusOptions' => StudentDisciplineReport::STATUSES,
        ]);
    }

    public function acknowledge(StudentDisciplineReport $report, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warden_remarks' => ['nullable', 'string', 'max:1200'],
        ]);

        $allowedClassIds = Student::query()
            ->forWarden($request->user())
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        $allowed = StudentDisciplineReport::query()
            ->whereKey((int) $report->id)
            ->when(
                $allowedClassIds !== [],
                fn (Builder $reportQuery): Builder => $reportQuery
                    ->whereHas('student', fn (Builder $studentQuery) => $studentQuery->forWarden($request->user()))
            )
            ->exists();

        if (! $allowed) {
            abort(403, 'You are not allowed to update this report.');
        }

        if (($validated['warden_remarks'] ?? null) !== null && trim((string) $validated['warden_remarks']) !== '') {
            $report->forceFill([
                'warden_remarks' => trim((string) $validated['warden_remarks']),
                'updated_by' => (int) $request->user()->id,
            ])->save();
        }

        $this->disciplineReportService->markAcknowledged($report, $request->user());

        return back()->with('success', 'Discipline report marked as acknowledged.');
    }
}
