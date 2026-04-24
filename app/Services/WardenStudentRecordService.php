<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\DisciplineComplaint;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassHistory;
use App\Models\StudentResult;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use RuntimeException;

class WardenStudentRecordService
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService,
        private readonly StudentResultService $studentResultService
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     students:LengthAwarePaginator,
     *     filters:array<string, mixed>,
     *     classes:array<int, array{id:int,name:string}>,
     *     sessions:array<int, string>
     * }
     */
    public function getStudents(array $filters, User $user): array
    {
        $normalized = $this->normalizeFilters($filters);

        $students = Student::query()
            ->forWarden($user)
            ->with('classRoom:id,name,section')
            ->when($normalized['search'] !== null, function ($query) use ($normalized): void {
                $search = (string) $normalized['search'];
                $contains = '%'.$search.'%';
                $prefix = $search.'%';

                $query->where(function ($inner) use ($contains, $prefix): void {
                    $inner->where('name', 'like', $contains)
                        ->orWhere('student_id', 'like', $prefix)
                        ->orWhere('father_name', 'like', $contains);
                });
            })
            ->when($normalized['class_id'] !== null, fn ($query) => $query->where('class_id', $normalized['class_id']))
            ->when($normalized['session'] !== null, function ($query) use ($normalized): void {
                $session = (string) $normalized['session'];
                [$fromDate, $toDate] = $this->sessionDateRange($session);

                $query->where(function ($inner) use ($session, $fromDate, $toDate): void {
                    $inner->whereHas('classHistories', fn ($classHistoryQuery) => $classHistoryQuery->where('session', $session))
                        ->orWhereHas('subjectMatrixAssignments', fn ($assignmentQuery) => $assignmentQuery->where('session', $session))
                        ->orWhereHas('subjectAssignments', fn ($assignmentQuery) => $assignmentQuery->where('session', $session));

                    $inner->orWhereHas('results', fn ($resultQuery) => $resultQuery->where('session', $session));
                });
            })
            ->orderBy('name')
            ->orderBy('student_id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        return [
            'students' => $students,
            'filters' => $normalized,
            'classes' => $this->classOptions($user),
            'sessions' => $this->sessionOptions(),
        ];
    }

    /**
     * @return array{
     *     student:Student,
     *     selected_session:string,
     *     sessions:array<int, string>,
     *     profile:array<string, mixed>,
     *     attendance_summary:array<string, mixed>,
     *     academic_summary:array<string, mixed>,
     *     subject_results:array<int, array<string, mixed>>,
     *     recent_results:array<int, StudentResult>,
     *     discipline_summary:array<string, mixed>
     * }
     */
    public function getStudentRecord(Student $student, ?string $session = null, ?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (! $user instanceof User) {
            throw new RuntimeException('Authenticated user context is required.');
        }

        $isVisibleToWarden = Student::query()
            ->forWarden($user)
            ->whereKey((int) $student->id)
            ->exists();

        if (! $isVisibleToWarden) {
            throw new RuntimeException('You are not allowed to access this student profile.');
        }

        $student->load('classRoom:id,name,section');

        $sessions = $this->studentSessionOptions((int) $student->id, $user);
        $selectedSession = $this->resolveRequestedSession($session, $sessions);
        [$fromDate, $toDate] = $this->sessionDateRange($selectedSession);

        $recentResults = $this->studentResultService->getRecentStudentResults((int) $student->id, $selectedSession, 30);

        $academicSummary = $this->buildAcademicSummary((int) $student->id, $selectedSession);
        $subjectResults = $this->buildSubjectResultSummary((int) $student->id, $selectedSession);
        $attendanceSummary = $this->buildAttendanceSummary((int) $student->id, $fromDate, $toDate);
        $disciplineSummary = $this->buildDisciplineSummary((int) $student->id, $fromDate, $toDate);

        $classHistory = StudentClassHistory::query()
            ->where('student_id', (int) $student->id)
            ->where('session', $selectedSession)
            ->with('classRoom:id,name,section')
            ->latest('joined_on')
            ->first();

        return [
            'student' => $student,
            'selected_session' => $selectedSession,
            'sessions' => $sessions,
            'profile' => [
                'class_name' => trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? '')),
                'session_class_name' => trim((string) ($classHistory?->classRoom?->name ?? '').' '.(string) ($classHistory?->classRoom?->section ?? '')),
                'status' => (string) ($student->status ?? 'inactive'),
                'session_status' => (string) ($classHistory?->status ?? 'n/a'),
            ],
            'attendance_summary' => $attendanceSummary,
            'academic_summary' => $academicSummary,
            'subject_results' => $subjectResults,
            'recent_results' => $recentResults->all(),
            'discipline_summary' => $disciplineSummary,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{search:?string,class_id:?int,session:?string,per_page:int}
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(10, min($perPage, 100));

        return [
            'search' => trim((string) ($filters['search'] ?? '')) ?: null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== ''
                ? (int) $filters['class_id']
                : null,
            'session' => trim((string) ($filters['session'] ?? '')) ?: null,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function classOptions(User $user): array
    {
        $classIds = Student::query()
            ->forWarden($user)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        return SchoolClass::query()
            ->when($classIds !== [], fn ($query) => $query->whereIn('id', $classIds))
            ->when($classIds === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $class): array => [
                'id' => (int) $class->id,
                'name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        return collect(array_merge(
            StudentClassHistory::query()
                ->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            $this->dailyDiaryService->sessionOptions()
        ))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function sessionDateRange(?string $session): array
    {
        $sessionValue = trim((string) $session);
        if (! preg_match('/^(\d{4})-(\d{4})$/', $sessionValue, $matches)) {
            return [null, null];
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];

        if ($endYear !== $startYear + 1) {
            return [null, null];
        }

        return [
            Carbon::create($startYear, 7, 1)->toDateString(),
            Carbon::create($endYear, 6, 30)->toDateString(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function studentSessionOptions(int $studentId, User $user): array
    {
        $student = Student::query()
            ->forWarden($user)
            ->whereKey($studentId)
            ->with([
                'subjectMatrixAssignments:id,student_id,session',
                'subjectAssignments:id,student_id,session',
            ])
            ->first();

        return collect(array_merge(
            StudentClassHistory::query()
                ->where('student_id', $studentId)
                ->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            $student?->subjectMatrixAssignments
                ?->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all() ?? [],
            $student?->subjectAssignments
                ?->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all() ?? [],
            $this->dailyDiaryService->sessionOptions()
        ))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $sessions
     */
    private function resolveRequestedSession(?string $requestedSession, array $sessions): string
    {
        $session = trim((string) $requestedSession);
        if ($session !== '' && in_array($session, $sessions, true)) {
            return $session;
        }

        return $sessions[0] ?? $this->dailyDiaryService->resolveSession(null);
    }

    /**
     * @return array{results_count:int,average_percentage:float,current_grade:string}
     */
    private function buildAcademicSummary(int $studentId, string $session): array
    {
        $stats = $this->studentResultService->getStudentResultStats($studentId, $session);

        return [
            'results_count' => (int) $stats['results_count'],
            'average_percentage' => (float) $stats['average_percentage'],
            'current_grade' => (string) $stats['grade'],
        ];
    }

    /**
     * @return array<int, array{subject_name:string,total_marks:float,obtained_marks:float,percentage:float,grade:string}>
     */
    private function buildSubjectResultSummary(int $studentId, string $session): array
    {
        $query = StudentResult::query()
            ->with('subject:id,name')
            ->where('student_id', $studentId)
            ->where('session', $session);

        return $query
            ->get()
            ->groupBy('subject_id')
            ->map(function ($rows): array {
                $totalMarks = (float) $rows->sum('total_marks');
                $obtainedMarks = (float) $rows->sum('obtained_marks');
                $percentage = $totalMarks > 0
                    ? round(($obtainedMarks / $totalMarks) * 100, 2)
                    : 0.0;

                return [
                    'subject_name' => (string) ($rows->first()?->subject?->name ?? 'Subject'),
                    'total_marks' => $totalMarks,
                    'obtained_marks' => $obtainedMarks,
                    'percentage' => $percentage,
                    'grade' => $rows->firstWhere('grade', '!=', null)?->grade
                        ?? $this->gradeFromPercentage($percentage),
                ];
            })
            ->sortBy('subject_name')
            ->values()
            ->all();
    }

    /**
     * @return array{total:int,present:int,absent:int,leave:int,attendance_percentage:float,source:string}
     */
    private function buildAttendanceSummary(int $studentId, ?string $fromDate, ?string $toDate): array
    {
        $modernQuery = Attendance::query()
            ->where('student_id', $studentId);

        if ($fromDate !== null && $toDate !== null) {
            $modernQuery->whereBetween('date', [$fromDate, $toDate]);
        }

        $modernCount = $modernQuery->count();
        if ($modernCount > 0) {
            $summary = $modernQuery
                ->selectRaw("COUNT(*) as total_count,
                    SUM(CASE WHEN lower(status) IN ('present', 'p') THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN lower(status) IN ('absent', 'a') THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN lower(status) IN ('leave', 'l') THEN 1 ELSE 0 END) as leave_count")
                ->first();

            $total = (int) ($summary?->total_count ?? 0);
            $present = (int) ($summary?->present_count ?? 0);
            $absent = (int) ($summary?->absent_count ?? 0);
            $leave = (int) ($summary?->leave_count ?? 0);

            return [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0.0,
                'source' => 'attendance',
            ];
        }

        $legacyQuery = StudentAttendance::query()
            ->where('student_id', $studentId);

        if ($fromDate !== null && $toDate !== null) {
            $legacyQuery->whereBetween('date', [$fromDate, $toDate]);
        }

        $summary = $legacyQuery
            ->selectRaw("COUNT(*) as total_count,
                SUM(CASE WHEN lower(status) IN ('present', 'p') THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN lower(status) IN ('absent', 'a') THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN lower(status) IN ('leave', 'l') THEN 1 ELSE 0 END) as leave_count")
            ->first();

        $total = (int) ($summary?->total_count ?? 0);
        $present = (int) ($summary?->present_count ?? 0);
        $absent = (int) ($summary?->absent_count ?? 0);
        $leave = (int) ($summary?->leave_count ?? 0);

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'leave' => $leave,
            'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0.0,
            'source' => 'student_attendance',
        ];
    }

    /**
     * @return array{total:int,open:int,recent:array<int, array<string, mixed>>}
     */
    private function buildDisciplineSummary(int $studentId, ?string $fromDate, ?string $toDate): array
    {
        $baseQuery = DisciplineComplaint::query()
            ->where('student_id', $studentId);

        if ($fromDate !== null && $toDate !== null) {
            $baseQuery->whereBetween('complaint_date', [$fromDate, $toDate]);
        }

        $rows = (clone $baseQuery)
            ->orderByDesc('complaint_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $total = (int) (clone $baseQuery)->count();
        $open = (int) (clone $baseQuery)
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();

        return [
            'total' => $total,
            'open' => $open,
            'recent' => $rows
                ->map(fn (DisciplineComplaint $row): array => [
                    'id' => (int) $row->id,
                    'date' => optional($row->complaint_date)->format('d M Y') ?? '-',
                    'status' => (string) ($row->status ?? 'pending'),
                    'description' => str((string) $row->description)->squish()->limit(120)->value(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function gradeFromPercentage(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 80 => 'A',
            $percentage >= 70 => 'B',
            $percentage >= 60 => 'C',
            $percentage >= 50 => 'D',
            default => 'F',
        };
    }
}
