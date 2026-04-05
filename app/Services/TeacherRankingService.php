<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\TeacherAssignment;
use App\Models\TeacherCgpaRanking;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

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

    /**
     * @var array<string, array{label:string}>
     */
    private const RANKING_GROUPS = [
        TeacherCgpaRanking::GROUP_EARLY_YEARS => ['label' => 'Early Years'],
        TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL => ['label' => 'Middle School'],
        TeacherCgpaRanking::GROUP_SENIOR_SCHOOL => ['label' => 'Senior School'],
    ];

    /**
     * @var array<int, string>
     */
    private const EARLY_YEARS_CLASS_NAMES = ['pg', 'prep', 'nursery', '1', 'class 1'];

    /**
     * @var array<int, string>
     */
    private const MIDDLE_SCHOOL_CLASS_NAMES = ['2', '3', '4', '5', '6', 'class 2', 'class 3', 'class 4', 'class 5', 'class 6'];

    /**
     * @var array<int, string>
     */
    private const SENIOR_SCHOOL_CLASS_NAMES = ['7', '8', '9', '10', '11', '12', 'class 7', 'class 8', 'class 9', 'class 10', 'class 11', 'class 12'];

    public function __construct(
        private readonly ClassAssessmentModeService $assessmentModeService,
        private readonly GradeScaleService $gradeScaleService
    ) {
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

    /**
     * @return array<string, array{label:string}>
     */
    public function rankingGroups(): array
    {
        return self::RANKING_GROUPS;
    }

    public function rankingGroupLabel(string $rankingGroup): string
    {
        $normalized = $this->normalizeRankingGroup($rankingGroup);

        return self::RANKING_GROUPS[$normalized]['label'] ?? self::RANKING_GROUPS[TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL]['label'];
    }

    public function rankingsTableReady(): bool
    {
        return Schema::hasTable('teacher_cgpa_rankings')
            && Schema::hasColumn('teacher_cgpa_rankings', 'pass_percentage')
            && Schema::hasColumn('teacher_cgpa_rankings', 'ranking_group');
    }

    public function rankingsTableMessage(): ?string
    {
        if ($this->rankingsTableReady()) {
            return null;
        }

        return 'Teacher CGPA rankings are not available yet on this server. Run the latest migrations, then regenerate rankings.';
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

        return collect($this->groupedClasswiseRows($resolvedSession, $resolvedExamType))
            ->flatMap(static fn (array $rows): array => $rows)
            ->values()
            ->all();
    }

    public function calculateTeacherOverallCgpa(string $session, ?string $examType = null): array
    {
        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);

        return collect($this->rankingGroupValues())
            ->flatMap(fn (string $rankingGroup): array => $this->calculateTeacherOverallCgpaByGroup(
                $resolvedSession,
                $rankingGroup,
                $resolvedExamType
            ))
            ->values()
            ->all();
    }

    public function calculateTeacherClasswiseCgpaByGroup(string $session, string $rankingGroup, ?string $examType = null): array
    {
        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);
        $resolvedGroup = $this->normalizeRankingGroup($rankingGroup);

        return $this->groupedClasswiseRows($resolvedSession, $resolvedExamType)[$resolvedGroup] ?? [];
    }

    public function calculateTeacherOverallCgpaByGroup(string $session, string $rankingGroup, ?string $examType = null): array
    {
        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);
        $resolvedGroup = $this->normalizeRankingGroup($rankingGroup);

        return $this->buildOverallRows(
            $this->groupedClasswiseRows($resolvedSession, $resolvedExamType)[$resolvedGroup] ?? [],
            $resolvedSession,
            $resolvedExamType
        );
    }

    public function storeTeacherCgpaRankings(string $session, ?string $examType = null): void
    {
        if (! $this->rankingsTableReady()) {
            throw new RuntimeException($this->rankingsTableMessage() ?? 'Teacher CGPA ranking storage is unavailable.');
        }

        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);
        $groupedClasswiseRows = $this->groupedClasswiseRows($resolvedSession, $resolvedExamType);

        DB::transaction(function () use ($resolvedSession, $resolvedExamType, $groupedClasswiseRows): void {
            foreach ($this->rankingGroupValues() as $rankingGroup) {
                $this->syncSnapshotRowsForGroup(
                    $resolvedSession,
                    $resolvedExamType,
                    $rankingGroup,
                    $groupedClasswiseRows[$rankingGroup] ?? []
                );
            }
        });
    }

    public function storeTeacherCgpaRankingsByGroup(string $session, string $rankingGroup, ?string $examType = null): void
    {
        if (! $this->rankingsTableReady()) {
            throw new RuntimeException($this->rankingsTableMessage() ?? 'Teacher CGPA ranking storage is unavailable.');
        }

        $resolvedSession = $this->resolveSession($session);
        $resolvedExamType = $this->normalizeExamType($examType);
        $resolvedGroup = $this->normalizeRankingGroup($rankingGroup);
        $classwiseRows = $this->calculateTeacherClasswiseCgpaByGroup($resolvedSession, $resolvedGroup, $resolvedExamType);

        DB::transaction(function () use ($resolvedSession, $resolvedExamType, $resolvedGroup, $classwiseRows): void {
            $this->syncSnapshotRowsForGroup($resolvedSession, $resolvedExamType, $resolvedGroup, $classwiseRows);
        });
    }

    public function resolveRankingGroupFromClass(SchoolClass|int|string|null $class): string
    {
        $className = $this->resolveClassName($class);
        $normalized = $this->normalizeClassName($className);

        if ($normalized === null) {
            return TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL;
        }

        return match (true) {
            in_array($normalized, self::EARLY_YEARS_CLASS_NAMES, true) => TeacherCgpaRanking::GROUP_EARLY_YEARS,
            in_array($normalized, self::MIDDLE_SCHOOL_CLASS_NAMES, true) => TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL,
            in_array($normalized, self::SENIOR_SCHOOL_CLASS_NAMES, true) => TeacherCgpaRanking::GROUP_SENIOR_SCHOOL,
            default => TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL,
        };
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

            if ($this->usesEarlyYearsTieBreakers($left, $right)) {
                $comparison = $this->compareAsc(
                    (int) ($left['u_grade_count'] ?? 0),
                    (int) ($right['u_grade_count'] ?? 0)
                );
                if ($comparison !== 0) {
                    return $comparison;
                }

                $comparison = $this->compareDesc(
                    (int) ($left['top_grade_count'] ?? 0),
                    (int) ($right['top_grade_count'] ?? 0)
                );
                if ($comparison !== 0) {
                    return $comparison;
                }
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

        $groupPayloads = [];
        foreach ($this->rankingGroupValues() as $rankingGroup) {
            $groupPayloads[$rankingGroup] = $this->snapshotGroup($resolvedSession, $resolvedExamType, $rankingGroup);
        }

        $schemaReady = $this->rankingsTableReady();
        $previewMode = collect($groupPayloads)
            ->contains(fn (array $payload): bool => (bool) ($payload['preview_mode'] ?? false));
        $dataSources = collect($groupPayloads)
            ->pluck('data_source')
            ->filter()
            ->unique()
            ->values();

        $schemaMessage = null;
        if (! $schemaReady) {
            $schemaMessage = 'Teacher CGPA ranking storage is not available yet on this server. Showing live calculated previews from current results. Run the latest migrations to enable saved rankings and regeneration.';
        } elseif ($previewMode) {
            $schemaMessage = 'Some ranking groups are showing live calculated previews from current results because no saved snapshot exists for the selected scope yet. Use Regenerate Rankings to save them.';
        }

        return $this->formatSnapshotPayload(
            $groupPayloads,
            $schemaReady,
            $schemaMessage,
            $previewMode,
            $dataSources->count() === 1 ? (string) $dataSources->first() : 'mixed'
        );
    }

    private function snapshotGroup(string $session, ?string $examType, string $rankingGroup): array
    {
        if (! $this->rankingsTableReady()) {
            return $this->liveSnapshotGroup($session, $examType, $rankingGroup, false);
        }

        $overallRows = $this->snapshotQuery($session, $examType, $rankingGroup)
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

        $classwiseRows = $this->snapshotQuery($session, $examType, $rankingGroup)
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_CLASSWISE)
            ->with([
                'teacher:id,teacher_id,user_id',
                'teacher.user:id,name',
                'classRoom:id,name,section',
            ])
            ->get()
            ->map(fn (TeacherCgpaRanking $ranking): array => $this->mapSnapshotRow($ranking))
            ->sort(fn (array $left, array $right): int => $this->compareClasswiseSnapshotRows($left, $right))
            ->values()
            ->all();

        if ($overallRows === [] && $classwiseRows === []) {
            return $this->liveSnapshotGroup($session, $examType, $rankingGroup, true);
        }

        return $this->formatGroupSnapshot(
            $rankingGroup,
            $overallRows,
            $classwiseRows,
            true,
            null,
            false,
            'snapshot'
        );
    }

    private function liveSnapshotGroup(string $session, ?string $examType, string $rankingGroup, bool $schemaReady): array
    {
        $classwiseRows = collect($this->calculateTeacherClasswiseCgpaByGroup($session, $rankingGroup, $examType))
            ->groupBy('class_id')
            ->flatMap(fn (Collection $rows): array => $this->rankTeachers($rows->all()))
            ->sort(fn (array $left, array $right): int => $this->compareClasswiseSnapshotRows($left, $right))
            ->values()
            ->all();

        $overallRows = collect(
            $this->rankTeachers($this->calculateTeacherOverallCgpaByGroup($session, $rankingGroup, $examType))
        )
            ->values()
            ->all();

        $message = $schemaReady
            ? 'No saved teacher ranking snapshot exists for this academic level and scope yet. Showing a live calculated preview from current results.'
            : 'Teacher CGPA ranking storage is not available yet on this server. Showing a live calculated preview from current results.';

        return $this->formatGroupSnapshot(
            $rankingGroup,
            $overallRows,
            $classwiseRows,
            $schemaReady,
            $message,
            true,
            'live_preview'
        );
    }

    /**
     * @param array<string, array{
     *   ranking_group:string,
     *   ranking_group_label:string,
     *   overall:array<int, array<string, mixed>>,
     *   classwise:array<int, array<string, mixed>>,
     *   summary:array<string, mixed>,
     *   schema_ready:bool,
     *   schema_message:?string,
     *   preview_mode:bool,
     *   data_source:string
     * }> $groupPayloads
     */
    private function formatSnapshotPayload(
        array $groupPayloads,
        bool $schemaReady,
        ?string $schemaMessage,
        bool $previewMode,
        string $dataSource
    ): array {
        $overallRows = collect($groupPayloads)
            ->flatMap(static fn (array $payload): array => $payload['overall'])
            ->sort(function (array $left, array $right): int {
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

                return strcasecmp((string) ($left['teacher_name'] ?? ''), (string) ($right['teacher_name'] ?? ''));
            })
            ->values()
            ->all();

        $classwiseRows = collect($groupPayloads)
            ->flatMap(static fn (array $payload): array => $payload['classwise'])
            ->sort(fn (array $left, array $right): int => $this->compareClasswiseSnapshotRows($left, $right))
            ->values()
            ->all();

        $averageSchoolTeacherCgpa = $overallRows === []
            ? null
            : round(
                array_sum(array_map(static fn (array $row): float => (float) $row['cgpa'], $overallRows)) / count($overallRows),
                2
            );

        return [
            'groups' => $groupPayloads,
            'overall' => $overallRows,
            'classwise' => $classwiseRows,
            'summary' => [
                'top_teacher' => $overallRows[0] ?? null,
                'average_school_teacher_cgpa' => $averageSchoolTeacherCgpa,
                'total_ranked_teachers' => count($overallRows),
            ],
            'schema_ready' => $schemaReady,
            'schema_message' => $schemaMessage,
            'preview_mode' => $previewMode,
            'data_source' => $dataSource,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $overallRows
     * @param array<int, array<string, mixed>> $classwiseRows
     * @return array{
     *   ranking_group:string,
     *   ranking_group_label:string,
     *   overall:array<int, array<string, mixed>>,
     *   classwise:array<int, array<string, mixed>>,
     *   summary:array{
     *     top_teacher:?array<string, mixed>,
     *     average_teacher_cgpa:?float,
     *     total_ranked_teachers:int
     *   },
     *   schema_ready:bool,
     *   schema_message:?string,
     *   preview_mode:bool,
     *   data_source:string
     * }
     */
    private function formatGroupSnapshot(
        string $rankingGroup,
        array $overallRows,
        array $classwiseRows,
        bool $schemaReady,
        ?string $schemaMessage,
        bool $previewMode,
        string $dataSource
    ): array {
        $normalizedGroup = $this->normalizeRankingGroup($rankingGroup);
        $enrichedOverallRows = $this->attachClassesToOverallRows($overallRows, $classwiseRows);
        $averageTeacherCgpa = $enrichedOverallRows === []
            ? null
            : round(
                array_sum(array_map(static fn (array $row): float => (float) $row['cgpa'], $enrichedOverallRows)) / count($enrichedOverallRows),
                2
            );

        return [
            'ranking_group' => $normalizedGroup,
            'ranking_group_label' => $this->rankingGroupLabel($normalizedGroup),
            'overall' => $enrichedOverallRows,
            'classwise' => $classwiseRows,
            'summary' => [
                'top_teacher' => $enrichedOverallRows[0] ?? null,
                'average_teacher_cgpa' => $averageTeacherCgpa,
                'total_ranked_teachers' => count($enrichedOverallRows),
            ],
            'schema_ready' => $schemaReady,
            'schema_message' => $schemaMessage,
            'preview_mode' => $previewMode,
            'data_source' => $dataSource,
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupedClasswiseRows(string $session, ?string $examType): array
    {
        $metricRows = $this->resultMetricRows($session, $examType);
        $groupedRows = [];

        foreach ($this->rankingGroupValues() as $rankingGroup) {
            $groupedRows[$rankingGroup] = $this->buildClasswiseRowsFromMetricRows(
                $metricRows->where('ranking_group', $rankingGroup)->values(),
                $session,
                $examType
            );
        }

        return $groupedRows;
    }

    /**
     * @param Collection<int, array<string, mixed>> $metricRows
     * @return array<int, array<string, mixed>>
     */
    private function buildClasswiseRowsFromMetricRows(Collection $metricRows, string $session, ?string $examType): array
    {
        if ($metricRows->isEmpty()) {
            return [];
        }

        $studentRows = $metricRows
            ->groupBy(fn (array $row): string => $row['teacher_id'].'|'.$row['class_id'].'|'.$row['student_id'])
            ->map(function (Collection $rows): array {
                /** @var array<string, mixed> $first */
                $first = $rows->first();

                return [
                    'teacher_id' => (int) $first['teacher_id'],
                    'teacher_name' => (string) $first['teacher_name'],
                    'teacher_code' => (string) ($first['teacher_code'] ?? ''),
                    'class_id' => (int) $first['class_id'],
                    'class_name' => (string) $first['class_name'],
                    'ranking_group' => (string) $first['ranking_group'],
                    'student_id' => (int) $first['student_id'],
                    'uses_grade_system' => (bool) ($first['uses_grade_system'] ?? false),
                    'student_percentage' => round((float) ($rows->avg('percentage_equivalent') ?? 0), 2),
                    'student_cgpa' => round((float) ($rows->avg('cgpa_value') ?? 0), 2),
                    'u_grade_count' => (int) $rows->sum('u_grade_count'),
                    'top_grade_count' => (int) $rows->sum('top_grade_count'),
                ];
            })
            ->values();

        return $studentRows
            ->groupBy(fn (array $row): string => $row['teacher_id'].'|'.$row['class_id'].'|'.$row['ranking_group'])
            ->map(function (Collection $rows) use ($session, $examType): array {
                /** @var array<string, mixed> $first */
                $first = $rows->first();
                $studentCount = $rows->count();
                $averagePercentage = $studentCount > 0 ? round((float) ($rows->avg('student_percentage') ?? 0), 2) : 0.0;
                $usesGradeSystem = (bool) ($first['uses_grade_system'] ?? false);
                $passCount = $rows
                    ->filter(fn (array $row): bool => (float) $row['student_percentage'] >= self::PASS_PERCENTAGE)
                    ->count();
                $passPercentage = $studentCount > 0 ? round(($passCount * 100) / $studentCount, 2) : 0.0;
                $cgpa = $usesGradeSystem
                    ? round((float) ($rows->avg('student_cgpa') ?? 0), 2)
                    : $this->convertPercentageToCgpa($averagePercentage);
                $rankingGroup = (string) $first['ranking_group'];

                return [
                    'teacher_id' => (int) $first['teacher_id'],
                    'teacher_name' => (string) $first['teacher_name'],
                    'teacher_code' => (string) ($first['teacher_code'] ?? ''),
                    'session' => $session,
                    'exam_type' => $examType,
                    'class_id' => (int) $first['class_id'],
                    'class_name' => (string) $first['class_name'],
                    'average_percentage' => $averagePercentage,
                    'cgpa' => $cgpa,
                    'student_count' => $studentCount,
                    'pass_percentage' => $passPercentage,
                    'pass_count' => $passCount,
                    'u_grade_count' => (int) $rows->sum('u_grade_count'),
                    'top_grade_count' => (int) $rows->sum('top_grade_count'),
                    'uses_grade_system' => $usesGradeSystem,
                    'ranking_scope' => TeacherCgpaRanking::SCOPE_CLASSWISE,
                    'ranking_group' => $rankingGroup,
                    'ranking_group_label' => $this->rankingGroupLabel($rankingGroup),
                ];
            })
            ->filter(fn (array $row): bool => (int) $row['student_count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $classwiseRows
     * @return array<int, array<string, mixed>>
     */
    private function buildOverallRows(array $classwiseRows, string $session, ?string $examType): array
    {
        return collect($classwiseRows)
            ->groupBy(fn (array $row): string => $row['teacher_id'].'|'.($row['ranking_group'] ?? TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL))
            ->map(function (Collection $rows) use ($session, $examType): array {
                /** @var array<string, mixed> $first */
                $first = $rows->first();
                $studentCount = (int) $rows->sum('student_count');
                $weightedPercentage = $studentCount > 0
                    ? round(
                        $rows->sum(fn (array $row): float => (float) $row['average_percentage'] * (int) $row['student_count']) / $studentCount,
                        2
                    )
                    : 0.0;
                $weightedCgpa = $studentCount > 0
                    ? round(
                        $rows->sum(fn (array $row): float => (float) $row['cgpa'] * (int) $row['student_count']) / $studentCount,
                        2
                    )
                    : 0.0;
                $passCount = (int) $rows->sum('pass_count');
                $passPercentage = $studentCount > 0 ? round(($passCount * 100) / $studentCount, 2) : 0.0;
                $classes = $this->sortedUniqueStrings(
                    $rows->pluck('class_name')->filter()->map(static fn ($value): string => (string) $value)->all()
                );
                $rankingGroup = (string) ($first['ranking_group'] ?? TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL);

                return [
                    'teacher_id' => (int) $first['teacher_id'],
                    'teacher_name' => (string) $first['teacher_name'],
                    'teacher_code' => (string) ($first['teacher_code'] ?? ''),
                    'session' => $session,
                    'exam_type' => $examType,
                    'class_id' => null,
                    'class_name' => null,
                    'average_percentage' => $weightedPercentage,
                    'cgpa' => $weightedCgpa,
                    'student_count' => $studentCount,
                    'pass_percentage' => $passPercentage,
                    'pass_count' => $passCount,
                    'u_grade_count' => (int) $rows->sum('u_grade_count'),
                    'top_grade_count' => (int) $rows->sum('top_grade_count'),
                    'uses_grade_system' => $rows->contains(fn (array $row): bool => (bool) ($row['uses_grade_system'] ?? false)),
                    'ranking_scope' => TeacherCgpaRanking::SCOPE_OVERALL,
                    'ranking_group' => $rankingGroup,
                    'ranking_group_label' => $this->rankingGroupLabel($rankingGroup),
                    'classes' => $classes,
                    'classes_label' => implode(', ', $classes),
                ];
            })
            ->filter(fn (array $row): bool => (int) $row['student_count'] > 0)
            ->values()
            ->all();
    }

    private function syncSnapshotRowsForGroup(string $session, ?string $examType, string $rankingGroup, array $classwiseRows): void
    {
        $rankedClasswiseRows = collect($classwiseRows)
            ->groupBy('class_id')
            ->flatMap(fn (Collection $rows): array => $this->rankTeachers($rows->all()))
            ->values()
            ->all();

        $rankedOverallRows = $this->rankTeachers(
            $this->buildOverallRows($classwiseRows, $session, $examType)
        );

        $incomingRows = array_merge($rankedClasswiseRows, $rankedOverallRows);
        $existingGroups = $this->snapshotQuery($session, $examType, $rankingGroup)
            ->get()
            ->groupBy(fn (TeacherCgpaRanking $ranking): string => $this->snapshotKey(
                (int) $ranking->teacher_id,
                (string) $ranking->ranking_scope,
                $ranking->class_id !== null ? (int) $ranking->class_id : null,
                (string) $ranking->ranking_group
            ));

        foreach ($incomingRows as $row) {
            $key = $this->snapshotKey(
                (int) $row['teacher_id'],
                (string) $row['ranking_scope'],
                isset($row['class_id']) ? (int) $row['class_id'] : null,
                (string) ($row['ranking_group'] ?? $rankingGroup)
            );

            /** @var Collection<int, TeacherCgpaRanking> $matches */
            $matches = $existingGroups->pull($key, collect());
            /** @var TeacherCgpaRanking|null $ranking */
            $ranking = $matches->shift();

            $payload = [
                'teacher_id' => (int) $row['teacher_id'],
                'session' => $session,
                'exam_type' => $examType,
                'class_id' => isset($row['class_id']) ? (int) $row['class_id'] : null,
                'average_percentage' => round((float) $row['average_percentage'], 2),
                'pass_percentage' => round((float) ($row['pass_percentage'] ?? 0), 2),
                'cgpa' => round((float) $row['cgpa'], 2),
                'student_count' => (int) $row['student_count'],
                'rank_position' => isset($row['rank_position']) ? (int) $row['rank_position'] : null,
                'ranking_scope' => (string) $row['ranking_scope'],
                'ranking_group' => (string) ($row['ranking_group'] ?? $rankingGroup),
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
    }

    private function resultMetricRows(string $session, ?string $examType): Collection
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
            ->leftJoinSub(
                DB::table('student_subject_assignments')
                    ->where('session', $session)
                    ->select('class_id', 'subject_id')
                    ->distinct(),
                'ssam',
                function (JoinClause $join): void {
                    $join->on('ssam.class_id', '=', 'e.class_id')
                        ->on('ssam.subject_id', '=', 'e.subject_id');
                }
            )
            ->leftJoin('student_subject_assignments as ssa', function (JoinClause $join) use ($session): void {
                $join->on('ssa.student_id', '=', 'm.student_id')
                    ->on('ssa.class_id', '=', 'e.class_id')
                    ->on('ssa.subject_id', '=', 'e.subject_id')
                    ->where('ssa.session', '=', $session);
            })
            ->leftJoin('student_subjects as ss', function (JoinClause $join) use ($session): void {
                $join->on('ss.student_id', '=', 'm.student_id')
                    ->on('ss.subject_id', '=', 'e.subject_id')
                    ->where('ss.session', '=', $session);
            })
            ->when($examType !== null, fn ($query) => $query->where('e.exam_type', $examType))
            ->select([
                'ta.teacher_id',
                'ta.teacher_name',
                'ta.teacher_code',
                'ta.class_id',
                'ta.raw_class_name',
                'ta.class_section',
                'm.student_id',
                'm.obtained_marks',
                'm.total_marks',
                'm.grade',
                'ssam.class_id as subject_assignment_matrix_class_id',
                'ssa.id as student_subject_assignment_id',
                'ss.id as student_subject_id',
            ])
            ->get()
            ->map(function ($row): ?array {
                $rawClassName = (string) $row->raw_class_name;
                $usesGradeSystem = $this->assessmentModeService->classUsesGradeSystem($rawClassName);
                $rankingGroup = $this->resolveRankingGroupFromClass($rawClassName);

                if ($this->requiresStudentSubjectAssignment($rawClassName)) {
                    $hasMatrixAssignments = $row->subject_assignment_matrix_class_id !== null;
                    $studentIsAssignedToSubject = $hasMatrixAssignments
                        ? $row->student_subject_assignment_id !== null
                        : $row->student_subject_id !== null;

                    if (! $studentIsAssignedToSubject) {
                        return null;
                    }
                }

                if ($usesGradeSystem) {
                    $grade = $this->assessmentModeService->normalizeGrade($row->grade);
                    if ($grade === null) {
                        return null;
                    }

                    return [
                        'teacher_id' => (int) $row->teacher_id,
                        'teacher_name' => (string) ($row->teacher_name ?? ('Teacher '.$row->teacher_id)),
                        'teacher_code' => (string) ($row->teacher_code ?? ''),
                        'class_id' => (int) $row->class_id,
                        'raw_class_name' => $rawClassName,
                        'class_name' => $this->classLabel($rawClassName, $row->class_section),
                        'ranking_group' => $rankingGroup,
                        'uses_grade_system' => true,
                        'student_id' => (int) $row->student_id,
                        'percentage_equivalent' => round($this->gradeScaleService->getPercentageEquivalent($grade), 4),
                        'cgpa_value' => round($this->gradeScaleService->getGradePoint($grade), 4),
                        'u_grade_count' => $grade === 'U' ? 1 : 0,
                        'top_grade_count' => $this->isTopGrade($grade) ? 1 : 0,
                    ];
                }

                if ($row->obtained_marks === null || $row->total_marks === null || (int) $row->total_marks <= 0) {
                    return null;
                }

                $percentage = round((((float) $row->obtained_marks) * 100.0) / (float) $row->total_marks, 4);

                return [
                    'teacher_id' => (int) $row->teacher_id,
                    'teacher_name' => (string) ($row->teacher_name ?? ('Teacher '.$row->teacher_id)),
                    'teacher_code' => (string) ($row->teacher_code ?? ''),
                    'class_id' => (int) $row->class_id,
                    'raw_class_name' => $rawClassName,
                    'class_name' => $this->classLabel($rawClassName, $row->class_section),
                    'ranking_group' => $rankingGroup,
                    'uses_grade_system' => false,
                    'student_id' => (int) $row->student_id,
                    'percentage_equivalent' => $percentage,
                    'cgpa_value' => round($this->convertPercentageToCgpa($percentage), 4),
                    'u_grade_count' => 0,
                    'top_grade_count' => 0,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param array<int, array<string, mixed>> $overallRows
     * @param array<int, array<string, mixed>> $classwiseRows
     * @return array<int, array<string, mixed>>
     */
    private function attachClassesToOverallRows(array $overallRows, array $classwiseRows): array
    {
        $classesByTeacher = collect($classwiseRows)
            ->groupBy(fn (array $row): string => ((int) ($row['teacher_id'] ?? 0)).'|'.((string) ($row['ranking_group'] ?? TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL)))
            ->map(function (Collection $rows): array {
                return $this->sortedUniqueStrings(
                    $rows->pluck('class_name')
                        ->filter()
                        ->map(static fn ($value): string => (string) $value)
                        ->all()
                );
            });

        return collect($overallRows)
            ->map(function (array $row) use ($classesByTeacher): array {
                $key = ((int) ($row['teacher_id'] ?? 0)).'|'.((string) ($row['ranking_group'] ?? TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL));
                $classes = $classesByTeacher->get($key, []);

                $row['classes'] = $classes;
                $row['classes_label'] = implode(', ', $classes);

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function sortedUniqueStrings(array $values): array
    {
        return collect($values)
            ->filter(static fn ($value): bool => trim((string) $value) !== '')
            ->map(static fn ($value): string => trim((string) $value))
            ->unique()
            ->sort(fn (string $left, string $right): int => strnatcasecmp($left, $right))
            ->values()
            ->all();
    }

    private function compareClasswiseSnapshotRows(array $left, array $right): int
    {
        $classComparison = strcasecmp((string) ($left['class_name'] ?? ''), (string) ($right['class_name'] ?? ''));
        if ($classComparison !== 0) {
            return $classComparison;
        }

        return $this->compareSnapshotRows($left, $right);
    }

    private function usesEarlyYearsTieBreakers(array $left, array $right): bool
    {
        $leftGroup = (string) ($left['ranking_group'] ?? '');
        $rightGroup = (string) ($right['ranking_group'] ?? '');

        return $leftGroup === TeacherCgpaRanking::GROUP_EARLY_YEARS
            && $rightGroup === TeacherCgpaRanking::GROUP_EARLY_YEARS;
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

    private function compareAsc(float|int $left, float|int $right): int
    {
        return $left <=> $right;
    }

    private function isTopGrade(string $grade): bool
    {
        return in_array($grade, ['A*', 'A'], true);
    }

    private function snapshotKey(int $teacherId, string $scope, ?int $classId, string $rankingGroup): string
    {
        return $rankingGroup.'|'.$scope.'|'.$teacherId.'|'.($classId ?? 'overall');
    }

    private function snapshotQuery(string $session, ?string $examType, ?string $rankingGroup = null)
    {
        return TeacherCgpaRanking::query()
            ->where('session', $session)
            ->when(
                $examType === null,
                fn ($query) => $query->whereNull('exam_type'),
                fn ($query) => $query->where('exam_type', $examType)
            )
            ->when($rankingGroup !== null, fn ($query) => $query->where('ranking_group', $rankingGroup));
    }

    private function mapSnapshotRow(TeacherCgpaRanking $ranking): array
    {
        $rankingGroup = (string) ($ranking->ranking_group ?: TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL);

        return [
            'teacher_id' => (int) $ranking->teacher_id,
            'teacher_name' => (string) ($ranking->teacher?->user?->name ?? ('Teacher '.$ranking->teacher_id)),
            'teacher_code' => (string) ($ranking->teacher?->teacher_id ?? ''),
            'class_id' => $ranking->class_id !== null ? (int) $ranking->class_id : null,
            'class_name' => $ranking->classRoom !== null
                ? $this->classLabel((string) $ranking->classRoom->name, $ranking->classRoom->section)
                : null,
            'average_percentage' => round((float) $ranking->average_percentage, 2),
            'pass_percentage' => round((float) $ranking->pass_percentage, 2),
            'cgpa' => round((float) $ranking->cgpa, 2),
            'student_count' => (int) $ranking->student_count,
            'rank_position' => $ranking->rank_position !== null ? (int) $ranking->rank_position : null,
            'ranking_scope' => (string) $ranking->ranking_scope,
            'ranking_group' => $rankingGroup,
            'ranking_group_label' => $this->rankingGroupLabel($rankingGroup),
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

    private function normalizeRankingGroup(string $rankingGroup): string
    {
        $candidate = trim($rankingGroup);

        return array_key_exists($candidate, self::RANKING_GROUPS)
            ? $candidate
            : TeacherCgpaRanking::GROUP_MIDDLE_SCHOOL;
    }

    /**
     * @return array<int, string>
     */
    private function rankingGroupValues(): array
    {
        return array_keys(self::RANKING_GROUPS);
    }

    private function resolveClassName(SchoolClass|int|string|null $class): ?string
    {
        if ($class instanceof SchoolClass) {
            return $class->name;
        }

        if (is_int($class)) {
            return SchoolClass::query()->find($class, ['name'])?->name;
        }

        if (is_string($class)) {
            return $class;
        }

        return null;
    }

    private function normalizeClassName(?string $className): ?string
    {
        if ($className === null) {
            return null;
        }

        $normalized = Str::of($className)
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        return $normalized !== '' ? $normalized : null;
    }

    private function currentSessionStartYear(): int
    {
        $now = now();

        return $now->month >= 7 ? $now->year : ($now->year - 1);
    }
}
