<?php

namespace App\Modules\Analytics\Services;

use App\Models\Attendance;
use App\Models\FeeDefaulter;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSubjectAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AnalyticsService
{
    private const PASS_PERCENTAGE = 60.0;
    private const EXAM_TYPES = ['class_test', 'bimonthly_test', 'first_term', 'final_term'];

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

    public function classOptions(): Collection
    {
        return SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);
    }

    public function dashboard(string $session, ?int $classId = null, ?string $examType = null): array
    {
        $exam = $this->normalizeExamType($examType);
        $className = null;
        if ($classId !== null) {
            $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
            if (! $classRoom) {
                throw new RuntimeException('Class not found.');
            }

            $className = $this->classLabel((string) $classRoom->name, $classRoom->section);
        }

        [$sessionStart, $sessionEnd] = $this->sessionDateRange($session);

        $studentPerformance = $this->studentPerformanceRows($session, $classId, $exam);
        $attendanceByStudent = $this->studentAttendanceRows($sessionStart, $sessionEnd, $classId)
            ->keyBy('student_id');

        return [
            'session' => $session,
            'class_id' => $classId,
            'exam' => $exam,
            'class_name' => $className,
            'kpis' => $this->kpis($session, $classId, $studentPerformance, $attendanceByStudent),
            'top_performers' => $this->topPerformers($studentPerformance),
            'weak_students' => $this->weakStudents($studentPerformance, $attendanceByStudent, $classId),
            'subject_performance' => $this->subjectPerformanceRows($session, $classId, $exam)->all(),
            'teacher_performance' => $this->teacherPerformanceRows($session, $classId, $exam)->take(15)->values()->all(),
            'class_comparison' => $this->classComparisonRows($session, $classId, $exam)->values()->all(),
            'charts' => [
                'attendance_trend' => $this->attendanceTrend($session, $classId),
                'exam_comparison' => $this->examComparison($session, $classId, null, $exam),
            ],
        ];
    }

    public function getDashboardSummary(string $session, ?string $examType = null, ?int $classId = null): array
    {
        return $this->dashboard($session, $classId, $examType)['kpis'];
    }

    public function getTopPerformers(string $session, ?string $examType = null, ?int $classId = null): array
    {
        $exam = $this->normalizeExamType($examType);

        return $this->topPerformers($this->studentPerformanceRows($session, $classId, $exam));
    }

    public function getWeakStudents(string $session, ?string $examType = null, ?int $classId = null): array
    {
        $exam = $this->normalizeExamType($examType);
        [$sessionStart, $sessionEnd] = $this->sessionDateRange($session);

        $studentPerformance = $this->studentPerformanceRows($session, $classId, $exam);
        $attendanceByStudent = $this->studentAttendanceRows($sessionStart, $sessionEnd, $classId)->keyBy('student_id');

        return $this->weakStudents($studentPerformance, $attendanceByStudent, $classId);
    }

    public function getSubjectPerformance(string $session, ?string $examType = null, ?int $classId = null): array
    {
        $exam = $this->normalizeExamType($examType);

        return $this->subjectPerformanceRows($session, $classId, $exam)->all();
    }

    public function getTeacherPerformance(string $session, ?string $examType = null, ?int $classId = null): array
    {
        $exam = $this->normalizeExamType($examType);

        return $this->teacherPerformanceRows($session, $classId, $exam)->values()->all();
    }

    public function getAttendanceTrend(string $session, ?string $examType = null, ?int $classId = null): array
    {
        return $this->attendanceTrend($session, $classId);
    }

    public function getFeeDefaulterSummary(string $session, ?string $examType = null, ?int $classId = null): array
    {
        if (! Schema::hasTable('fee_defaulters')) {
            return [
                'active_count' => 0,
                'total_due' => 0.0,
                'oldest_due_date' => null,
            ];
        }

        $query = FeeDefaulter::query()
            ->join('students as s', 's.id', '=', 'fee_defaulters.student_id')
            ->where('fee_defaulters.session', $session)
            ->where('fee_defaulters.is_active', true)
            ->where('fee_defaulters.total_due', '>', 0)
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->when($classId !== null, fn ($subQuery) => $subQuery->where('s.class_id', $classId));

        return [
            'active_count' => (clone $query)->count(),
            'total_due' => round((float) ((clone $query)->sum('fee_defaulters.total_due') ?? 0), 2),
            'oldest_due_date' => (clone $query)->min('fee_defaulters.oldest_due_date'),
        ];
    }

    public function getClassComparison(string $session, ?string $examType = null, ?int $classId = null): array
    {
        $exam = $this->normalizeExamType($examType);

        return $this->classComparisonRows($session, $classId, $exam)->values()->all();
    }

    public function classDrilldown(int $classId, string $session): array
    {
        $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
        if (! $classRoom) {
            throw new RuntimeException('Class not found.');
        }

        $payload = $this->dashboard($session, $classId);
        $overallClassRanking = $this->classComparisonRows($session, null);
        $rankRow = $overallClassRanking->firstWhere('class_id', $classId);

        $topPerformers = $this->topPerformers($this->studentPerformanceRows($session, $classId), 20);

        return [
            'class' => [
                'id' => (int) $classRoom->id,
                'name' => $this->classLabel((string) $classRoom->name, $classRoom->section),
            ],
            'session' => $session,
            'kpis' => $payload['kpis'],
            'top_performers' => $topPerformers,
            'weak_students' => $payload['weak_students'],
            'subject_performance' => $payload['subject_performance'],
            'teacher_performance' => $payload['teacher_performance'],
            'class_rank' => [
                'position' => $rankRow['rank'] ?? null,
                'total_classes' => $overallClassRanking->count(),
            ],
            'charts' => $payload['charts'],
        ];
    }

    public function teacherDrilldown(int $teacherId, string $session, ?int $classId = null): array
    {
        if ($classId !== null && ! SchoolClass::query()->whereKey($classId)->exists()) {
            throw new RuntimeException('Class not found.');
        }

        $teacher = Teacher::query()
            ->with('user:id,name,status')
            ->find($teacherId, ['id', 'teacher_id', 'user_id']);

        if (! $teacher) {
            throw new RuntimeException('Teacher not found.');
        }

        $teacherPerformanceRows = $this->teacherPerformanceRows($session, $classId);
        $summaryRow = $teacherPerformanceRows->firstWhere('teacher_id', (int) $teacher->id);

        $assignedClasses = TeacherSubjectAssignment::query()
            ->with('classRoom:id,name,section')
            ->where('session', $session)
            ->where('teacher_id', $teacher->id)
            ->when($classId !== null, fn (Builder $query) => $query->where('class_id', $classId))
            ->orderBy('class_id')
            ->get(['id', 'class_id'])
            ->map(fn (TeacherSubjectAssignment $assignment): array => [
                'id' => (int) $assignment->class_id,
                'name' => $this->classLabel(
                    (string) ($assignment->classRoom?->name ?? 'Class'),
                    $assignment->classRoom?->section
                ),
            ])
            ->unique('id')
            ->values()
            ->all();

        $subjectRows = Mark::query()
            ->join('exams as e', 'e.id', '=', 'marks.exam_id')
            ->join('subjects as sub', 'sub.id', '=', 'e.subject_id')
            ->join('school_classes as c', 'c.id', '=', 'e.class_id')
            ->join('students as s', 's.id', '=', 'marks.student_id')
            ->where('marks.session', $session)
            ->where('marks.teacher_id', $teacher->id)
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->when($classId !== null, fn ($query) => $query->where('e.class_id', $classId))
            ->selectRaw("
                e.class_id as class_id,
                c.name as class_name,
                c.section as class_section,
                e.subject_id as subject_id,
                sub.name as subject_name,
                AVG((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as average_percentage,
                (
                    SUM(
                        CASE
                            WHEN ((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) >= ?
                            THEN 1
                            ELSE 0
                        END
                    ) * 100.0
                ) / NULLIF(COUNT(*), 0) as pass_percentage,
                COUNT(*) as entries
            ", [self::PASS_PERCENTAGE])
            ->groupBy('e.class_id', 'c.name', 'c.section', 'e.subject_id', 'sub.name')
            ->orderBy('c.name')
            ->orderBy('c.section')
            ->orderBy('sub.name')
            ->get()
            ->map(function ($row): array {
                return [
                    'class_id' => (int) $row->class_id,
                    'class_name' => $this->classLabel((string) $row->class_name, $row->class_section),
                    'subject_id' => (int) $row->subject_id,
                    'subject_name' => (string) $row->subject_name,
                    'average_percentage' => round((float) $row->average_percentage, 2),
                    'pass_percentage' => round((float) $row->pass_percentage, 2),
                    'entries' => (int) $row->entries,
                ];
            })
            ->values();

        $classBreakdownRows = $subjectRows
            ->groupBy('class_id')
            ->map(function (Collection $rows): array {
                return [
                    'class_id' => (int) ($rows->first()['class_id'] ?? 0),
                    'class_name' => (string) ($rows->first()['class_name'] ?? 'Class'),
                    'average_percentage' => round((float) ($rows->avg('average_percentage') ?? 0), 2),
                ];
            })
            ->sortByDesc('average_percentage')
            ->values();

        return [
            'teacher' => [
                'id' => (int) $teacher->id,
                'name' => (string) ($teacher->user?->name ?? ('Teacher '.$teacher->teacher_id)),
                'teacher_code' => (string) ($teacher->teacher_id ?? ''),
            ],
            'session' => $session,
            'class_id' => $classId,
            'summary' => [
                'average_score' => $summaryRow['average_score'] ?? null,
                'pass_percentage' => $summaryRow['pass_percentage'] ?? null,
                'rank' => $summaryRow['rank'] ?? null,
                'entries' => $summaryRow['entries'] ?? 0,
                'classes_count' => $summaryRow['classes_count'] ?? 0,
            ],
            'assigned_classes' => $assignedClasses,
            'subject_performance' => $subjectRows->all(),
            'charts' => [
                'exam_comparison' => $this->examComparison($session, $classId, (int) $teacher->id),
                'class_breakdown' => [
                    'labels' => $classBreakdownRows->pluck('class_name')->all(),
                    'values' => $classBreakdownRows->pluck('average_percentage')->all(),
                ],
            ],
        ];
    }

    public function studentPerformanceRows(string $session, ?int $classId = null, ?string $examType = null): Collection
    {
        $exam = $this->normalizeExamType($examType);

        return Mark::query()
            ->join('exams as e', 'e.id', '=', 'marks.exam_id')
            ->join('students as s', 's.id', '=', 'marks.student_id')
            ->join('school_classes as c', 'c.id', '=', 's.class_id')
            ->where('marks.session', $session)
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->when($classId !== null, fn ($query) => $query->where('s.class_id', $classId))
            ->when($exam !== null, fn ($query) => $query->where('e.exam_type', $exam))
            ->selectRaw('
                marks.student_id as student_id,
                s.student_id as student_code,
                s.name as student_name,
                s.class_id as class_id,
                c.name as class_name,
                c.section as class_section,
                AVG((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as percentage
            ')
            ->groupBy('marks.student_id', 's.student_id', 's.name', 's.class_id', 'c.name', 'c.section')
            ->orderByDesc('percentage')
            ->orderBy('s.name')
            ->get()
            ->map(fn ($row): array => [
                'student_id' => (int) $row->student_id,
                'student_code' => (string) ($row->student_code ?? ''),
                'student_name' => (string) $row->student_name,
                'class_id' => (int) $row->class_id,
                'class_name' => $this->classLabel((string) $row->class_name, $row->class_section),
                'percentage' => round((float) $row->percentage, 2),
            ])
            ->values();
    }

    public function studentAttendanceRows(
        Carbon $sessionStart,
        Carbon $sessionEnd,
        ?int $classId = null
    ): Collection {
        return Attendance::query()
            ->join('students as s', 's.id', '=', 'attendance.student_id')
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->whereBetween('attendance.date', [$sessionStart->toDateString(), $sessionEnd->toDateString()])
            ->when($classId !== null, fn ($query) => $query->where('attendance.class_id', $classId))
            ->selectRaw("
                attendance.student_id as student_id,
                (
                    SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) * 100.0
                ) / NULLIF(COUNT(*), 0) as attendance_percentage
            ")
            ->groupBy('attendance.student_id')
            ->get()
            ->map(fn ($row): array => [
                'student_id' => (int) $row->student_id,
                'attendance_percentage' => round((float) $row->attendance_percentage, 2),
            ])
            ->values();
    }

    public function subjectPerformanceRows(string $session, ?int $classId = null, ?string $examType = null): Collection
    {
        $exam = $this->normalizeExamType($examType);

        return Mark::query()
            ->join('exams as e', 'e.id', '=', 'marks.exam_id')
            ->join('subjects as sub', 'sub.id', '=', 'e.subject_id')
            ->join('students as s', 's.id', '=', 'marks.student_id')
            ->where('marks.session', $session)
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->when($classId !== null, fn ($query) => $query->where('e.class_id', $classId))
            ->when($exam !== null, fn ($query) => $query->where('e.exam_type', $exam))
            ->selectRaw("
                e.subject_id as subject_id,
                sub.name as subject_name,
                sub.code as subject_code,
                AVG((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as average_percentage,
                (
                    SUM(
                        CASE
                            WHEN ((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) >= ?
                            THEN 1
                            ELSE 0
                        END
                    ) * 100.0
                ) / NULLIF(COUNT(*), 0) as pass_percentage,
                COUNT(*) as entries
            ", [self::PASS_PERCENTAGE])
            ->groupBy('e.subject_id', 'sub.name', 'sub.code')
            ->orderBy('sub.name')
            ->get()
            ->map(function ($row): array {
                $average = $row->average_percentage !== null ? round((float) $row->average_percentage, 2) : null;
                $pass = $row->pass_percentage !== null ? round((float) $row->pass_percentage, 2) : null;

                return [
                    'subject_id' => (int) $row->subject_id,
                    'subject_name' => (string) $row->subject_name,
                    'subject_code' => (string) ($row->subject_code ?? ''),
                    'average_percentage' => $average,
                    'pass_percentage' => $pass,
                    'difficulty' => $this->difficultyFromAverage($average),
                    'entries' => (int) $row->entries,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $difficultyDiff = $this->difficultyWeight($right['difficulty']) <=> $this->difficultyWeight($left['difficulty']);
                if ($difficultyDiff !== 0) {
                    return $difficultyDiff;
                }

                $leftAverage = $left['average_percentage'] ?? 0;
                $rightAverage = $right['average_percentage'] ?? 0;
                if ($leftAverage !== $rightAverage) {
                    return $leftAverage <=> $rightAverage;
                }

                return strcasecmp($left['subject_name'], $right['subject_name']);
            })
            ->values();
    }

    public function teacherPerformanceRows(string $session, ?int $classId = null, ?string $examType = null): Collection
    {
        $exam = $this->normalizeExamType($examType);

        $teachers = TeacherSubjectAssignment::query()
            ->join('teachers as t', 't.id', '=', 'teacher_subject_assignments.teacher_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('teacher_subject_assignments.session', $session)
            ->when($classId !== null, fn ($query) => $query->where('teacher_subject_assignments.class_id', $classId))
            ->where(function ($query): void {
                $query->whereNull('u.status')
                    ->orWhere('u.status', 'active');
            })
            ->selectRaw('
                t.id as teacher_id,
                t.teacher_id as teacher_code,
                u.name as teacher_name,
                COUNT(DISTINCT teacher_subject_assignments.class_id) as classes_count
            ')
            ->groupBy('t.id', 't.teacher_id', 'u.name')
            ->orderBy('u.name')
            ->get();

        $teacherIds = $teachers->pluck('teacher_id')->map(fn ($id): int => (int) $id)->all();

        $marksByTeacher = [];
        if ($teacherIds !== []) {
            $markRows = Mark::query()
                ->join('exams as e', 'e.id', '=', 'marks.exam_id')
                ->join('students as s', 's.id', '=', 'marks.student_id')
                ->where('marks.session', $session)
                ->whereNull('s.deleted_at')
                ->where('s.status', 'active')
                ->whereIn('marks.teacher_id', $teacherIds)
                ->when($classId !== null, fn ($query) => $query->where('e.class_id', $classId))
                ->when($exam !== null, fn ($query) => $query->where('e.exam_type', $exam))
                ->selectRaw("
                    marks.teacher_id as teacher_id,
                    AVG((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as average_score,
                    (
                        SUM(
                            CASE
                                WHEN ((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) >= ?
                                THEN 1
                                ELSE 0
                            END
                        ) * 100.0
                    ) / NULLIF(COUNT(*), 0) as pass_percentage,
                    COUNT(*) as entries
                ", [self::PASS_PERCENTAGE])
                ->groupBy('marks.teacher_id')
                ->get();

            foreach ($markRows as $row) {
                $marksByTeacher[(int) $row->teacher_id] = [
                    'average_score' => $row->average_score !== null ? round((float) $row->average_score, 2) : null,
                    'pass_percentage' => $row->pass_percentage !== null ? round((float) $row->pass_percentage, 2) : null,
                    'entries' => (int) ($row->entries ?? 0),
                ];
            }
        }

        $rows = $teachers->map(function ($row) use ($marksByTeacher): array {
            $teacherId = (int) $row->teacher_id;
            $metric = $marksByTeacher[$teacherId] ?? null;

            return [
                'teacher_id' => $teacherId,
                'teacher_code' => (string) ($row->teacher_code ?? ''),
                'teacher_name' => (string) $row->teacher_name,
                'average_score' => $metric['average_score'] ?? null,
                'pass_percentage' => $metric['pass_percentage'] ?? null,
                'entries' => $metric['entries'] ?? 0,
                'classes_count' => (int) ($row->classes_count ?? 0),
            ];
        });

        return $this->rankRowsByMetric($rows, 'average_score');
    }

    public function classComparisonRows(string $session, ?int $classId = null, ?string $examType = null): Collection
    {
        $exam = $this->normalizeExamType($examType);
        $classes = $this->classMap($classId);
        if ($classes->isEmpty()) {
            return collect();
        }

        [$sessionStart, $sessionEnd] = $this->sessionDateRange($session);

        $studentPerformanceByClass = $this->studentPerformanceRows($session, $classId, $exam)->groupBy('class_id');
        $attendanceByClass = Attendance::query()
            ->join('students as s', 's.id', '=', 'attendance.student_id')
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->whereBetween('attendance.date', [$sessionStart->toDateString(), $sessionEnd->toDateString()])
            ->when($classId !== null, fn ($query) => $query->where('attendance.class_id', $classId))
            ->selectRaw("
                attendance.class_id as class_id,
                (
                    SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) * 100.0
                ) / NULLIF(COUNT(*), 0) as attendance_percentage
            ")
            ->groupBy('attendance.class_id')
            ->get()
            ->keyBy('class_id');

        $studentCounts = $this->activeStudentsQuery($classId)
            ->selectRaw('class_id, COUNT(*) as students_count')
            ->groupBy('class_id')
            ->pluck('students_count', 'class_id');

        $rows = $classes->map(function (string $className, int $mappedClassId) use (
            $studentPerformanceByClass,
            $attendanceByClass,
            $studentCounts
        ): array {
            $resultRows = $studentPerformanceByClass->get($mappedClassId, collect());
            $studentsWithResults = $resultRows->count();
            $average = $studentsWithResults > 0 ? round((float) ($resultRows->avg('percentage') ?? 0), 2) : null;
            $pass = $studentsWithResults > 0
                ? round(
                    (
                        $resultRows->filter(fn (array $row): bool => (float) $row['percentage'] >= self::PASS_PERCENTAGE)->count()
                        * 100.0
                    ) / $studentsWithResults,
                    2
                )
                : null;

            $attendancePercentage = null;
            if ($attendanceByClass->has($mappedClassId)) {
                $attendancePercentage = round((float) ($attendanceByClass->get($mappedClassId)->attendance_percentage ?? 0), 2);
            }

            return [
                'class_id' => (int) $mappedClassId,
                'class_name' => $className,
                'average_percentage' => $average,
                'pass_percentage' => $pass,
                'attendance_percentage' => $attendancePercentage,
                'students_count' => (int) ($studentCounts->get($mappedClassId) ?? 0),
            ];
        })->values();

        return $this->rankRowsByMetric($rows, 'average_percentage');
    }

    public function attendanceTrend(string $session, ?int $classId = null): array
    {
        [$sessionStart, $sessionEnd] = $this->sessionDateRange($session);
        $driver = DB::connection()->getDriverName();
        $monthKeyExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', attendance.date)"
            : "DATE_FORMAT(attendance.date, '%Y-%m')";

        $monthKeys = [];
        $cursor = $sessionStart->copy();
        for ($i = 0; $i < 12; $i++) {
            $monthKeys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $rows = Attendance::query()
            ->join('students as s', 's.id', '=', 'attendance.student_id')
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->whereBetween('attendance.date', [$sessionStart->toDateString(), $sessionEnd->toDateString()])
            ->when($classId !== null, fn ($query) => $query->where('attendance.class_id', $classId))
            ->selectRaw($monthKeyExpression." as month_key")
            ->selectRaw("SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) as present_count")
            ->selectRaw('COUNT(*) as total_count')
            ->groupByRaw($monthKeyExpression)
            ->get()
            ->keyBy('month_key');

        $labels = [];
        $values = [];
        foreach ($monthKeys as $monthKey) {
            $monthRow = $rows->get($monthKey);
            $labels[] = Carbon::createFromFormat('Y-m', $monthKey)->format('M Y');

            if (! $monthRow || (int) $monthRow->total_count === 0) {
                $values[] = 0;
                continue;
            }

            $values[] = round(((int) $monthRow->present_count * 100.0) / (int) $monthRow->total_count, 2);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    public function examComparison(
        string $session,
        ?int $classId = null,
        ?int $teacherId = null,
        ?string $examType = null
    ): array
    {
        $exam = $this->normalizeExamType($examType);

        $rows = Mark::query()
            ->join('exams as e', 'e.id', '=', 'marks.exam_id')
            ->join('students as s', 's.id', '=', 'marks.student_id')
            ->where('marks.session', $session)
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->when($classId !== null, fn ($query) => $query->where('e.class_id', $classId))
            ->when($teacherId !== null, fn ($query) => $query->where('marks.teacher_id', $teacherId))
            ->when($exam !== null, fn ($query) => $query->where('e.exam_type', $exam))
            ->selectRaw("
                e.exam_type as exam_type,
                AVG((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as average_percentage,
                (
                    SUM(
                        CASE
                            WHEN ((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) >= ?
                            THEN 1
                            ELSE 0
                        END
                    ) * 100.0
                ) / NULLIF(COUNT(*), 0) as pass_percentage
            ", [self::PASS_PERCENTAGE])
            ->groupBy('e.exam_type')
            ->get()
            ->keyBy('exam_type');

        $typeOrder = ['class_test', 'bimonthly_test', 'first_term', 'final_term'];
        $labels = [
            'class_test' => 'Class Test',
            'bimonthly_test' => 'Bimonthly',
            'first_term' => '1st Term',
            'final_term' => 'Final Term',
        ];

        $chartLabels = [];
        $averageValues = [];
        $passValues = [];

        foreach ($typeOrder as $type) {
            $row = $rows->get($type);
            $chartLabels[] = $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
            $averageValues[] = $row ? round((float) ($row->average_percentage ?? 0), 2) : 0;
            $passValues[] = $row ? round((float) ($row->pass_percentage ?? 0), 2) : 0;
        }

        return [
            'labels' => $chartLabels,
            'average_values' => $averageValues,
            'pass_values' => $passValues,
        ];
    }

    private function kpis(
        string $session,
        ?int $classId,
        Collection $studentPerformance,
        Collection $attendanceByStudent
    ): array {
        $studentCount = $this->activeStudentsQuery($classId)->count();

        $resultValues = $studentPerformance
            ->pluck('percentage')
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): float => (float) $value)
            ->values();

        $attendanceValues = $attendanceByStudent
            ->pluck('attendance_percentage')
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): float => (float) $value)
            ->values();

        $passRate = null;
        if ($resultValues->isNotEmpty()) {
            $passRate = round(
                ($resultValues->filter(fn (float $value): bool => $value >= self::PASS_PERCENTAGE)->count() * 100.0)
                / $resultValues->count(),
                2
            );
        }

        return [
            'total_students' => $studentCount,
            'pass_rate' => $passRate,
            'average_attendance' => $attendanceValues->isNotEmpty() ? round((float) $attendanceValues->avg(), 2) : null,
            'fee_defaulters' => $this->feeDefaultersCount($session, $classId),
            'average_result_percentage' => $resultValues->isNotEmpty() ? round((float) $resultValues->avg(), 2) : null,
            'active_teachers' => $this->activeTeachersCount($session, $classId),
        ];
    }

    /**
     * @param Collection<int, array{
     *   student_id:int,
     *   student_code:string,
     *   student_name:string,
     *   class_id:int,
     *   class_name:string,
     *   percentage:float
     * }> $studentPerformance
     * @return array<int, array{
     *   student_id:int,
     *   student_name:string,
     *   class_name:string,
     *   percentage:float,
     *   rank:int|null
     * }>
     */
    private function topPerformers(Collection $studentPerformance, int $limit = 10): array
    {
        return $this->rankRowsByMetric($studentPerformance, 'percentage')
            ->take($limit)
            ->values()
            ->map(fn (array $row): array => [
                'student_id' => (int) $row['student_id'],
                'student_name' => (string) $row['student_name'],
                'class_name' => (string) $row['class_name'],
                'percentage' => round((float) $row['percentage'], 2),
                'rank' => isset($row['rank']) ? (int) $row['rank'] : null,
            ])
            ->all();
    }

    /**
     * @param Collection<int, array{student_id:int,attendance_percentage:float}> $attendanceByStudent
     * @return array<int, array{
     *   student_id:int,
     *   student_name:string,
     *   class_name:string,
     *   result_percentage:float|null,
     *   attendance_percentage:float|null,
     *   risk_level:string
     * }>
     */
    private function weakStudents(
        Collection $studentPerformance,
        Collection $attendanceByStudent,
        ?int $classId = null,
        int $limit = 12
    ): array
    {
        $performanceMap = $studentPerformance->keyBy('student_id');

        $rows = $this->activeStudentsQuery($classId)
            ->with('classRoom:id,name,section')
            ->orderBy('name')
            ->get(['id', 'student_id', 'name', 'class_id'])
            ->map(function (Student $student) use ($performanceMap, $attendanceByStudent): array {
                $performance = $performanceMap->get((int) $student->id);
                $attendance = $attendanceByStudent->get((int) $student->id);

                $resultPercentage = $performance['percentage'] ?? null;
                $attendancePercentage = $attendance['attendance_percentage'] ?? null;
                $riskLevel = $this->riskLevel(
                    $resultPercentage !== null ? (float) $resultPercentage : null,
                    $attendancePercentage !== null ? (float) $attendancePercentage : null
                );

                return [
                    'student_id' => (int) $student->id,
                    'student_name' => (string) $student->name,
                    'class_name' => $this->classLabel(
                        (string) ($student->classRoom?->name ?? 'Class'),
                        $student->classRoom?->section
                    ),
                    'result_percentage' => $resultPercentage !== null ? round((float) $resultPercentage, 2) : null,
                    'attendance_percentage' => $attendancePercentage !== null ? round((float) $attendancePercentage, 2) : null,
                    'risk_level' => $riskLevel,
                ];
            })
            ->filter(fn (array $row): bool => in_array($row['risk_level'], ['high', 'medium'], true))
            ->sort(function (array $left, array $right): int {
                $riskDiff = $this->riskWeight($right['risk_level']) <=> $this->riskWeight($left['risk_level']);
                if ($riskDiff !== 0) {
                    return $riskDiff;
                }

                $leftResult = $left['result_percentage'] ?? 0;
                $rightResult = $right['result_percentage'] ?? 0;
                if ($leftResult !== $rightResult) {
                    return $leftResult <=> $rightResult;
                }

                $leftAttendance = $left['attendance_percentage'] ?? 0;
                $rightAttendance = $right['attendance_percentage'] ?? 0;
                if ($leftAttendance !== $rightAttendance) {
                    return $leftAttendance <=> $rightAttendance;
                }

                return strcasecmp($left['student_name'], $right['student_name']);
            })
            ->values()
            ->take($limit);

        return $rows->all();
    }

    private function rankRowsByMetric(Collection $rows, string $metricKey, string $rankKey = 'rank'): Collection
    {
        $sorted = $rows
            ->sort(function (array $left, array $right) use ($metricKey): int {
                $leftValue = $left[$metricKey] ?? null;
                $rightValue = $right[$metricKey] ?? null;

                if ($leftValue === null && $rightValue === null) {
                    return 0;
                }
                if ($leftValue === null) {
                    return 1;
                }
                if ($rightValue === null) {
                    return -1;
                }

                $leftFloat = (float) $leftValue;
                $rightFloat = (float) $rightValue;
                if ($leftFloat === $rightFloat) {
                    return 0;
                }

                return $leftFloat < $rightFloat ? 1 : -1;
            })
            ->values();

        $rank = 0;
        $index = 0;
        $lastMetric = null;

        return $sorted->map(function (array $row) use ($metricKey, $rankKey, &$rank, &$index, &$lastMetric): array {
            $index++;
            $metric = $row[$metricKey] ?? null;

            if ($metric === null) {
                $row[$rankKey] = null;

                return $row;
            }

            $metricFloat = (float) $metric;
            if ($lastMetric === null || $metricFloat !== $lastMetric) {
                $rank = $index;
                $lastMetric = $metricFloat;
            }

            $row[$rankKey] = $rank;

            return $row;
        });
    }

    private function riskLevel(?float $resultPercentage, ?float $attendancePercentage): string
    {
        $result = $resultPercentage ?? 0.0;
        $attendance = $attendancePercentage ?? 0.0;

        if ($result < 50 || $attendance < 70) {
            return 'high';
        }

        if ($result < 60 || $attendance < 80) {
            return 'medium';
        }

        return 'low';
    }

    private function riskWeight(string $riskLevel): int
    {
        return match ($riskLevel) {
            'high' => 3,
            'medium' => 2,
            default => 1,
        };
    }

    private function difficultyFromAverage(?float $averagePercentage): string
    {
        if ($averagePercentage === null) {
            return 'moderate';
        }

        if ($averagePercentage >= 75) {
            return 'easy';
        }

        if ($averagePercentage >= 60) {
            return 'moderate';
        }

        return 'hard';
    }

    private function difficultyWeight(string $difficulty): int
    {
        return match ($difficulty) {
            'hard' => 3,
            'moderate' => 2,
            default => 1,
        };
    }

    private function classLabel(string $name, ?string $section): string
    {
        return trim($name.' '.(string) ($section ?? ''));
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

    private function feeDefaultersCount(string $session, ?int $classId = null): int
    {
        if (! Schema::hasTable('fee_defaulters')) {
            return 0;
        }

        return FeeDefaulter::query()
            ->join('students as s', 's.id', '=', 'fee_defaulters.student_id')
            ->where('fee_defaulters.session', $session)
            ->where('fee_defaulters.is_active', true)
            ->where('fee_defaulters.total_due', '>', 0)
            ->whereNull('s.deleted_at')
            ->where('s.status', 'active')
            ->when($classId !== null, fn ($query) => $query->where('s.class_id', $classId))
            ->count();
    }

    private function activeTeachersCount(string $session, ?int $classId = null): int
    {
        return (int) TeacherSubjectAssignment::query()
            ->join('teachers as t', 't.id', '=', 'teacher_subject_assignments.teacher_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('teacher_subject_assignments.session', $session)
            ->when($classId !== null, fn ($query) => $query->where('teacher_subject_assignments.class_id', $classId))
            ->where(function ($query): void {
                $query->whereNull('u.status')
                    ->orWhere('u.status', 'active');
            })
            ->count(DB::raw('DISTINCT teacher_subject_assignments.teacher_id'));
    }

    private function activeStudentsQuery(?int $classId = null): Builder
    {
        return Student::query()
            ->where('status', 'active')
            ->when($classId !== null, fn (Builder $query) => $query->where('class_id', $classId));
    }

    /**
     * @return Collection<int, string>
     */
    private function classMap(?int $classId = null): Collection
    {
        return SchoolClass::query()
            ->when($classId !== null, fn (Builder $query) => $query->where('id', $classId))
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->mapWithKeys(fn (SchoolClass $classRoom): array => [
                (int) $classRoom->id => $this->classLabel((string) $classRoom->name, $classRoom->section),
            ]);
    }

    private function normalizeExamType(?string $examType): ?string
    {
        $candidate = trim((string) $examType);
        if ($candidate === '') {
            return null;
        }

        return in_array($candidate, self::EXAM_TYPES, true) ? $candidate : null;
    }
}
