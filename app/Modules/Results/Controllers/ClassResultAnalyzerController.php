<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Modules\Exams\Enums\ExamType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassResultAnalyzerController extends Controller
{
    private const PASS_PERCENTAGE = 60.0;
    private const RISK_LOW_THRESHOLD = 40.0;
    private const RISK_HIGH_THRESHOLD = 70.0;

    public function index(Request $request): View
    {
        $user = $request->user();
        $isPrincipal = $user?->hasRole('Principal') ?? false;
        $isTeacher = $user?->hasRole('Teacher') ?? false;

        if (! $isPrincipal && ! $isTeacher) {
            abort(403, 'You are not authorized to access the class result analyzer.');
        }

        $teacher = null;
        if ($isTeacher) {
            $teacher = Teacher::query()->where('user_id', (int) $user->id)->first();
            if (! $teacher) {
                abort(403, 'Teacher profile not found.');
            }
        }

        $classes = $this->classesForUser($isPrincipal, $teacher?->id);
        if (! $isPrincipal && $isTeacher && $classes->isEmpty()) {
            abort(403, 'Only class teachers can access the analyzer.');
        }

        $sessions = $this->availableSessions();
        $examTypes = ExamType::options();
        $examTypeValues = array_column($examTypes, 'value');

        $selectedSession = $request->filled('session') && in_array((string) $request->input('session'), $sessions, true)
            ? (string) $request->input('session')
            : ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $defaultExamType = in_array('final_term', $examTypeValues, true)
            ? 'final_term'
            : ($examTypeValues[0] ?? 'first_term');

        $selectedExamType = $request->filled('exam_type') && in_array((string) $request->input('exam_type'), $examTypeValues, true)
            ? (string) $request->input('exam_type')
            : $defaultExamType;

        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        if ($selectedClassId !== null && ! $classes->contains('id', $selectedClassId)) {
            abort(403, 'You are not allowed to analyze this class.');
        }

        if ($selectedClassId === null && $classes->isNotEmpty()) {
            $selectedClassId = (int) $classes->first()->id;
        }

        $selectedClass = $selectedClassId !== null
            ? $classes->firstWhere('id', $selectedClassId)
            : null;

        if (
            $selectedClass !== null
            && ! $isPrincipal
            && $isTeacher
            && (int) ($teacher?->id ?? 0) !== (int) ($selectedClass->class_teacher_id ?? 0)
        ) {
            abort(403, 'You can access analyzer only for classes where you are class teacher.');
        }

        $analysis = $selectedClassId !== null
            ? $this->analyze((int) $selectedClassId, $selectedSession, $selectedExamType)
            : $this->emptyAnalysis();

        return view('modules.results.analyzer.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'examTypes' => $examTypes,
            'selectedClassId' => $selectedClassId,
            'selectedSession' => $selectedSession,
            'selectedExamType' => $selectedExamType,
            'selectedClassLabel' => $selectedClass ? trim($selectedClass->name.' '.($selectedClass->section ?? '')) : null,
            'passPercentageThreshold' => self::PASS_PERCENTAGE,
            'analysis' => $analysis,
        ]);
    }

    private function classesForUser(bool $isPrincipal, ?int $teacherId)
    {
        $query = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section');

        if (! $isPrincipal && $teacherId !== null) {
            $query->where('class_teacher_id', (int) $teacherId);
        }

        return $query->get(['id', 'name', 'section', 'class_teacher_id']);
    }

    private function availableSessions(): array
    {
        $sessions = Exam::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();

        if ($sessions !== []) {
            return $sessions;
        }

        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        $fallback = [];
        for ($year = $startYear - 1; $year <= $startYear + 3; $year++) {
            $fallback[] = $year.'-'.($year + 1);
        }

        return array_reverse($fallback);
    }

    private function emptyAnalysis(): array
    {
        return [
            'summary' => [
                'students' => 0,
                'pass_percentage' => 0.0,
                'class_average' => 0.0,
                'highest' => 0.0,
                'lowest' => 0.0,
            ],
            'subject_performance' => [],
            'subject_difficulty_heatmap' => [],
            'student_risk_predictions' => [],
            'teacher_effectiveness_ranking' => [],
            'progress_tracking' => [
                'items' => [
                    ['exam_type' => 'class_test', 'label' => 'Class Test', 'average_percentage' => 0.0],
                    ['exam_type' => 'bimonthly_test', 'label' => 'Bimonthly', 'average_percentage' => 0.0],
                    ['exam_type' => 'first_term', 'label' => 'First Term', 'average_percentage' => 0.0],
                    ['exam_type' => 'final_term', 'label' => 'Final', 'average_percentage' => 0.0],
                ],
                'improvement' => 0.0,
            ],
            'top_students' => [],
            'weak_students' => [],
            'charts' => [
                'subject_difficulty' => [
                    'labels' => [],
                    'values' => [],
                    'colors' => [],
                ],
                'student_risk' => [
                    'labels' => ['Low', 'Medium', 'High'],
                    'values' => [0, 0, 0],
                ],
                'student_performance_trend' => [
                    'labels' => ['Class Test', 'Bimonthly', 'First Term', 'Final'],
                    'values' => [0, 0, 0, 0],
                ],
            ],
        ];
    }

    private function analyze(int $classId, string $session, string $examType): array
    {
        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'name', 'student_id', 'class_id']);

        $subjects = SchoolClass::query()
            ->findOrFail($classId)
            ->subjects()
            ->orderBy('name')
            ->get(['subjects.id', 'subjects.name']);

        $subjectIds = $subjects->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        $studentIds = $students->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $selectedExams = Exam::query()
            ->with(['teacher.user:id,name'])
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('exam_type', $examType)
            ->when($subjectIds !== [], fn ($query) => $query->whereIn('subject_id', $subjectIds))
            ->get(['id', 'subject_id', 'teacher_id', 'total_marks', 'class_id', 'session', 'exam_type']);

        $selectedExamLookup = $selectedExams->keyBy('id');
        $selectedExamIds = $selectedExams->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $selectedMarks = Mark::query()
            ->with('student:id,name,student_id,class_id')
            ->when($selectedExamIds !== [], fn ($query) => $query->whereIn('exam_id', $selectedExamIds), fn ($query) => $query->whereRaw('1=0'))
            ->when($studentIds !== [], fn ($query) => $query->whereIn('student_id', $studentIds), fn ($query) => $query->whereRaw('1=0'))
            ->get(['id', 'exam_id', 'student_id', 'obtained_marks', 'total_marks', 'session', 'teacher_id']);

        $sessionExams = Exam::query()
            ->where('class_id', $classId)
            ->where('session', $session)
            ->when($subjectIds !== [], fn ($query) => $query->whereIn('subject_id', $subjectIds))
            ->get(['id', 'subject_id', 'teacher_id', 'total_marks', 'class_id', 'session', 'exam_type']);

        $sessionExamLookup = $sessionExams->keyBy('id');
        $sessionExamIds = $sessionExams->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $sessionMarks = Mark::query()
            ->when($sessionExamIds !== [], fn ($query) => $query->whereIn('exam_id', $sessionExamIds), fn ($query) => $query->whereRaw('1=0'))
            ->when($studentIds !== [], fn ($query) => $query->whereIn('student_id', $studentIds), fn ($query) => $query->whereRaw('1=0'))
            ->get(['id', 'exam_id', 'student_id', 'obtained_marks', 'total_marks', 'session', 'teacher_id']);

        $subjectTeacherMap = TeacherAssignment::query()
            ->with('teacher.user:id,name')
            ->where('class_id', $classId)
            ->where('session', $session)
            ->when($subjectIds !== [], fn ($query) => $query->whereIn('subject_id', $subjectIds))
            ->whereNotNull('subject_id')
            ->get(['teacher_id', 'subject_id'])
            ->groupBy('subject_id')
            ->map(function ($rows): string {
                return $rows
                    ->map(fn ($row) => $row->teacher?->user?->name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
            });

        $examTeacherMap = $selectedExams
            ->groupBy('subject_id')
            ->map(function ($rows): string {
                return $rows
                    ->map(fn ($row) => $row->teacher?->user?->name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
            });

        $marksBySubject = [];
        $marksByStudent = [];
        $selectedPercentagesByTeacher = [];
        foreach ($selectedMarks as $mark) {
            $exam = $selectedExamLookup->get((int) $mark->exam_id);
            if (! $exam) {
                continue;
            }

            $subjectId = (int) $exam->subject_id;
            $studentId = (int) $mark->student_id;
            $teacherId = (int) ($mark->teacher_id ?: $exam->teacher_id ?: 0);
            $percentage = $this->markPercentage($mark, $selectedExamLookup);

            $marksBySubject[$subjectId] = $marksBySubject[$subjectId] ?? collect();
            $marksBySubject[$subjectId]->push($mark);
            $marksByStudent[$studentId] = $marksByStudent[$studentId] ?? collect();
            $marksByStudent[$studentId]->push($mark);

            if ($teacherId > 0) {
                $selectedPercentagesByTeacher[$teacherId] = $selectedPercentagesByTeacher[$teacherId] ?? [];
                $selectedPercentagesByTeacher[$teacherId][] = $percentage;
            }
        }

        $sessionPercentagesByTeacherExamType = [];
        $sessionPercentagesByExamType = [];
        foreach ($sessionMarks as $mark) {
            $exam = $sessionExamLookup->get((int) $mark->exam_id);
            if (! $exam) {
                continue;
            }

            $teacherId = (int) ($mark->teacher_id ?: $exam->teacher_id ?: 0);
            if ($teacherId <= 0) {
                continue;
            }

            $examTypeKey = (string) $exam->exam_type;
            $percentage = $this->markPercentage($mark, $sessionExamLookup);
            $sessionPercentagesByExamType[$examTypeKey] = $sessionPercentagesByExamType[$examTypeKey] ?? [];
            $sessionPercentagesByExamType[$examTypeKey][] = $percentage;
            $sessionPercentagesByTeacherExamType[$teacherId] = $sessionPercentagesByTeacherExamType[$teacherId] ?? [];
            $sessionPercentagesByTeacherExamType[$teacherId][$examTypeKey] = $sessionPercentagesByTeacherExamType[$teacherId][$examTypeKey] ?? [];
            $sessionPercentagesByTeacherExamType[$teacherId][$examTypeKey][] = $percentage;
        }

        $progressTracking = $this->buildProgressTracking($sessionPercentagesByExamType);

        $subjectPerformance = $subjects->map(function ($subject) use ($marksBySubject, $selectedExamLookup, $subjectTeacherMap, $examTeacherMap): array {
            $rows = $marksBySubject[(int) $subject->id] ?? collect();
            $percentages = $rows->map(fn (Mark $mark): float => $this->markPercentage($mark, $selectedExamLookup));

            $avg = $percentages->isEmpty() ? 0.0 : round((float) $percentages->avg(), 2);
            $passCount = $percentages->filter(fn (float $value): bool => $value >= self::PASS_PERCENTAGE)->count();
            $passPercentage = $percentages->count() > 0
                ? round(($passCount / $percentages->count()) * 100, 2)
                : 0.0;
            $difficultyBand = $this->difficultyBand($avg);

            return [
                'subject_id' => (int) $subject->id,
                'subject' => (string) $subject->name,
                'average_percentage' => $avg,
                'pass_percentage' => $passPercentage,
                'teacher' => $subjectTeacherMap->get((int) $subject->id)
                    ?: $examTeacherMap->get((int) $subject->id)
                    ?: '-',
                'difficulty' => $difficultyBand,
                'difficulty_color' => $this->difficultyColor($difficultyBand),
                'is_low' => $avg < 60,
            ];
        })->values();

        $subjectDifficultyHeatmap = $subjectPerformance
            ->map(function (array $row): array {
                return [
                    'subject' => (string) $row['subject'],
                    'average_percentage' => (float) $row['average_percentage'],
                    'difficulty' => (string) $row['difficulty'],
                    'difficulty_color' => (string) $row['difficulty_color'],
                    'teacher' => (string) $row['teacher'],
                    'pass_percentage' => (float) $row['pass_percentage'],
                ];
            })
            ->values();

        $attendanceByStudent = $this->attendancePercentageMap($classId, $session, $studentIds);
        $totalSubjects = max(1, $subjects->count());

        $studentRows = $students->map(function (Student $student) use (
            $marksByStudent,
            $selectedExamLookup,
            $attendanceByStudent,
            $totalSubjects
        ): array {
            $rows = $marksByStudent[(int) $student->id] ?? collect();
            $subjectPercentages = $rows->map(fn (Mark $mark): float => $this->markPercentage($mark, $selectedExamLookup));

            $totalMarks = (float) $rows->sum(function (Mark $mark) use ($selectedExamLookup): float {
                $exam = $selectedExamLookup->get((int) $mark->exam_id);

                return (float) ($mark->total_marks ?: $exam?->total_marks ?: 0);
            });
            $obtainedMarks = (float) $rows->sum(fn (Mark $mark): float => (float) $mark->obtained_marks);
            $percentage = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0.0;
            $failedSubjectsCount = $subjectPercentages
                ->filter(fn (float $value): bool => $value < self::PASS_PERCENTAGE)
                ->count();
            $attendancePercentage = (float) ($attendanceByStudent[(int) $student->id]['attendance_percentage'] ?? 0.0);
            $riskScore = $this->riskScore($percentage, $attendancePercentage, $failedSubjectsCount, $totalSubjects);
            $riskLevel = $this->riskLevel($riskScore);

            return [
                'student_id' => (int) $student->id,
                'student_name' => (string) $student->name,
                'student_ref' => (string) $student->student_id,
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
                'percentage' => $percentage,
                'grade' => $this->grade($percentage),
                'is_pass' => $percentage >= self::PASS_PERCENTAGE,
                'attendance_percentage' => $attendancePercentage,
                'failed_subjects_count' => $failedSubjectsCount,
                'risk_score' => $riskScore,
                'risk_level' => $riskLevel,
            ];
        })->values();

        $studentsCount = $studentRows->count();
        $passCount = $studentRows->filter(fn (array $row): bool => (bool) $row['is_pass'])->count();
        $passPercentage = $studentsCount > 0 ? round(($passCount / $studentsCount) * 100, 2) : 0.0;
        $classAverage = $studentsCount > 0 ? round((float) $studentRows->avg('percentage'), 2) : 0.0;
        $highest = $studentsCount > 0 ? round((float) $studentRows->max('percentage'), 2) : 0.0;
        $lowest = $studentsCount > 0 ? round((float) $studentRows->min('percentage'), 2) : 0.0;

        $topStudents = $studentRows
            ->filter(fn (array $row): bool => (float) $row['total_marks'] > 0)
            ->sortByDesc('percentage')
            ->take(5)
            ->values()
            ->all();

        $weakStudents = $studentRows
            ->filter(fn (array $row): bool => (float) $row['percentage'] < self::PASS_PERCENTAGE)
            ->sortBy('percentage')
            ->values()
            ->all();

        $studentRiskPredictions = $studentRows
            ->sortByDesc('risk_score')
            ->map(function (array $row): array {
                return [
                    'student_id' => (int) $row['student_id'],
                    'student_name' => (string) $row['student_name'],
                    'student_ref' => (string) $row['student_ref'],
                    'average_score' => (float) $row['percentage'],
                    'attendance_percentage' => (float) $row['attendance_percentage'],
                    'failed_subjects_count' => (int) $row['failed_subjects_count'],
                    'risk_score' => (float) $row['risk_score'],
                    'risk_level' => (string) $row['risk_level'],
                ];
            })
            ->values()
            ->all();

        $riskCounts = [
            'Low' => 0,
            'Medium' => 0,
            'High' => 0,
        ];
        foreach ($studentRiskPredictions as $prediction) {
            $level = (string) ($prediction['risk_level'] ?? '');
            if (array_key_exists($level, $riskCounts)) {
                $riskCounts[$level]++;
            }
        }

        $teacherAssignments = TeacherAssignment::query()
            ->with(['teacher.user:id,name', 'subject:id,name'])
            ->where('class_id', $classId)
            ->where('session', $session)
            ->whereNotNull('teacher_id')
            ->get(['teacher_id', 'subject_id', 'class_id', 'session']);

        $teacherEffectivenessRanking = $teacherAssignments
            ->groupBy('teacher_id')
            ->map(function ($rows, $teacherId) use ($selectedPercentagesByTeacher, $sessionPercentagesByTeacherExamType, $examType): array {
                $teacherId = (int) $teacherId;
                $teacherName = (string) ($rows->first()?->teacher?->user?->name ?: ('Teacher #'.$teacherId));

                $selectedPercentages = collect($selectedPercentagesByTeacher[$teacherId] ?? []);
                $avgScore = $selectedPercentages->isNotEmpty()
                    ? round((float) $selectedPercentages->avg(), 2)
                    : 0.0;
                $passRate = $selectedPercentages->isNotEmpty()
                    ? round(($selectedPercentages->filter(fn (float $value): bool => $value >= self::PASS_PERCENTAGE)->count() / $selectedPercentages->count()) * 100, 2)
                    : 0.0;
                $improvement = $this->teacherImprovement(
                    $sessionPercentagesByTeacherExamType[$teacherId] ?? [],
                    $examType
                );
                $score = round(($avgScore * 0.5) + ($passRate * 0.3) + ($improvement * 0.2), 2);

                return [
                    'teacher_id' => $teacherId,
                    'teacher_name' => $teacherName,
                    'avg_score' => $avgScore,
                    'pass_rate' => $passRate,
                    'improvement' => $improvement,
                    'effectiveness_score' => $score,
                    'subjects' => $rows
                        ->map(fn ($row) => $row->subject?->name)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->sortByDesc('effectiveness_score')
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                return $row;
            })
            ->all();

        return [
            'summary' => [
                'students' => $studentsCount,
                'pass_percentage' => $passPercentage,
                'class_average' => $classAverage,
                'highest' => $highest,
                'lowest' => $lowest,
            ],
            'subject_performance' => $subjectPerformance->all(),
            'subject_difficulty_heatmap' => $subjectDifficultyHeatmap->all(),
            'student_risk_predictions' => $studentRiskPredictions,
            'teacher_effectiveness_ranking' => $teacherEffectivenessRanking,
            'progress_tracking' => $progressTracking,
            'top_students' => $topStudents,
            'weak_students' => $weakStudents,
            'charts' => [
                'subject_difficulty' => [
                    'labels' => $subjectDifficultyHeatmap->pluck('subject')->values()->all(),
                    'values' => $subjectDifficultyHeatmap->pluck('average_percentage')->map(fn ($value): float => round((float) $value, 2))->values()->all(),
                    'colors' => $subjectDifficultyHeatmap->pluck('difficulty_color')->values()->all(),
                ],
                'student_risk' => [
                    'labels' => ['Low', 'Medium', 'High'],
                    'values' => [
                        (int) $riskCounts['Low'],
                        (int) $riskCounts['Medium'],
                        (int) $riskCounts['High'],
                    ],
                ],
                'student_performance_trend' => [
                    'labels' => collect($progressTracking['items'])->pluck('label')->values()->all(),
                    'values' => collect($progressTracking['items'])->pluck('average_percentage')->map(fn ($value): float => round((float) $value, 2))->values()->all(),
                ],
            ],
        ];
    }

    private function buildProgressTracking(array $sessionPercentagesByExamType): array
    {
        $examTypeOrder = ['class_test', 'bimonthly_test', 'first_term', 'final_term'];
        $examTypeLabels = [
            'class_test' => 'Class Test',
            'bimonthly_test' => 'Bimonthly',
            'first_term' => 'First Term',
            'final_term' => 'Final',
        ];

        $items = collect($examTypeOrder)
            ->map(function (string $examType) use ($sessionPercentagesByExamType, $examTypeLabels): array {
                $values = array_map(
                    fn ($value): float => (float) $value,
                    $sessionPercentagesByExamType[$examType] ?? []
                );

                return [
                    'exam_type' => $examType,
                    'label' => $examTypeLabels[$examType] ?? ucfirst(str_replace('_', ' ', $examType)),
                    'average_percentage' => $values === []
                        ? 0.0
                        : round(array_sum($values) / count($values), 2),
                ];
            })
            ->values();

        $validItems = $items
            ->filter(fn (array $row): bool => (float) $row['average_percentage'] > 0)
            ->values();

        $improvement = 0.0;
        if ($validItems->count() >= 2) {
            $first = (float) ($validItems->first()['average_percentage'] ?? 0.0);
            $last = (float) ($validItems->last()['average_percentage'] ?? 0.0);
            $improvement = round($last - $first, 2);
        }

        return [
            'items' => $items->all(),
            'improvement' => $improvement,
        ];
    }

    private function markPercentage(Mark $mark, $examLookup): float
    {
        $exam = $examLookup->get((int) $mark->exam_id);
        $total = (float) ($mark->total_marks ?: $exam?->total_marks ?: 0);
        $obtained = (float) $mark->obtained_marks;

        return $total > 0 ? round(($obtained / $total) * 100, 2) : 0.0;
    }

    private function attendancePercentageMap(int $classId, string $session, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        [$startDate, $endDate] = $this->sessionDateRange($session);
        if (! $startDate || ! $endDate) {
            return [];
        }

        $rows = Attendance::query()
            ->where('class_id', $classId)
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['student_id', 'status']);

        $grouped = $rows->groupBy('student_id');
        $map = [];
        foreach ($studentIds as $studentId) {
            $studentRows = $grouped->get($studentId, collect());
            $total = $studentRows->count();
            $present = $studentRows
                ->filter(function ($row): bool {
                    $status = strtolower((string) $row->status);

                    return in_array($status, ['present', 'p'], true);
                })
                ->count();

            $map[$studentId] = [
                'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0.0,
            ];
        }

        return $map;
    }

    private function sessionDateRange(string $session): array
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $session, $matches)) {
            return [null, null];
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        if ($endYear !== ($startYear + 1)) {
            return [null, null];
        }

        return [
            Carbon::create($startYear, 7, 1)->startOfDay(),
            Carbon::create($endYear, 6, 30)->endOfDay(),
        ];
    }

    private function riskScore(float $averageScore, float $attendancePercentage, int $failedSubjectsCount, int $totalSubjects): float
    {
        $averageRisk = max(0.0, min(100.0, 100.0 - $averageScore));
        $attendanceRisk = max(0.0, min(100.0, 100.0 - $attendancePercentage));
        $failureRisk = max(0.0, min(100.0, ($failedSubjectsCount / max(1, $totalSubjects)) * 100.0));

        $score = ($averageRisk * 0.5) + ($attendanceRisk * 0.3) + ($failureRisk * 0.2);

        return round(max(0.0, min(100.0, $score)), 2);
    }

    private function riskLevel(float $riskScore): string
    {
        if ($riskScore >= self::RISK_HIGH_THRESHOLD) {
            return 'High';
        }

        if ($riskScore >= self::RISK_LOW_THRESHOLD) {
            return 'Medium';
        }

        return 'Low';
    }

    private function difficultyBand(float $averagePercentage): string
    {
        if ($averagePercentage >= 75) {
            return 'Easy';
        }
        if ($averagePercentage >= 60) {
            return 'Moderate';
        }

        return 'Hard';
    }

    private function difficultyColor(string $difficulty): string
    {
        return match ($difficulty) {
            'Easy' => '#16a34a',
            'Moderate' => '#d97706',
            default => '#dc2626',
        };
    }

    private function teacherImprovement(array $examTypePercentages, string $selectedExamType): float
    {
        $examTypeOrder = ['class_test', 'bimonthly_test', 'first_term', 'final_term'];
        $averages = [];
        foreach ($examTypePercentages as $type => $values) {
            $values = array_map(fn ($value): float => (float) $value, (array) $values);
            if ($values === []) {
                continue;
            }

            $averages[(string) $type] = round(array_sum($values) / count($values), 2);
        }

        if (isset($averages['first_term'], $averages['final_term'])) {
            return round((float) $averages['final_term'] - (float) $averages['first_term'], 2);
        }

        if (isset($averages[$selectedExamType])) {
            $selectedIndex = array_search($selectedExamType, $examTypeOrder, true);
            if ($selectedIndex !== false) {
                for ($index = $selectedIndex - 1; $index >= 0; $index--) {
                    $previousType = $examTypeOrder[$index];
                    if (isset($averages[$previousType])) {
                        return round((float) $averages[$selectedExamType] - (float) $averages[$previousType], 2);
                    }
                }
            }
        }

        $orderedAverages = [];
        foreach ($examTypeOrder as $type) {
            if (isset($averages[$type])) {
                $orderedAverages[] = (float) $averages[$type];
            }
        }

        if (count($orderedAverages) >= 2) {
            $first = $orderedAverages[0];
            $last = $orderedAverages[count($orderedAverages) - 1];

            return round($last - $first, 2);
        }

        return 0.0;
    }

    private function grade(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'A*';
        }
        if ($percentage >= 80) {
            return 'A';
        }
        if ($percentage >= 70) {
            return 'B+';
        }
        if ($percentage >= 60) {
            return 'B';
        }

        return 'Fail';
    }
}
