<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\TeacherResultEntryFilterRequest;
use App\Models\Teacher;
use App\Services\TeacherResultEntryReportService;
use Illuminate\View\View;

class TeacherResultEntryController extends Controller
{
    public function __construct(private readonly TeacherResultEntryReportService $reportService)
    {
    }

    public function index(TeacherResultEntryFilterRequest $request): View
    {
        $filters = $request->validated();
        $session = $this->reportService->resolveSession(isset($filters['session']) ? (string) $filters['session'] : null);
        $filters['session'] = $session;

        return view('principal.results.teacher-entries.index', [
            'filters' => $this->presentedFilters($filters),
            'summary' => $this->reportService->getTeacherEntrySummary($filters),
            'cards' => $this->reportService->getTeacherEntryDashboardCards($filters),
            'teachers' => $this->reportService->teacherOptions($session),
            'classes' => $this->reportService->classOptions($session),
            'subjects' => $this->reportService->subjectOptions($session),
            'sessions' => $this->reportService->sessionOptions(),
            'examTypes' => $this->reportService->examTypeOptions(),
        ]);
    }

    public function showTeacher(Teacher $teacher, TeacherResultEntryFilterRequest $request): View
    {
        $filters = $request->validated();
        $session = $this->reportService->resolveSession(isset($filters['session']) ? (string) $filters['session'] : null);
        $filters['session'] = $session;

        $entriesPayload = $this->reportService->getTeacherSubjectEntries((int) $teacher->id, $filters);
        $selectedExamType = isset($filters['exam_type']) && trim((string) $filters['exam_type']) !== ''
            ? (string) $filters['exam_type']
            : null;
        $completion = $selectedExamType !== null
            ? $this->reportService->getTeacherCompletionStatus((int) $teacher->id, $session, $selectedExamType)
            : null;

        return view('principal.results.teacher-entries.show', [
            'teacher' => $teacher->loadMissing('user:id,name'),
            'filters' => $this->presentedFilters($filters),
            'entries' => $entriesPayload['items'] ?? [],
            'groups' => $entriesPayload['groups'] ?? [],
            'completion' => $completion,
            'classes' => $this->reportService->classOptions($session),
            'subjects' => $this->reportService->subjectOptions($session),
            'sessions' => $this->reportService->sessionOptions(),
            'examTypes' => $this->reportService->examTypeOptions(),
        ]);
    }

    public function logs(Teacher $teacher, TeacherResultEntryFilterRequest $request): View
    {
        $filters = $request->validated();
        $session = $this->reportService->resolveSession(isset($filters['session']) ? (string) $filters['session'] : null);
        $filters['session'] = $session;

        return view('principal.results.teacher-entries.logs', [
            'teacher' => $teacher->loadMissing('user:id,name'),
            'filters' => $this->presentedFilters($filters),
            'logs' => $this->reportService->getTeacherEntryLogs((int) $teacher->id, $filters),
            'classes' => $this->reportService->classOptions($session),
            'subjects' => $this->reportService->subjectOptions($session),
            'sessions' => $this->reportService->sessionOptions(),
            'examTypes' => $this->reportService->examTypeOptions(),
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function presentedFilters(array $filters): array
    {
        return [
            'teacher_id' => $filters['teacher_id'] ?? null,
            'class_id' => $filters['class_id'] ?? null,
            'subject_id' => $filters['subject_id'] ?? null,
            'session' => $filters['session'] ?? null,
            'exam_type' => $filters['exam_type'] ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];
    }
}

