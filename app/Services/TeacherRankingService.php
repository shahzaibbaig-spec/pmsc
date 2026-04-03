<?php

namespace App\Services;

use App\Models\TeacherAssignment;
use App\Models\TeacherCgpaRanking;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherRankingService
{
    private const PASS_PERCENTAGE = 40.0;

    /**
     * @var array<int, string>
     */
    private const ALLOWED_EXAM_TYPES = [
        'class_test',
        'bimonthly_test',
        'first_term',
        'final_term',
    ];

    public function __construct(private readonly ClassAssessmentModeService $assessmentModeService)
    {
    }

    /**
     * @return array<int, string>
     */
    public function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $currentStartYear = $this->currentSessionStartYear();
        $sessions = [];

        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
    }

    public function resolveSession(?string $session): string
    {
        $sessions = $this->sessionOptions();
        $fallback = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));
        $candidate = trim((string) $session);

        if ($candidate === '') {
            return $fallback;
        }

        if (! preg_match('/^(\d{4})-(\d{4})$/', $candidate, $matches)) {
            return $fallback;
        }

        if ((int) $matches[2] !== ((int) $matches[1] + 1)) {
            return $fallback;
        }

        return $candidate;
    }

    public function normalizeExamType(?string $examType): ?string
    {
        $candidate = trim((string) $examType);

        if ($candidate === '' || $candidate === 'overall') {
            return null;
        }

        if ($candidate === 'bimonthly') {
            return 'bimonthly_test';
        }

        return in_array($candidate, self::ALLOWED_EXAM_TYPES, true) ? $candidate : null;
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    public function examTypeOptions(): array
    {
        return [
            ['value' => 'overall', 'label' => 'Overall'],
            ['value' => 'class_test', 'label' => 'Class Test'],
            ['value' => 'bimonthly', 'label' => 'Bimonthly'],
            ['value' => 'first_term', 'label' => '1st Term'],
            ['value' => 'final_term', 'label' => 'Final Term'],
        ];
    }

    public function examTypeSelectionValue(?string $examType): string
    {
        $normalized = $this->normalizeExamType($examType);

        return match ($normalized) {
            null => 'overall',
            'bimonthly_test' => 'bimonthly',
            default => $normalized,
        };
    }

    public function examTypeLabel(?string $examType): string
    {
        return match ($this->normalizeExamType($examType)) {
            'class_test' => 'Class Test',
            'bimonthly_test' => 'Bimonthly',
            'first_term' => '1st Term',
            'final_term' => 'Final Term',
            default => 'Overall',
        };
    }

    public function convertPercentageToCgpa(float $percentage): float
    {
        $normalized = max(0.0, min(100.0, $percentage));

        return round(($normalized / 100) * 6, 2);
    }

    public function calculateTeacherClasswiseCgpa(string $session, ?string $examType = null): array
    {
        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);

        return $this->buildClasswiseRows($resolvedSession, $resolvedExamType);
    }

    public function calculateTeacherOverallCgpa(string $session, ?string $examType = null): array
    {
        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);

        return $this->buildOverallRows(
            $this->buildClasswiseRows($resolvedSession, $resolvedExamType),
            $resolvedSession,
            $resolvedExamType
        );
    }

    public function storeTeacherCgpaRankings(string $session, ?string $examType = null): void
    {
        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);

        $classwiseRows = $this->buildClasswiseRows($resolvedSession, $resolvedExamType);
        $rankedClasswiseRows = collect($classwiseRows)
            ->groupBy('class_id')
            ->flatMap(fn (Collection $rows): array => $this->rankTeachers($rows->all()))
            ->values()
            ->all();

        $rankedOverallRows = $this->rankTeachers(
            $this->buildOverallRows($classwiseRows, $resolvedSession, $resolvedExamType)
        );

        $incomingRows = array_merge($rankedClasswiseRows, $rankedOverallRows);

        DB::transaction(function () use ($resolvedSession, $resolvedExamType, $incomingRows): void {
            $existingGroups = $this->snapshotQuery($resolvedSession, $resolvedExamType)
                ->get()
                ->groupBy(fn (TeacherCgpaRanking $ranking): string => $this->snapshotKey(
                    (int) $ranking->teacher_id,
                    (string) $ranking->ranking_scope,
                    $ranking->class_id !== null ? (int) $ranking->class_id : null
                ));

            foreach ($incomingRows as $row) {
                $key = $this->snapshotKey(
                    (int) $row['teacher_id'],
                    (string) $row['ranking_scope'],
                    isset($row['class_id']) ? (int) $row['class_id'] : null
                );

                /** @var Collection<int, TeacherCgpaRanking> $matches */
                $matches = $existingGroups->pull($key, collect());
                /** @var TeacherCgpaRanking|null $ranking */
                $ranking = $matches->shift();

                $payload = [
                    'teacher_id' => (int) $row['teacher_id'],
                    'session' => $resolvedSession,
                    'exam_type' => $resolvedExamType,
                    'class_id' => isset($row['class_id']) ? (int) $row['class_id'] : null,
                    'average_percentage' => round((float) $row['average_percentage'], 2),
                    'cgpa' => round((float) $row['cgpa'], 2),
                    'student_count' => (int) $row['student_count'],
                    'rank_position' => isset($row['rank_position']) ? (int) $row['rank_position'] : null,
                    'ranking_scope' => (string) $row['ranking_scope'],
                ];

                if ($ranking !== null) {
                    $ranking->fill($payload);
                    $ranking->save();
                } else {
                    TeacherCgpaRanking::query()->create($payload);
                }

                $matches->each->delete();
            }

            $existingGroups->flatten(1)->each->delete();
        });
    }

    public function rankTeachers(array $rows): array
    {
        usort($rows, function (array $left, array $right): int {
            $comparison = $this->compareDesc((float) ($left['cgpa'] ?? 0), (float) ($right['cgpa'] ?? 0));
            if ($comparison !== 0) {
                return $comparison;
            }

            $comparison = $this->compareDesc(
                (float) ($left['average_percentage'] ?? 0),
                (float) ($right['average_percentage'] ?? 0)
            );
            if ($comparison !== 0) {
                return $comparison;
            }

            $comparison = $this->compareDesc(
                (float) ($left['pass_percentage'] ?? 0),
                (float) ($right['pass_percentage'] ?? 0)
            );
            if ($comparison !== 0) {
                return $comparison;
            }

            $comparison = $this->compareDesc(
                (int) ($left['student_count'] ?? 0),
                (int) ($right['student_count'] ?? 0)
            );
            if ($comparison !== 0) {
                return $comparison;
            }

            $comparison = strcasecmp((string) ($left['teacher_name'] ?? ''), (string) ($right['teacher_name'] ?? ''));
            if ($comparison !== 0) {
                return $comparison;
            }

            return ((int) ($left['teacher_id'] ?? 0)) <=> ((int) ($right['teacher_id'] ?? 0));
        });

        foreach ($rows as $index => &$row) {
            $row['rank_position'] = $index + 1;
        }
        unset($row);

        return $rows;
    }

    public function snapshot(string $session, ?string $examType = null): array
    {
        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);

        $overallRows = $this->snapshotQuery($resolvedSession, $resolvedExamType)
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_OVERALL)
            ->with([
                'teacher:id,teacher_id,user_id',
                'teacher.user:id,name',
            ])
            ->get()
            ->map(fn (TeacherCgpaRanking $ranking): array => $this->mapSnapshotRow($ranking))
            ->sort(fn (array $left, array $right): int => $this->compareSnapshotRows($left, $right))
            ->values()
            ->all();

        $classwiseRows = $this->snapshotQuery($resolvedSession, $resolvedExamType)
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_CLASSWISE)
            ->with([
                'teacher:id,teacher_id,user_id',
                'teacher.user:id,name',
                'classRoom:id,name,section',
            ])
            ->get()
            ->map(fn (TeacherCgpaRanking $ranking): array => $this->mapSnapshotRow($ranking))
            ->sort(function (array $left, array $right): int {
                $classComparison = strcasecmp((string) ($left['class_name'] ?? ''), (string) ($right['class_name'] ?? ''));
                if ($classComparison !== 0) {
                    return $classComparison;
                }

                return $this->compareSnapshotRows($left, $right);
            })
            ->values()
            ->all();

        $topTeacher = $overallRows[0] ?? null;
        $averageSchoolTeacherCgpa = $overallRows === []
            ? null
            : round(
                array_sum(array_map(static fn (array $row): float => (float) $row['cgpa'], $overallRows)) / count($overallRows),
                2
            );

        return [
            'overall' => $overallRows,
            'classwise' => $classwiseRows,
            'summary' => [
                'top_teacher' => $topTeacher,
                'average_school_teacher_cgpa' => $averageSchoolTeacherCgpa,
                'total_ranked_teachers' => count($overallRows),
            ],
        ];
    }

    private function buildClasswiseRows(string $session, ?string $examType): array
    {
        $studentRows = $this->subjectPercentageRows($session, $examType)
            ->groupBy(fn (array $row): string => $row['teacher_id'].'|'.$row['class_id'].'|'.$row['student_id'])
            ->map(function (Collection $rows): array {
                $first = $rows->first();
                $studentPercentage = round((float) ($rows->avg('percentage') ?? 0), 2);

                return [
                    'teacher_id' => (int) $first['teacher_id'],
                    'teacher_name' => (string) $first['teacher_name'],
                    'teacher_code' => (string) ($first['teacher_code'] ?? ''),
                    'class_id' => (int) $first['class_id'],
                    'class_name' => (string) $first['class_name'],
                    'student_id' => (int) $first['student_id'],
                    'student_percentage' => $studentPercentage,
                ];
            })
            ->values();

        return $studentRows
            ->groupBy(fn (array $row): string => $row['teacher_id'].'|'.$row['class_id'])
            ->map(function (Collection $rows) use ($session, $examType): array {
                $first = $rows->first();
                $studentCount = $rows->count();
                $averagePercentage = $studentCount > 0 ? round((float) ($rows->avg('student_percentage') ?? 0), 2) : 0.0;
                $passCount = $rows
                    ->filter(fn (array $row): bool => (float) $row['student_percentage'] >= self::PASS_PERCENTAGE)
                    ->count();
                $passPercentage = $studentCount > 0 ? round(($passCount * 100) / $studentCount, 2) : 0.0;

                return [
                    'teacher_id' => (int) $first['teacher_id'],
                    'teacher_name' => (string) $first['teacher_name'],
                    'teacher_code' => (string) ($first['teacher_code'] ?? ''),
                    'session' => $session,
                    'exam_type' => $examType,
                    'class_id' => (int) $first['class_id'],
                    'class_name' => (string) $first['class_name'],
                    'average_percentage' => $averagePercentage,
                    'cgpa' => $this->convertPercentageToCgpa($averagePercentage),
                    'student_count' => $studentCount,
                    'pass_percentage' => $passPercentage,
                    'pass_count' => $passCount,
                    'ranking_scope' => TeacherCgpaRanking::SCOPE_CLASSWISE,
                ];
            })
            ->filter(fn (array $row): bool => (int) $row['student_count'] > 0)
            ->values()
            ->all();
    }

    private function buildOverallRows(array $classwiseRows, string $session, ?string $examType): array
    {
        return collect($classwiseRows)
            ->groupBy('teacher_id')
            ->map(function (Collection $rows) use ($session, $examType): array {
                $first = $rows->first();
                $studentCount = (int) $rows->sum('student_count');
                $weightedPercentage = $studentCount > 0
                    ? round(
                        $rows->sum(fn (array $row): float => (float) $row['average_percentage'] * (int) $row['student_count']) / $studentCount,
                        2
                    )
                    : 0.0;
                $passCount = (int) $rows->sum('pass_count');
                $passPercentage = $studentCount > 0 ? round(($passCount * 100) / $studentCount, 2) : 0.0;

                return [
                    'teacher_id' => (int) $first['teacher_id'],
                    'teacher_name' => (string) $first['teacher_name'],
                    'teacher_code' => (string) ($first['teacher_code'] ?? ''),
                    'session' => $session,
                    'exam_type' => $examType,
                    'class_id' => null,
                    'class_name' => null,
                    'average_percentage' => $weightedPercentage,
                    'cgpa' => $this->convertPercentageToCgpa($weightedPercentage),
                    'student_count' => $studentCount,
                    'pass_percentage' => $passPercentage,
                    'pass_count' => $passCount,
                    'ranking_scope' => TeacherCgpaRanking::SCOPE_OVERALL,
                ];
            })
            ->filter(fn (array $row): bool => (int) $row['student_count'] > 0)
            ->values()
            ->all();
    }

    private function subjectPercentageRows(string $session, ?string $examType): Collection
    {
        $assignmentSubquery = TeacherAssignment::query()
            ->join('teachers as t', 't.id', '=', 'teacher_assignments.teacher_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->join('school_classes as c', 'c.id', '=', 'teacher_assignments.class_id')
            ->where('teacher_assignments.session', $session)
            ->whereNotNull('teacher_assignments.subject_id')
            ->where('teacher_assignments.is_class_teacher', false)
            ->select([
                'teacher_assignments.teacher_id',
                'teacher_assignments.class_id',
                'teacher_assignments.subject_id',
                'u.name as teacher_name',
                't.teacher_id as teacher_code',
                'c.name as raw_class_name',
                'c.section as class_section',
            ])
            ->distinct();

        return DB::query()
            ->fromSub($assignmentSubquery, 'ta')
            ->join('exams as e', function (JoinClause $join) use ($session): void {
                $join->on('e.class_id', '=', 'ta.class_id')
                    ->on('e.subject_id', '=', 'ta.subject_id')
                    ->where('e.session', '=', $session);
            })
            ->join('marks as m', function (JoinClause $join) use ($session): void {
                $join->on('m.exam_id', '=', 'e.id')
                    ->where('m.session', '=', $session);
            })
            ->leftJoin('student_subject_assignments as ssa', function (JoinClause $join) use ($session): void {
                $join->on('ssa.student_id', '=', 'm.student_id')
                    ->on('ssa.class_id', '=', 'e.class_id')
                    ->on('ssa.subject_id', '=', 'e.subject_id')
                    ->where('ssa.session', '=', $session);
            })
            ->when($examType !== null, fn ($query) => $query->where('e.exam_type', $examType))
            ->whereNotNull('m.obtained_marks')
            ->whereNotNull('m.total_marks')
            ->where('m.total_marks', '>', 0)
            ->select([
                'ta.teacher_id',
                'ta.teacher_name',
                'ta.teacher_code',
                'ta.class_id',
                'ta.raw_class_name',
                'ta.class_section',
                'm.student_id',
                'ssa.id as student_subject_assignment_id',
            ])
            ->selectRaw('(m.obtained_marks * 100.0) / NULLIF(m.total_marks, 0) as percentage')
            ->get()
            ->map(function ($row): array {
                return [
                    'teacher_id' => (int) $row->teacher_id,
                    'teacher_name' => (string) ($row->teacher_name ?? ('Teacher '.$row->teacher_id)),
                    'teacher_code' => (string) ($row->teacher_code ?? ''),
                    'class_id' => (int) $row->class_id,
                    'raw_class_name' => (string) $row->raw_class_name,
                    'class_name' => $this->classLabel((string) $row->raw_class_name, $row->class_section),
                    'student_id' => (int) $row->student_id,
                    'student_subject_assignment_id' => $row->student_subject_assignment_id !== null
                        ? (int) $row->student_subject_assignment_id
                        : null,
                    'percentage' => round((float) ($row->percentage ?? 0), 4),
                ];
            })
            ->filter(function (array $row): bool {
                if ($this->assessmentModeService->classUsesGradeSystem($row['raw_class_name'])) {
                    return false;
                }

                if (! $this->requiresStudentSubjectAssignment($row['raw_class_name'])) {
                    return true;
                }

                return $row['student_subject_assignment_id'] !== null;
            })
            ->values();
    }

    private function requiresStudentSubjectAssignment(string $className): bool
    {
        $normalized = strtolower(trim($className));

        return preg_match('/(^|[^0-9])(9|10|11|12)(st|nd|rd|th)?($|[^0-9])/', $normalized) === 1;
    }

    private function classLabel(string $name, mixed $section): string
    {
        return trim($name.' '.(string) ($section ?? ''));
    }

    private function compareDesc(float|int $left, float|int $right): int
    {
        return $right <=> $left;
    }

    private function snapshotKey(int $teacherId, string $scope, ?int $classId): string
    {
        return $scope.'|'.$teacherId.'|'.($classId ?? 'overall');
    }

    private function snapshotQuery(string $session, ?string $examType)
    {
        return TeacherCgpaRanking::query()
            ->where('session', $session)
            ->when(
                $examType === null,
                fn ($query) => $query->whereNull('exam_type'),
                fn ($query) => $query->where('exam_type', $examType)
            );
    }

    private function mapSnapshotRow(TeacherCgpaRanking $ranking): array
    {
        return [
            'teacher_id' => (int) $ranking->teacher_id,
            'teacher_name' => (string) ($ranking->teacher?->user?->name ?? ('Teacher '.$ranking->teacher_id)),
            'teacher_code' => (string) ($ranking->teacher?->teacher_id ?? ''),
            'class_id' => $ranking->class_id !== null ? (int) $ranking->class_id : null,
            'class_name' => $ranking->classRoom !== null
                ? $this->classLabel((string) $ranking->classRoom->name, $ranking->classRoom->section)
                : null,
            'average_percentage' => round((float) $ranking->average_percentage, 2),
            'cgpa' => round((float) $ranking->cgpa, 2),
            'student_count' => (int) $ranking->student_count,
            'rank_position' => $ranking->rank_position !== null ? (int) $ranking->rank_position : null,
            'ranking_scope' => (string) $ranking->ranking_scope,
        ];
    }

    private function compareSnapshotRows(array $left, array $right): int
    {
        $leftRank = $left['rank_position'] ?? PHP_INT_MAX;
        $rightRank = $right['rank_position'] ?? PHP_INT_MAX;

        if ($leftRank !== $rightRank) {
            return $leftRank <=> $rightRank;
        }

        return strcasecmp((string) ($left['teacher_name'] ?? ''), (string) ($right['teacher_name'] ?? ''));
    }

    private function currentSessionStartYear(): int
    {
        $now = now();

        return $now->month >= 7 ? $now->year : ($now->year - 1);
    }
}
