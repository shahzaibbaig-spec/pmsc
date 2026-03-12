<?php

namespace App\Modules\Analytics\Services;

use App\Models\Attendance;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TeacherAnalyticsService
{
    private const PASS_PERCENTAGE = 40.0;

    /**
     * @return array<int, string>
     */
    public function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        $sessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
    }

    public function filters(): array
    {
        $teachers = Teacher::query()
            ->with('user:id,name,status')
            ->whereHas('user')
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'employee_code', 'user_id'])
            ->map(fn (Teacher $teacher): array => [
                'id' => (int) $teacher->id,
                'name' => $teacher->user?->name ?: ('Teacher '.$teacher->teacher_id),
                'teacher_id' => $teacher->teacher_id,
                'employee_code' => $teacher->employee_code,
            ])
            ->values()
            ->all();

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $classRoom): array => [
                'id' => (int) $classRoom->id,
                'name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ])
            ->values()
            ->all();

        $subjects = Subject::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Subject $subject): array => [
                'id' => (int) $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
            ])
            ->values()
            ->all();

        return [
            'teachers' => $teachers,
            'classes' => $classes,
            'subjects' => $subjects,
        ];
    }

    public function tableData(
        string $session,
        ?int $teacherId = null,
        ?int $classId = null,
        ?int $subjectId = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = 15
    ): array {
        [$sessionStart, $sessionEnd] = $this->sessionDateRange($session);

        $teacherQuery = Teacher::query()
            ->with('user:id,name')
            ->where(function (Builder $query) use ($session, $classId, $subjectId): void {
                $query
                    ->whereHas('assignments', function (Builder $subQuery) use ($session, $classId, $subjectId): void {
                        $subQuery->where('session', $session);
                        if ($classId !== null) {
                            $subQuery->where('class_id', $classId);
                        }
                        if ($subjectId !== null) {
                            $subQuery->where('subject_id', $subjectId);
                        }
                    })
                    ->orWhereHas('marks', function (Builder $subQuery) use ($session, $classId, $subjectId): void {
                        $subQuery
                            ->where('marks.session', $session)
                            ->whereHas('exam', function (Builder $examQuery) use ($classId, $subjectId): void {
                                if ($classId !== null) {
                                    $examQuery->where('class_id', $classId);
                                }
                                if ($subjectId !== null) {
                                    $examQuery->where('subject_id', $subjectId);
                                }
                            });
                    });
            })
            ->when($teacherId !== null, fn (Builder $query) => $query->where('id', $teacherId))
            ->when($search !== null && trim($search) !== '', function (Builder $query) use ($search): void {
                $term = trim((string) $search);
                $query->where(function (Builder $subQuery) use ($term): void {
                    $subQuery
                        ->where('teacher_id', 'like', $term.'%')
                        ->orWhere('employee_code', 'like', $term.'%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->orderBy('teacher_id');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $teacherQuery->paginate(
            min(max($perPage, 5), 100),
            ['id', 'teacher_id', 'employee_code', 'user_id'],
            'page',
            max($page, 1)
        );

        /** @var Collection<int, Teacher> $teachers */
        $teachers = collect($paginator->items());
        $teacherIds = $teachers->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $attendanceMap = $this->attendanceMap($sessionStart, $sessionEnd, $session, $classId, $subjectId, $teacherIds);
        $marksMap = $this->marksMap($session, $classId, $subjectId, $teacherIds);
        $improvementMap = $this->improvementMap($session, $classId, $subjectId, $teacherIds);
        $classMap = $this->teacherClassMap($session, $classId, $subjectId, $teacherIds);

        $rows = $teachers->map(function (Teacher $teacher) use (
            $attendanceMap,
            $marksMap,
            $improvementMap,
            $classMap
        ): array {
            $tid = (int) $teacher->id;
            $attendance = $attendanceMap[$tid] ?? null;
            $marks = $marksMap[$tid] ?? null;
            $improvement = $improvementMap[$tid] ?? null;
            $classes = $classMap[$tid] ?? [];

            $attendancePercent = $attendance && $attendance['total'] > 0
                ? round(($attendance['present'] / $attendance['total']) * 100, 2)
                : null;

            $classPreview = collect($classes)->take(2)->implode(', ');
            $remaining = count($classes) - 2;
            if ($remaining > 0) {
                $classPreview .= ' +'.$remaining;
            }

            return [
                'teacher_id' => $tid,
                'teacher_name' => $teacher->user?->name ?: ('Teacher '.$teacher->teacher_id),
                'teacher_code' => $teacher->teacher_id,
                'attendance_percentage' => $attendancePercent,
                'average_student_score' => $marks ? round((float) $marks['avg_score'], 2) : null,
                'pass_percentage' => $marks ? round((float) $marks['pass_rate'], 2) : null,
                'improvement_percentage' => $improvement !== null ? round((float) $improvement, 2) : null,
                'classes_count' => count($classes),
                'classes_label' => $classPreview !== '' ? $classPreview : '-',
                'classes' => $classes,
            ];
        })->values();

        $kpis = [
            'average_attendance' => $this->averageMetric($rows->pluck('attendance_percentage')->all()),
            'average_student_score' => $this->averageMetric($rows->pluck('average_student_score')->all()),
            'pass_rate' => $this->averageMetric($rows->pluck('pass_percentage')->all()),
            'improvement' => $this->averageMetric($rows->pluck('improvement_percentage')->all()),
        ];

        return [
            'rows' => $rows->all(),
            'kpis' => $kpis,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    public function teacherDetail(
        int $teacherId,
        string $session,
        ?int $classId = null,
        ?int $subjectId = null
    ): array {
        [$sessionStart, $sessionEnd] = $this->sessionDateRange($session);

        $teacher = Teacher::query()
            ->with('user:id,name')
            ->findOrFail($teacherId, ['id', 'teacher_id', 'employee_code', 'user_id']);

        $assignments = TeacherAssignment::query()
            ->with([
                'classRoom:id,name,section',
                'subject:id,name,code',
            ])
            ->where('teacher_id', $teacherId)
            ->where('session', $session)
            ->when($classId !== null, fn (Builder $query) => $query->where('class_id', $classId))
            ->when($subjectId !== null, fn (Builder $query) => $query->where('subject_id', $subjectId))
            ->orderBy('class_id')
            ->get(['id', 'teacher_id', 'class_id', 'subject_id', 'is_class_teacher', 'session']);

        $classes = $assignments
            ->map(fn (TeacherAssignment $assignment): array => [
                'id' => (int) ($assignment->classRoom?->id ?? $assignment->class_id),
                'name' => trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')),
            ])
            ->filter(fn (array $row): bool => $row['name'] !== '')
            ->unique('id')
            ->values();

        $subjects = $assignments
            ->map(fn (TeacherAssignment $assignment): array => [
                'id' => (int) ($assignment->subject?->id ?? $assignment->subject_id),
                'name' => $assignment->subject?->name ?? '',
                'code' => $assignment->subject?->code,
            ])
            ->filter(fn (array $row): bool => $row['name'] !== '')
            ->unique('id')
            ->values();

        $classIds = $classes->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $attendanceRows = empty($classIds)
            ? collect()
            : Attendance::query()
                ->whereIn('class_id', $classIds)
                ->whereBetween('date', [$sessionStart->toDateString(), $sessionEnd->toDateString()])
                ->get(['date', 'status']);

        $monthlyAttendance = $this->buildMonthlyAttendance($sessionStart, $attendanceRows);

        $marksRows = $this->marksQuery($session, $classId, $subjectId, [$teacherId])
            ->select([
                'marks.id',
                'marks.teacher_id',
                'marks.obtained_marks',
                'marks.total_marks',
                'marks.created_at',
                'exams.class_id',
                'exams.subject_id',
                'exams.exam_type',
            ])
            ->get();

        $examPerformance = $this->buildExamTypePerformance($marksRows);
        $monthlyExamPerformance = $this->buildMonthlyExamPerformance($sessionStart, $marksRows);
        $classPerformance = $this->buildClassPerformance($marksRows);

        $bestClass = null;
        $weakestClass = null;
        if ($classPerformance->isNotEmpty()) {
            $bestClass = $classPerformance->sortByDesc('avg_score')->first();
            $weakestClass = $classPerformance->sortBy('avg_score')->first();
        }

        $metrics = $this->tableData($session, $teacherId, $classId, $subjectId, null, 1, 1);
        $summary = $metrics['rows'][0] ?? null;

        return [
            'teacher' => [
                'id' => (int) $teacher->id,
                'name' => $teacher->user?->name ?: ('Teacher '.$teacher->teacher_id),
                'teacher_id' => $teacher->teacher_id,
                'employee_code' => $teacher->employee_code,
            ],
            'summary' => $summary,
            'assigned_classes' => $classes->all(),
            'assigned_subjects' => $subjects->all(),
            'monthly_attendance' => $monthlyAttendance,
            'exam_performance' => $examPerformance,
            'monthly_exam_performance' => $monthlyExamPerformance,
            'best_class' => $bestClass,
            'weakest_class' => $weakestClass,
            'class_performance' => $classPerformance->values()->all(),
        ];
    }

    /**
     * @param array<int, int> $teacherIds
     * @return array<int, array{total:int,present:int}>
     */
    private function attendanceMap(
        Carbon $sessionStart,
        Carbon $sessionEnd,
        string $session,
        ?int $classId,
        ?int $subjectId,
        array $teacherIds
    ): array {
        if (empty($teacherIds)) {
            return [];
        }

        $classSubquery = $this->teacherClassSubquery($session, $classId, $subjectId, $teacherIds);

        $rows = DB::query()
            ->fromSub($classSubquery, 'tc')
            ->join('attendance as a', 'a.class_id', '=', 'tc.class_id')
            ->whereBetween('a.date', [$sessionStart->toDateString(), $sessionEnd->toDateString()])
            ->selectRaw("
                tc.teacher_id as teacher_id,
                COUNT(*) as total_records,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_records
            ")
            ->groupBy('tc.teacher_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->teacher_id] = [
                'total' => (int) $row->total_records,
                'present' => (int) $row->present_records,
            ];
        }

        return $map;
    }

    /**
     * @param array<int, int> $teacherIds
     * @return array<int, array{avg_score:float,pass_rate:float}>
     */
    private function marksMap(string $session, ?int $classId, ?int $subjectId, array $teacherIds): array
    {
        if (empty($teacherIds)) {
            return [];
        }

        $rows = $this->marksQuery($session, $classId, $subjectId, $teacherIds)
            ->selectRaw("
                marks.teacher_id as teacher_id,
                AVG((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as avg_score,
                (
                    SUM(
                        CASE
                            WHEN ((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) >= ?
                            THEN 1
                            ELSE 0
                        END
                    ) * 100.0
                ) / NULLIF(COUNT(*), 0) as pass_rate
            ", [self::PASS_PERCENTAGE])
            ->groupBy('marks.teacher_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->teacher_id] = [
                'avg_score' => (float) ($row->avg_score ?? 0),
                'pass_rate' => (float) ($row->pass_rate ?? 0),
            ];
        }

        return $map;
    }

    /**
     * @param array<int, int> $teacherIds
     * @return array<int, float|null>
     */
    private function improvementMap(string $session, ?int $classId, ?int $subjectId, array $teacherIds): array
    {
        if (empty($teacherIds)) {
            return [];
        }

        $rows = $this->marksQuery($session, $classId, $subjectId, $teacherIds)
            ->whereIn('exams.exam_type', ['first_term', 'final_term'])
            ->selectRaw("
                marks.teacher_id as teacher_id,
                exams.exam_type as exam_type,
                AVG((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as avg_score
            ")
            ->groupBy('marks.teacher_id', 'exams.exam_type')
            ->get();

        $grouped = $rows->groupBy('teacher_id');
        $map = [];

        foreach ($grouped as $teacherId => $teacherRows) {
            $firstTerm = $teacherRows->firstWhere('exam_type', 'first_term');
            $finalTerm = $teacherRows->firstWhere('exam_type', 'final_term');

            if (! $firstTerm || ! $finalTerm) {
                $map[(int) $teacherId] = null;
                continue;
            }

            $map[(int) $teacherId] = (float) $finalTerm->avg_score - (float) $firstTerm->avg_score;
        }

        return $map;
    }

    /**
     * @param array<int, int> $teacherIds
     * @return array<int, array<int, string>>
     */
    private function teacherClassMap(string $session, ?int $classId, ?int $subjectId, array $teacherIds): array
    {
        if (empty($teacherIds)) {
            return [];
        }

        $classSubquery = $this->teacherClassSubquery($session, $classId, $subjectId, $teacherIds);

        $rows = DB::query()
            ->fromSub($classSubquery, 'tc')
            ->join('school_classes as c', 'c.id', '=', 'tc.class_id')
            ->select([
                'tc.teacher_id',
                'c.name',
                'c.section',
            ])
            ->orderBy('c.name')
            ->orderBy('c.section')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $tid = (int) $row->teacher_id;
            $map[$tid] ??= [];
            $map[$tid][] = trim((string) $row->name.' '.((string) ($row->section ?? '')));
        }

        return $map;
    }

    /**
     * @param array<int, int>|null $teacherIds
     */
    private function teacherClassSubquery(
        string $session,
        ?int $classId = null,
        ?int $subjectId = null,
        ?array $teacherIds = null
    ): Builder {
        return TeacherAssignment::query()
            ->select([
                'teacher_id',
                'class_id',
            ])
            ->where('session', $session)
            ->when($classId !== null, fn (Builder $query) => $query->where('class_id', $classId))
            ->when($subjectId !== null, fn (Builder $query) => $query->where('subject_id', $subjectId))
            ->when($teacherIds !== null && $teacherIds !== [], fn (Builder $query) => $query->whereIn('teacher_id', $teacherIds))
            ->distinct();
    }

    /**
     * @param array<int, int> $teacherIds
     */
    private function marksQuery(string $session, ?int $classId, ?int $subjectId, array $teacherIds): Builder
    {
        return Mark::query()
            ->join('exams', 'exams.id', '=', 'marks.exam_id')
            ->where('marks.session', $session)
            ->whereIn('marks.teacher_id', $teacherIds)
            ->when($classId !== null, fn (Builder $query) => $query->where('exams.class_id', $classId))
            ->when($subjectId !== null, fn (Builder $query) => $query->where('exams.subject_id', $subjectId));
    }

    /**
     * @param array<int, float|int|string|null> $values
     */
    private function averageMetric(array $values): ?float
    {
        $valid = array_values(array_filter($values, fn ($value): bool => $value !== null));
        if ($valid === []) {
            return null;
        }

        return round(array_sum(array_map(fn ($value): float => (float) $value, $valid)) / count($valid), 2);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function sessionDateRange(string $session): array
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $session, $matches)) {
            throw new RuntimeException('Invalid session format.');
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        if ($endYear !== ($startYear + 1)) {
            throw new RuntimeException('Invalid session range.');
        }

        $start = Carbon::create($startYear, 7, 1)->startOfDay();
        $end = Carbon::create($endYear, 6, 30)->endOfDay();

        return [$start, $end];
    }

    private function buildMonthlyAttendance(Carbon $sessionStart, Collection $attendanceRows): array
    {
        $monthKeys = [];
        $cursor = $sessionStart->copy();
        for ($i = 0; $i < 12; $i++) {
            $monthKeys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $grouped = $attendanceRows
            ->groupBy(fn ($row) => Carbon::parse((string) $row->date)->format('Y-m'));

        $labels = [];
        $values = [];
        foreach ($monthKeys as $monthKey) {
            $monthRows = $grouped->get($monthKey, collect());
            $total = $monthRows->count();
            $present = $monthRows->filter(fn ($row): bool => (string) $row->status === 'present')->count();

            $labels[] = Carbon::createFromFormat('Y-m', $monthKey)->format('M Y');
            $values[] = $total > 0 ? round(($present / $total) * 100, 2) : 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function buildExamTypePerformance(Collection $marksRows): array
    {
        $typeOrder = ['class_test', 'bimonthly_test', 'first_term', 'final_term'];
        $typeLabels = [
            'class_test' => 'Class Test',
            'bimonthly_test' => 'Bimonthly',
            'first_term' => '1st Term',
            'final_term' => 'Final Term',
        ];

        $grouped = $marksRows->groupBy('exam_type');

        $labels = [];
        $averageScores = [];
        $passRates = [];
        foreach ($typeOrder as $type) {
            $rows = $grouped->get($type, collect());
            $labels[] = $typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));

            if ($rows->isEmpty()) {
                $averageScores[] = 0;
                $passRates[] = 0;
                continue;
            }

            $percentages = $rows->map(fn ($row): float => $this->percentage((float) $row->obtained_marks, (float) $row->total_marks));
            $averageScores[] = round($percentages->avg() ?? 0, 2);
            $passRates[] = round(($percentages->filter(fn (float $value): bool => $value >= self::PASS_PERCENTAGE)->count() / $percentages->count()) * 100, 2);
        }

        return [
            'labels' => $labels,
            'average_scores' => $averageScores,
            'pass_rates' => $passRates,
        ];
    }

    private function buildMonthlyExamPerformance(Carbon $sessionStart, Collection $marksRows): array
    {
        $monthKeys = [];
        $cursor = $sessionStart->copy();
        for ($i = 0; $i < 12; $i++) {
            $monthKeys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $grouped = $marksRows->groupBy(fn ($row) => Carbon::parse((string) $row->created_at)->format('Y-m'));

        $labels = [];
        $values = [];
        foreach ($monthKeys as $monthKey) {
            $rows = $grouped->get($monthKey, collect());
            $labels[] = Carbon::createFromFormat('Y-m', $monthKey)->format('M Y');
            if ($rows->isEmpty()) {
                $values[] = 0;
                continue;
            }

            $percentages = $rows->map(fn ($row): float => $this->percentage((float) $row->obtained_marks, (float) $row->total_marks));
            $values[] = round($percentages->avg() ?? 0, 2);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function buildClassPerformance(Collection $marksRows): Collection
    {
        if ($marksRows->isEmpty()) {
            return collect();
        }

        $classNames = SchoolClass::query()
            ->whereIn('id', $marksRows->pluck('class_id')->filter()->unique()->values())
            ->get(['id', 'name', 'section'])
            ->mapWithKeys(fn (SchoolClass $classRoom): array => [
                (int) $classRoom->id => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ]);

        return $marksRows
            ->groupBy('class_id')
            ->map(function (Collection $rows, $classId) use ($classNames): array {
                $percentages = $rows->map(fn ($row): float => $this->percentage((float) $row->obtained_marks, (float) $row->total_marks));
                $avgScore = round($percentages->avg() ?? 0, 2);
                $passRate = $percentages->count() > 0
                    ? round(($percentages->filter(fn (float $value): bool => $value >= self::PASS_PERCENTAGE)->count() / $percentages->count()) * 100, 2)
                    : 0.0;

                return [
                    'class_id' => (int) $classId,
                    'class_name' => $classNames->get((int) $classId, 'Class #'.$classId),
                    'avg_score' => $avgScore,
                    'pass_rate' => $passRate,
                    'entries' => $percentages->count(),
                ];
            })
            ->values();
    }

    private function percentage(float $obtained, float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return ($obtained / $total) * 100;
    }
}

