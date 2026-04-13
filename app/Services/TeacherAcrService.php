<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Mark;
use App\Models\SchoolSetting;
use App\Models\Teacher;
use App\Models\TeacherAcr;
use App\Models\TeacherAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TeacherAcrService
{
    private const ATTENDANCE_WEIGHT = 15.0;

    private const ACADEMIC_WEIGHT = 30.0;

    private const IMPROVEMENT_WEIGHT = 15.0;

    private const CONDUCT_WEIGHT = 15.0;

    private const PD_WEIGHT = 10.0;

    private const PRINCIPAL_WEIGHT = 15.0;

    private const PD_NEUTRAL_SCORE = 5.0;

    private const IMPROVEMENT_NEUTRAL_SCORE = 7.5;

    private const ATTENDANCE_NEUTRAL_SCORE = 7.5;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $sessionContextCache = [];

    public function __construct(
        private readonly TeacherRankingService $teacherRankingService,
        private readonly ClassAssessmentModeService $assessmentModeService,
        private readonly GradeScaleService $gradeScaleService,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function sessionOptions(): array
    {
        return $this->teacherRankingService->sessionOptions();
    }

    public function resolveSession(?string $session): string
    {
        return $this->teacherRankingService->resolveSession($session);
    }

    /**
     * @return array<int, array{id:int,name:string,teacher_id:string,employee_code:?string}>
     */
    public function teacherOptionsForSession(string $session): array
    {
        $context = $this->sessionContext($this->resolveSession($session));

        return $context['teachers']
            ->map(fn (Teacher $teacher): array => [
                'id' => (int) $teacher->id,
                'name' => $this->teacherName($teacher),
                'teacher_id' => (string) $teacher->teacher_id,
                'employee_code' => $teacher->employee_code,
            ])
            ->values()
            ->all();
    }

    public function generateDraftAcr(int $teacherId, string $session): array
    {
        $resolvedSession = $this->resolveSession($session);
        $context = $this->sessionContext($resolvedSession);

        return $this->persistDraftAcr($teacherId, $resolvedSession, $context);
    }

    public function generateDraftAcrsForSession(string $session): array
    {
        $resolvedSession = $this->resolveSession($session);
        $context = $this->sessionContext($resolvedSession);

        $items = [];
        $created = 0;
        $updated = 0;
        $skippedFinalized = 0;

        foreach ($context['teachers'] as $teacher) {
            $result = $this->persistDraftAcr((int) $teacher->id, $resolvedSession, $context);
            $items[] = $result;

            if (($result['skipped_reason'] ?? null) === 'finalized') {
                $skippedFinalized++;
                continue;
            }

            if ((bool) ($result['created'] ?? false)) {
                $created++;
                continue;
            }

            $updated++;
        }

        return [
            'session' => $resolvedSession,
            'total' => count($items),
            'created' => $created,
            'updated' => $updated,
            'skipped_finalized' => $skippedFinalized,
            'items' => $items,
        ];
    }

    public function calculateAttendanceScore(int $teacherId, string $session): float
    {
        $resolvedSession = $this->resolveSession($session);
        $context = $this->sessionContext($resolvedSession);
        $attendanceMetric = $context['attendance'][$teacherId] ?? null;

        return $attendanceMetric['score'] ?? self::ATTENDANCE_NEUTRAL_SCORE;
    }

    public function calculateAcademicScore(int $teacherId, string $session): float
    {
        $resolvedSession = $this->resolveSession($session);
        $context = $this->sessionContext($resolvedSession);
        $academicMetric = $context['academic'][$teacherId] ?? null;

        return $academicMetric['score'] ?? 0.0;
    }

    public function calculateImprovementScore(int $teacherId, string $session): float
    {
        $resolvedSession = $this->resolveSession($session);
        $context = $this->sessionContext($resolvedSession);
        $improvementMetric = $context['improvement'][$teacherId] ?? null;

        return $improvementMetric['score'] ?? self::IMPROVEMENT_NEUTRAL_SCORE;
    }

    public function calculatePdScore(int $teacherId, string $session): float
    {
        $resolvedSession = $this->resolveSession($session);
        $context = $this->sessionContext($resolvedSession);
        $pdMetric = $context['pd'][$teacherId] ?? null;

        return $pdMetric['score'] ?? self::PD_NEUTRAL_SCORE;
    }

    public function calculateFinalGrade(float $totalScore): string
    {
        return match (true) {
            $totalScore >= 90 => 'Outstanding',
            $totalScore >= 80 => 'Very Good',
            $totalScore >= 70 => 'Good',
            $totalScore >= 60 => 'Average',
            $totalScore >= 50 => 'Weak',
            default => 'Unsatisfactory',
        };
    }

    public function savePrincipalReview(int $acrId, array $data, int $principalUserId): void
    {
        DB::transaction(function () use ($acrId, $data, $principalUserId): void {
            /** @var TeacherAcr $acr */
            $acr = TeacherAcr::query()
                ->lockForUpdate()
                ->findOrFail($acrId);

            if ($acr->status === TeacherAcr::STATUS_FINALIZED) {
                throw new RuntimeException('A finalized ACR cannot be reviewed again.');
            }

            $acr->conduct_score = $this->normalizeWeightedScore(
                (float) ($data['conduct_score'] ?? 0),
                self::CONDUCT_WEIGHT
            );
            $acr->principal_score = $this->normalizeWeightedScore(
                (float) ($data['principal_score'] ?? 0),
                self::PRINCIPAL_WEIGHT
            );
            $acr->strengths = $this->nullableText($data['strengths'] ?? null);
            $acr->areas_for_improvement = $this->nullableText($data['areas_for_improvement'] ?? null);
            $acr->recommendations = $this->nullableText($data['recommendations'] ?? null);
            $acr->confidential_remarks = $this->nullableText($data['confidential_remarks'] ?? null);
            $acr->status = TeacherAcr::STATUS_REVIEWED;
            $acr->reviewed_by = $principalUserId;
            $acr->reviewed_at = now();
            $acr->total_score = $this->recalculateTotalScore($acr);
            $acr->final_grade = $this->calculateFinalGrade((float) $acr->total_score);
            $acr->needs_refresh = false;
            $acr->last_metrics_refresh_at = now();
            $acr->save();
        });
    }

    public function finalizeAcr(int $acrId, int $principalUserId): void
    {
        DB::transaction(function () use ($acrId, $principalUserId): void {
            /** @var TeacherAcr $acr */
            $acr = TeacherAcr::query()
                ->lockForUpdate()
                ->findOrFail($acrId);

            if ($acr->status === TeacherAcr::STATUS_DRAFT) {
                throw new RuntimeException('Principal review is required before finalizing an ACR.');
            }

            if ($acr->status === TeacherAcr::STATUS_FINALIZED) {
                return;
            }

            $acr->status = TeacherAcr::STATUS_FINALIZED;
            $acr->reviewed_by ??= $principalUserId;
            $acr->reviewed_at ??= now();
            $acr->finalized_at = now();
            $acr->total_score = $this->recalculateTotalScore($acr);
            $acr->final_grade = $this->calculateFinalGrade((float) $acr->total_score);
            $acr->needs_refresh = false;
            $acr->save();
        });
    }

    public function refreshCalculatedFields(int $acrId): void
    {
        $this->applyCalculatedFieldRefresh($acrId, false);
    }

    public function refreshCalculatedFieldsFromLatestResults(int $acrId): void
    {
        $this->applyCalculatedFieldRefresh($acrId, true);
    }

    public function manualRefreshFinalizedAcr(int $acrId, int $principalUserId): void
    {
        DB::transaction(function () use ($acrId, $principalUserId): void {
            /** @var TeacherAcr $acr */
            $acr = TeacherAcr::query()
                ->lockForUpdate()
                ->findOrFail($acrId);

            if ($acr->status !== TeacherAcr::STATUS_FINALIZED) {
                throw new RuntimeException('Only finalized ACR records can be manually refreshed.');
            }

            $acr->reviewed_by ??= $principalUserId;
            $acr->reviewed_at ??= now();
            $acr->save();
        });

        $this->applyCalculatedFieldRefresh($acrId, true);
    }

    public function markNeedsRefreshIfFinalized(int $teacherId, string $session): void
    {
        $resolvedSession = $this->resolveSession($session);

        TeacherAcr::query()
            ->where('teacher_id', $teacherId)
            ->where('session', $resolvedSession)
            ->where('status', TeacherAcr::STATUS_FINALIZED)
            ->update([
                'needs_refresh' => true,
                'last_metrics_refresh_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function buildPrintableAcr(int $acrId): array
    {
        /** @var TeacherAcr $acr */
        $acr = TeacherAcr::query()
            ->with([
                'teacher:id,teacher_id,user_id,designation,employee_code',
                'teacher.user:id,name',
                'metric',
                'preparedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->findOrFail($acrId);

        $school = SchoolSetting::cached();
        $metric = $acr->metric;
        $meta = is_array($metric?->meta) ? $metric->meta : [];

        return [
            'school' => [
                'name' => $school?->school_name ?: config('app.name', 'School Management System'),
                'address' => $school?->address,
                'phone' => $school?->phone,
                'email' => $school?->email,
            ],
            'acr' => [
                'id' => (int) $acr->id,
                'session' => (string) $acr->session,
                'status' => (string) $acr->status,
                'status_label' => ucfirst((string) $acr->status),
                'total_score' => round((float) $acr->total_score, 2),
                'final_grade' => $acr->final_grade,
                'prepared_by' => $acr->preparedBy?->name,
                'reviewed_by' => $acr->reviewedBy?->name,
                'reviewed_at' => $acr->reviewed_at,
                'finalized_at' => $acr->finalized_at,
                'needs_refresh' => (bool) $acr->needs_refresh,
                'last_metrics_refresh_at' => $acr->last_metrics_refresh_at,
            ],
            'teacher' => [
                'id' => (int) ($acr->teacher?->id ?? 0),
                'name' => $this->teacherName($acr->teacher),
                'teacher_id' => (string) ($acr->teacher?->teacher_id ?? ''),
                'employee_code' => $acr->teacher?->employee_code,
                'designation' => $acr->teacher?->designation,
                'classes' => $meta['classes'] ?? [],
            ],
            'scores' => $this->printableScoreRows($acr, $metric?->attendance_percentage, $metric?->teacher_cgpa, $metric?->pass_percentage, $metric?->student_improvement_percentage, (int) ($metric?->trainings_attended ?? 0)),
            'metrics' => [
                'attendance_percentage' => $metric?->attendance_percentage,
                'teacher_cgpa' => $metric?->teacher_cgpa,
                'pass_percentage' => $metric?->pass_percentage,
                'student_improvement_percentage' => $metric?->student_improvement_percentage,
                'trainings_attended' => (int) ($metric?->trainings_attended ?? 0),
                'late_count' => (int) ($metric?->late_count ?? 0),
                'discipline_flags' => (int) ($metric?->discipline_flags ?? 0),
                'meta' => $meta,
                'notes' => $meta['notes'] ?? [],
            ],
            'narrative' => [
                'strengths' => $acr->strengths,
                'areas_for_improvement' => $acr->areas_for_improvement,
                'recommendations' => $acr->recommendations,
                'confidential_remarks' => $acr->confidential_remarks,
            ],
        ];
    }

    private function applyCalculatedFieldRefresh(int $acrId, bool $allowFinalizedOverwrite): void
    {
        DB::transaction(function () use ($acrId, $allowFinalizedOverwrite): void {
            /** @var TeacherAcr $acr */
            $acr = TeacherAcr::query()
                ->with([
                    'teacher:id,teacher_id,user_id,designation,employee_code',
                    'teacher.user:id,name,status',
                    'metric',
                ])
                ->lockForUpdate()
                ->findOrFail($acrId);

            $resolvedSession = $this->resolveSession((string) $acr->session);
            $context = $this->sessionContext($resolvedSession);

            /** @var Teacher|null $teacher */
            $teacher = $context['teachers']->get((int) $acr->teacher_id);
            if (! $teacher instanceof Teacher) {
                $teacher = Teacher::query()
                    ->with('user:id,name,status')
                    ->findOrFail((int) $acr->teacher_id, ['id', 'teacher_id', 'user_id', 'designation', 'employee_code']);
            }

            $draftPayload = $this->buildDraftPayload($teacher, $resolvedSession, $context);

            if ($acr->status === TeacherAcr::STATUS_FINALIZED && ! $allowFinalizedOverwrite) {
                $acr->needs_refresh = true;
                $acr->last_metrics_refresh_at = now();
                $acr->save();

                return;
            }

            $acr->session = $resolvedSession;
            $acr->academic_score = $draftPayload['scores']['academic_score'];
            $acr->improvement_score = $draftPayload['scores']['improvement_score'];
            $acr->total_score = $this->recalculateTotalScore($acr);
            $acr->final_grade = $this->calculateFinalGrade((float) $acr->total_score);
            $acr->needs_refresh = false;
            $acr->last_metrics_refresh_at = now();
            $acr->save();

            $acr->metric()->updateOrCreate(
                ['acr_id' => $acr->id],
                $draftPayload['metric']
            );
        });
    }

    private function printableScoreRows(
        TeacherAcr $acr,
        ?float $attendancePercentage,
        ?float $teacherCgpa,
        ?float $passPercentage,
        ?float $studentImprovementPercentage,
        int $trainingsAttended
    ): array {
        return [
            [
                'label' => 'Attendance & punctuality',
                'score' => round((float) $acr->attendance_score, 2),
                'weight' => self::ATTENDANCE_WEIGHT,
                'metric' => $attendancePercentage !== null ? number_format($attendancePercentage, 2).'%' : 'N/A',
            ],
            [
                'label' => 'Academic results / teacher CGPA',
                'score' => round((float) $acr->academic_score, 2),
                'weight' => self::ACADEMIC_WEIGHT,
                'metric' => $teacherCgpa !== null
                    ? 'CGPA '.number_format($teacherCgpa, 2).($passPercentage !== null ? ' | Pass '.number_format($passPercentage, 2).'%' : '')
                    : 'N/A',
            ],
            [
                'label' => 'Student improvement',
                'score' => round((float) $acr->improvement_score, 2),
                'weight' => self::IMPROVEMENT_WEIGHT,
                'metric' => $studentImprovementPercentage !== null
                    ? number_format($studentImprovementPercentage, 2).'% students improved'
                    : 'Insufficient longitudinal data',
            ],
            [
                'label' => 'Conduct / classroom behavior',
                'score' => round((float) $acr->conduct_score, 2),
                'weight' => self::CONDUCT_WEIGHT,
                'metric' => 'Principal-reviewed score',
            ],
            [
                'label' => 'Professional development',
                'score' => round((float) $acr->pd_score, 2),
                'weight' => self::PD_WEIGHT,
                'metric' => $trainingsAttended.' training(s)',
            ],
            [
                'label' => 'Principal review score',
                'score' => round((float) $acr->principal_score, 2),
                'weight' => self::PRINCIPAL_WEIGHT,
                'metric' => 'Principal final input',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function persistDraftAcr(int $teacherId, string $session, array $context): array
    {
        /** @var Teacher $teacher */
        $teacher = Teacher::query()
            ->with('user:id,name,status')
            ->findOrFail($teacherId, ['id', 'teacher_id', 'user_id', 'designation', 'employee_code']);

        $draftPayload = $this->buildDraftPayload($teacher, $session, $context);

        return DB::transaction(function () use ($teacher, $session, $draftPayload): array {
            /** @var TeacherAcr|null $existing */
            $existing = TeacherAcr::query()
                ->with('metric')
                ->where('teacher_id', $teacher->id)
                ->where('session', $session)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === TeacherAcr::STATUS_FINALIZED) {
                return [
                    'acr_id' => (int) $existing->id,
                    'teacher_id' => (int) $teacher->id,
                    'teacher_name' => $this->teacherName($teacher),
                    'session' => $session,
                    'status' => $existing->status,
                    'created' => false,
                    'skipped_reason' => 'finalized',
                ];
            }

            $acr = $existing ?? new TeacherAcr([
                'teacher_id' => $teacher->id,
                'session' => $session,
            ]);

            $acr->attendance_score = $draftPayload['scores']['attendance_score'];
            $acr->academic_score = $draftPayload['scores']['academic_score'];
            $acr->improvement_score = $draftPayload['scores']['improvement_score'];
            $acr->pd_score = $draftPayload['scores']['pd_score'];
            $acr->total_score = $draftPayload['scores']['total_score']
                + (float) ($existing?->conduct_score ?? 0)
                + (float) ($existing?->principal_score ?? 0);
            $acr->prepared_by = Auth::id() ?: $acr->prepared_by;

            if ($existing === null) {
                $acr->conduct_score = 0;
                $acr->principal_score = 0;
                $acr->status = TeacherAcr::STATUS_DRAFT;
                $acr->final_grade = null;
            } elseif ($acr->status !== TeacherAcr::STATUS_DRAFT) {
                $acr->final_grade = $this->calculateFinalGrade((float) $acr->total_score);
            }

            if ($existing === null || blank($acr->strengths)) {
                $acr->strengths = $draftPayload['narrative']['strengths'];
            }
            if ($existing === null || blank($acr->areas_for_improvement)) {
                $acr->areas_for_improvement = $draftPayload['narrative']['areas_for_improvement'];
            }
            if ($existing === null || blank($acr->recommendations)) {
                $acr->recommendations = $draftPayload['narrative']['recommendations'];
            }

            $acr->needs_refresh = false;
            $acr->last_metrics_refresh_at = now();
            $acr->save();

            $acr->metric()->updateOrCreate(
                ['acr_id' => $acr->id],
                $draftPayload['metric']
            );

            return [
                'acr_id' => (int) $acr->id,
                'teacher_id' => (int) $teacher->id,
                'teacher_name' => $this->teacherName($teacher),
                'session' => $session,
                'status' => $acr->status,
                'created' => $existing === null,
                'skipped_reason' => null,
            ];
        });
    }

    /**
     * @param array<string, mixed> $context
     * @return array{
     *   scores:array<string, float>,
     *   metric:array<string, mixed>,
     *   narrative:array<string, ?string>
     * }
     */
    private function buildDraftPayload(Teacher $teacher, string $session, array $context): array
    {
        $teacherId = (int) $teacher->id;
        $attendanceMetric = $context['attendance'][$teacherId] ?? [
            'attendance_percentage' => null,
            'score' => self::ATTENDANCE_NEUTRAL_SCORE,
            'source' => 'neutral_default_no_attendance_records',
            'notes' => ['No session attendance records were found for the assigned classes, so a neutral attendance baseline was applied.'],
        ];
        $academicMetric = $context['academic'][$teacherId] ?? [
            'teacher_cgpa' => null,
            'pass_percentage' => null,
            'average_percentage' => null,
            'rank' => null,
            'student_count' => 0,
            'ranking_groups' => [],
            'score' => 0.0,
            'source' => 'no_result_metrics',
            'notes' => ['No result-derived ranking metrics were available for this teacher in the selected session.'],
        ];
        $improvementMetric = $context['improvement'][$teacherId] ?? [
            'student_improvement_percentage' => null,
            'average_delta' => null,
            'eligible_students' => 0,
            'improved_students' => 0,
            'score' => self::IMPROVEMENT_NEUTRAL_SCORE,
            'source' => 'neutral_default_no_longitudinal_data',
            'notes' => ['Longitudinal exam comparison data was not available, so a neutral improvement baseline was applied.'],
        ];
        $pdMetric = $context['pd'][$teacherId] ?? [
            'trainings_attended' => 0,
            'score' => self::PD_NEUTRAL_SCORE,
            'source' => 'neutral_default_missing_training_module',
            'notes' => ['Training records are not digitized in the current system, so a neutral professional development baseline was applied.'],
        ];
        $classMetric = $context['classes'][$teacherId] ?? [
            'ids' => [],
            'labels' => [],
        ];

        $notes = array_values(array_filter(array_merge(
            $attendanceMetric['notes'] ?? [],
            $academicMetric['notes'] ?? [],
            $improvementMetric['notes'] ?? [],
            $pdMetric['notes'] ?? []
        )));

        $scores = [
            'attendance_score' => round((float) ($attendanceMetric['score'] ?? self::ATTENDANCE_NEUTRAL_SCORE), 2),
            'academic_score' => round((float) ($academicMetric['score'] ?? 0), 2),
            'improvement_score' => round((float) ($improvementMetric['score'] ?? self::IMPROVEMENT_NEUTRAL_SCORE), 2),
            'pd_score' => round((float) ($pdMetric['score'] ?? self::PD_NEUTRAL_SCORE), 2),
        ];
        $scores['total_score'] = round(array_sum($scores), 2);

        return [
            'scores' => $scores,
            'metric' => [
                'attendance_percentage' => $attendanceMetric['attendance_percentage'],
                'teacher_cgpa' => $academicMetric['teacher_cgpa'],
                'pass_percentage' => $academicMetric['pass_percentage'],
                'student_improvement_percentage' => $improvementMetric['student_improvement_percentage'],
                'trainings_attended' => (int) ($pdMetric['trainings_attended'] ?? 0),
                'late_count' => 0,
                'discipline_flags' => 0,
                'meta' => [
                    'session' => $session,
                    'classes' => $classMetric['labels'],
                    'class_ids' => $classMetric['ids'],
                    'attendance' => [
                        'percentage' => $attendanceMetric['attendance_percentage'],
                        'source' => $attendanceMetric['source'] ?? null,
                    ],
                    'academic' => [
                        'average_percentage' => $academicMetric['average_percentage'],
                        'teacher_cgpa' => $academicMetric['teacher_cgpa'],
                        'pass_percentage' => $academicMetric['pass_percentage'],
                        'rank' => $academicMetric['rank'],
                        'student_count' => $academicMetric['student_count'],
                        'ranking_groups' => $academicMetric['ranking_groups'],
                        'source' => $academicMetric['source'] ?? null,
                    ],
                    'improvement' => [
                        'student_improvement_percentage' => $improvementMetric['student_improvement_percentage'],
                        'average_delta' => $improvementMetric['average_delta'],
                        'eligible_students' => $improvementMetric['eligible_students'],
                        'improved_students' => $improvementMetric['improved_students'],
                        'source' => $improvementMetric['source'] ?? null,
                    ],
                    'professional_development' => [
                        'trainings_attended' => (int) ($pdMetric['trainings_attended'] ?? 0),
                        'source' => $pdMetric['source'] ?? null,
                    ],
                    'notes' => $notes,
                ],
            ],
            'narrative' => $this->buildNarrativeSuggestions(
                $teacher,
                $attendanceMetric,
                $academicMetric,
                $improvementMetric,
                $pdMetric,
                $classMetric
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionContext(string $session): array
    {
        if (array_key_exists($session, $this->sessionContextCache)) {
            return $this->sessionContextCache[$session];
        }

        $teachers = Teacher::query()
            ->with('user:id,name,status')
            ->where(function ($query) use ($session): void {
                $query->whereHas('assignments', fn ($assignmentQuery) => $assignmentQuery->where('session', $session))
                    ->orWhereHas('marks', fn ($markQuery) => $markQuery->where('session', $session))
                    ->orWhereHas('acrs', fn ($acrQuery) => $acrQuery->where('session', $session));
            })
            ->whereHas('user', function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', 'active');
            })
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id', 'designation', 'employee_code'])
            ->keyBy('id');

        $teacherIds = $teachers->keys()->map(fn ($value): int => (int) $value)->all();
        $classesByTeacher = $this->teacherClassMetrics($session, $teacherIds);

        $context = [
            'teachers' => $teachers,
            'classes' => $classesByTeacher,
            'attendance' => $this->attendanceMetrics($session, $classesByTeacher),
            'academic' => $this->academicMetrics($session),
            'improvement' => $this->improvementMetrics($session, $teacherIds),
            'pd' => $this->pdMetrics($teacherIds),
        ];

        $this->sessionContextCache[$session] = $context;

        return $context;
    }

    /**
     * @param array<int, int> $teacherIds
     * @return array<int, array{ids:array<int, int>,labels:array<int, string>}>
     */
    private function teacherClassMetrics(string $session, array $teacherIds): array
    {
        if ($teacherIds === []) {
            return [];
        }

        $assignmentClasses = TeacherAssignment::query()
            ->join('school_classes as c', 'c.id', '=', 'teacher_assignments.class_id')
            ->where('teacher_assignments.session', $session)
            ->whereIn('teacher_assignments.teacher_id', $teacherIds)
            ->select([
                'teacher_assignments.teacher_id',
                'teacher_assignments.class_id',
                'c.name',
                'c.section',
            ])
            ->distinct()
            ->get();

        $markClasses = Mark::query()
            ->join('exams as e', 'e.id', '=', 'marks.exam_id')
            ->join('school_classes as c', 'c.id', '=', 'e.class_id')
            ->where('marks.session', $session)
            ->whereIn('marks.teacher_id', $teacherIds)
            ->selectRaw('DISTINCT marks.teacher_id as teacher_id, e.class_id as class_id, c.name as name, c.section as section')
            ->get();

        return $assignmentClasses
            ->merge($markClasses)
            ->unique(fn ($row): string => (int) $row->teacher_id.'|'.(int) $row->class_id)
            ->groupBy('teacher_id')
            ->map(function (Collection $rows): array {
                $labels = $rows
                    ->map(fn ($row): string => trim((string) $row->name.' '.(string) ($row->section ?? '')))
                    ->filter(fn (string $label): bool => trim($label) !== '')
                    ->unique()
                    ->sort(fn (string $left, string $right): int => strnatcasecmp($left, $right))
                    ->values()
                    ->all();

                $ids = $rows
                    ->pluck('class_id')
                    ->map(fn ($value): int => (int) $value)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'ids' => $ids,
                    'labels' => $labels,
                ];
            })
            ->mapWithKeys(fn (array $row, $teacherId): array => [(int) $teacherId => $row])
            ->all();
    }

    /**
     * @param array<int, array{ids:array<int, int>,labels:array<int, string>}> $classesByTeacher
     * @return array<int, array<string, mixed>>
     */
    private function attendanceMetrics(string $session, array $classesByTeacher): array
    {
        $classIds = collect($classesByTeacher)
            ->pluck('ids')
            ->flatten()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($classIds === []) {
            return [];
        }

        [$sessionStart, $sessionEnd] = $this->sessionDateRange($session);

        $attendanceByClass = Attendance::query()
            ->whereIn('class_id', $classIds)
            ->whereBetween('date', [$sessionStart->toDateString(), $sessionEnd->toDateString()])
            ->selectRaw("
                class_id,
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_records
            ")
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

        $metrics = [];
        foreach ($classesByTeacher as $teacherId => $classMetric) {
            $total = 0;
            $present = 0;

            foreach ($classMetric['ids'] as $classId) {
                $row = $attendanceByClass->get($classId);
                $total += (int) ($row->total_records ?? 0);
                $present += (int) ($row->present_records ?? 0);
            }

            $percentage = $total > 0 ? round(($present * 100.0) / $total, 2) : null;
            $score = $percentage !== null
                ? round(($this->clampPercent($percentage) / 100.0) * self::ATTENDANCE_WEIGHT, 2)
                : self::ATTENDANCE_NEUTRAL_SCORE;

            $metrics[(int) $teacherId] = [
                'attendance_percentage' => $percentage,
                'score' => $score,
                'source' => $percentage !== null ? 'class_attendance_coverage' : 'neutral_default_no_attendance_records',
                'notes' => $percentage === null
                    ? ['No session attendance records were found for the assigned classes, so a neutral attendance baseline was applied.']
                    : [],
            ];
        }

        return $metrics;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function academicMetrics(string $session): array
    {
        $snapshot = $this->teacherRankingService->snapshot($session);
        $rows = collect($snapshot['overall'] ?? []);

        return $rows
            ->groupBy(fn (array $row): int => (int) $row['teacher_id'])
            ->map(function (Collection $teacherRows) use ($snapshot): array {
                /** @var array<string, mixed> $first */
                $first = $teacherRows->first();
                $studentCount = (int) $teacherRows->sum(fn (array $row): int => (int) ($row['student_count'] ?? 0));

                $teacherCgpa = $studentCount > 0
                    ? round(
                        $teacherRows->sum(fn (array $row): float => (float) ($row['cgpa'] ?? 0) * (int) ($row['student_count'] ?? 0)) / $studentCount,
                        2
                    )
                    : null;
                $passPercentage = $studentCount > 0
                    ? round(
                        $teacherRows->sum(fn (array $row): float => (float) ($row['pass_percentage'] ?? 0) * (int) ($row['student_count'] ?? 0)) / $studentCount,
                        2
                    )
                    : null;
                $averagePercentage = $studentCount > 0
                    ? round(
                        $teacherRows->sum(fn (array $row): float => (float) ($row['average_percentage'] ?? 0) * (int) ($row['student_count'] ?? 0)) / $studentCount,
                        2
                    )
                    : null;

                $academicPercent = null;
                if ($teacherCgpa !== null && $passPercentage !== null) {
                    $academicPercent = (($teacherCgpa / 6.0) * 70.0) + ($passPercentage * 0.30);
                } elseif ($averagePercentage !== null && $passPercentage !== null) {
                    $academicPercent = ($averagePercentage * 0.60) + ($passPercentage * 0.40);
                } elseif ($averagePercentage !== null) {
                    $academicPercent = $averagePercentage;
                }

                return [
                    'teacher_cgpa' => $teacherCgpa,
                    'pass_percentage' => $passPercentage,
                    'average_percentage' => $averagePercentage,
                    'rank' => $teacherRows->count() === 1 ? ($first['rank_position'] ?? null) : null,
                    'student_count' => $studentCount,
                    'ranking_groups' => $teacherRows
                        ->pluck('ranking_group_label')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                    'score' => $academicPercent !== null
                        ? round(($this->clampPercent($academicPercent) / 100.0) * self::ACADEMIC_WEIGHT, 2)
                        : 0.0,
                    'source' => (string) ($snapshot['data_source'] ?? 'snapshot'),
                    'notes' => $studentCount > 0
                        ? []
                        : ['No result-derived ranking metrics were available for this teacher in the selected session.'],
                ];
            })
            ->mapWithKeys(fn (array $row, $teacherId): array => [(int) $teacherId => $row])
            ->all();
    }

    /**
     * @param array<int, int> $teacherIds
     * @return array<int, array<string, mixed>>
     */
    private function improvementMetrics(string $session, array $teacherIds): array
    {
        if ($teacherIds === []) {
            return [];
        }

        $examOrder = $this->examOrder();

        $rows = Mark::query()
            ->join('exams as e', 'e.id', '=', 'marks.exam_id')
            ->join('school_classes as c', 'c.id', '=', 'e.class_id')
            ->where('marks.session', $session)
            ->whereIn('marks.teacher_id', $teacherIds)
            ->whereIn('e.exam_type', array_keys($examOrder))
            ->select([
                'marks.teacher_id',
                'marks.student_id',
                'marks.obtained_marks',
                'marks.total_marks',
                'marks.grade',
                'e.exam_type',
                'c.name as class_name',
            ])
            ->get()
            ->map(function ($row): ?array {
                $percentage = $this->comparablePercentage(
                    (string) $row->class_name,
                    $row->grade,
                    $row->obtained_marks,
                    $row->total_marks
                );

                if ($percentage === null) {
                    return null;
                }

                return [
                    'teacher_id' => (int) $row->teacher_id,
                    'student_id' => (int) $row->student_id,
                    'exam_type' => (string) $row->exam_type,
                    'percentage' => $percentage,
                ];
            })
            ->filter()
            ->values();

        $metrics = [];
        $grouped = $rows->groupBy(fn (array $row): string => $row['teacher_id'].'|'.$row['student_id']);

        foreach ($teacherIds as $teacherId) {
            $metrics[$teacherId] = [
                'student_improvement_percentage' => null,
                'average_delta' => null,
                'eligible_students' => 0,
                'improved_students' => 0,
                'score' => self::IMPROVEMENT_NEUTRAL_SCORE,
                'source' => 'neutral_default_no_longitudinal_data',
                'notes' => ['Longitudinal exam comparison data was not available, so a neutral improvement baseline was applied.'],
            ];
        }

        $byTeacher = [];
        foreach ($grouped as $rowsForStudent) {
            /** @var array<string, float> $averages */
            $averages = $rowsForStudent
                ->groupBy('exam_type')
                ->map(fn (Collection $examRows): float => round((float) $examRows->avg('percentage'), 4))
                ->all();

            $orderedTypes = collect($examOrder)
                ->sort()
                ->keys()
                ->values();

            $firstType = $orderedTypes->first(fn (string $examType): bool => array_key_exists($examType, $averages));
            $lastType = $orderedTypes->reverse()->first(fn (string $examType): bool => array_key_exists($examType, $averages));

            if (! is_string($firstType) || ! is_string($lastType) || $firstType === $lastType) {
                continue;
            }

            $teacherId = (int) ($rowsForStudent->first()['teacher_id'] ?? 0);
            $first = (float) $averages[$firstType];
            $last = (float) $averages[$lastType];

            $byTeacher[$teacherId]['eligible_students'] = (int) ($byTeacher[$teacherId]['eligible_students'] ?? 0) + 1;
            $byTeacher[$teacherId]['improved_students'] = (int) ($byTeacher[$teacherId]['improved_students'] ?? 0) + ($last > $first ? 1 : 0);
            $byTeacher[$teacherId]['deltas'][] = round($last - $first, 4);
        }

        foreach ($byTeacher as $teacherId => $summary) {
            $eligibleStudents = (int) ($summary['eligible_students'] ?? 0);
            $improvedStudents = (int) ($summary['improved_students'] ?? 0);
            $deltas = collect($summary['deltas'] ?? []);
            $improvementPercentage = $eligibleStudents > 0
                ? round(($improvedStudents * 100.0) / $eligibleStudents, 2)
                : null;

            $metrics[(int) $teacherId] = [
                'student_improvement_percentage' => $improvementPercentage,
                'average_delta' => $deltas->isNotEmpty() ? round((float) $deltas->avg(), 2) : null,
                'eligible_students' => $eligibleStudents,
                'improved_students' => $improvedStudents,
                'score' => $improvementPercentage !== null
                    ? round(($this->clampPercent($improvementPercentage) / 100.0) * self::IMPROVEMENT_WEIGHT, 2)
                    : self::IMPROVEMENT_NEUTRAL_SCORE,
                'source' => $improvementPercentage !== null ? 'student_exam_progression' : 'neutral_default_no_longitudinal_data',
                'notes' => $improvementPercentage !== null
                    ? []
                    : ['Longitudinal exam comparison data was not available, so a neutral improvement baseline was applied.'],
            ];
        }

        return $metrics;
    }

    /**
     * @param array<int, int> $teacherIds
     * @return array<int, array<string, mixed>>
     */
    private function pdMetrics(array $teacherIds): array
    {
        $metrics = [];

        foreach ($teacherIds as $teacherId) {
            $metrics[$teacherId] = [
                'trainings_attended' => 0,
                'score' => self::PD_NEUTRAL_SCORE,
                'source' => 'neutral_default_missing_training_module',
                'notes' => ['Training records are not digitized in the current system, so a neutral professional development baseline was applied.'],
            ];
        }

        return $metrics;
    }

    /**
     * @param array<string, mixed> $attendanceMetric
     * @param array<string, mixed> $academicMetric
     * @param array<string, mixed> $improvementMetric
     * @param array<string, mixed> $pdMetric
     * @param array<string, mixed> $classMetric
     * @return array<string, string>
     */
    private function buildNarrativeSuggestions(
        Teacher $teacher,
        array $attendanceMetric,
        array $academicMetric,
        array $improvementMetric,
        array $pdMetric,
        array $classMetric
    ): array {
        $strengths = [];
        $areas = [];
        $recommendations = [];

        $attendancePercentage = $attendanceMetric['attendance_percentage'] ?? null;
        $averagePercentage = $academicMetric['average_percentage'] ?? null;
        $passPercentage = $academicMetric['pass_percentage'] ?? null;
        $teacherCgpa = $academicMetric['teacher_cgpa'] ?? null;
        $improvementPercentage = $improvementMetric['student_improvement_percentage'] ?? null;
        $classes = $classMetric['labels'] ?? [];

        if ($attendancePercentage !== null && $attendancePercentage >= 90) {
            $strengths[] = 'Maintains strong instructional continuity across assigned classes with excellent attendance coverage.';
        }
        if ($teacherCgpa !== null && $teacherCgpa >= 5.00) {
            $strengths[] = 'Produces strong academic outcomes, reflected in a teacher CGPA of '.number_format((float) $teacherCgpa, 2).' and stable class performance.';
        } elseif ($averagePercentage !== null && $averagePercentage >= 80) {
            $strengths[] = 'Delivers above-benchmark academic results with an average student performance of '.number_format((float) $averagePercentage, 2).'%.';
        }
        if ($improvementPercentage !== null && $improvementPercentage >= 60) {
            $strengths[] = number_format((float) $improvementPercentage, 2).'% of tracked students improved between the first and latest available exams.';
        }
        if ($classes !== []) {
            $strengths[] = 'Handled assigned teaching responsibilities in '.implode(', ', array_slice($classes, 0, 4)).'.';
        }
        if ($strengths === []) {
            $strengths[] = $this->teacherName($teacher).' maintained steady instructional responsibilities during the session and provides a workable baseline for further development.';
        }

        if ($attendancePercentage !== null && $attendancePercentage < 85) {
            $areas[] = 'Needs stronger consistency in class attendance coverage and day-to-day instructional continuity.';
            $recommendations[] = 'Monitor attendance and punctuality patterns monthly and set a short-cycle improvement target with the principal.';
        }
        if (($passPercentage !== null && $passPercentage < 75) || ($averagePercentage !== null && $averagePercentage < 70)) {
            $areas[] = 'Requires closer attention to result quality, assessment preparation, and targeted remediation for low-performing learners.';
            $recommendations[] = 'Prepare a data-led subject intervention plan using pass-rate and class-wise result trends for the next review cycle.';
        }
        if ($improvementPercentage !== null && $improvementPercentage < 50) {
            $areas[] = 'Student growth is not yet consistent enough across the session and needs sharper follow-up after each assessment.';
            $recommendations[] = 'Introduce structured post-exam remediation and progress tracking for students whose scores remain static.';
        }
        if (($pdMetric['source'] ?? null) === 'neutral_default_missing_training_module') {
            $areas[] = 'Professional development activity is not yet fully documented inside the system.';
            $recommendations[] = 'Record trainings, workshops, mentoring, and peer-learning activity in the system once staff development tracking is available.';
        }
        if ($areas === []) {
            $areas[] = 'No major automated concern was detected from the available performance data; principal review should confirm conduct, leadership, and professional habits.';
        }
        if ($recommendations === []) {
            $recommendations[] = 'Maintain current performance standards and continue evidence-based teaching support for weaker learners.';
        }

        return [
            'strengths' => implode("\n", $this->uniqueSentences($strengths)),
            'areas_for_improvement' => implode("\n", $this->uniqueSentences($areas)),
            'recommendations' => implode("\n", $this->uniqueSentences($recommendations)),
        ];
    }

    private function recalculateTotalScore(TeacherAcr $acr): float
    {
        return round(
            (float) $acr->attendance_score
            + (float) $acr->academic_score
            + (float) $acr->improvement_score
            + (float) $acr->conduct_score
            + (float) $acr->pd_score
            + (float) $acr->principal_score,
            2
        );
    }

    private function teacherName(?Teacher $teacher): string
    {
        if ($teacher === null) {
            return 'Teacher';
        }

        return (string) ($teacher->user?->name ?: ('Teacher '.$teacher->teacher_id));
    }

    private function clampPercent(float $value): float
    {
        return round(max(0.0, min(100.0, $value)), 2);
    }

    private function normalizeWeightedScore(float $value, float $weight): float
    {
        return round(max(0.0, min($weight, $value)), 2);
    }

    private function nullableText(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array<int, string>
     */
    private function uniqueSentences(array $values): array
    {
        return collect($values)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function comparablePercentage(
        string $className,
        mixed $grade,
        mixed $obtainedMarks,
        mixed $totalMarks
    ): ?float {
        if ($this->assessmentModeService->classUsesGradeSystem($className)) {
            $normalizedGrade = $this->assessmentModeService->normalizeGrade(is_string($grade) ? $grade : null);
            if ($normalizedGrade === null) {
                return null;
            }

            return round($this->gradeScaleService->getPercentageEquivalent($normalizedGrade), 4);
        }

        if ($obtainedMarks === null || $totalMarks === null || (float) $totalMarks <= 0) {
            return null;
        }

        return round((((float) $obtainedMarks) * 100.0) / (float) $totalMarks, 4);
    }

    /**
     * @return array<string, int>
     */
    private function examOrder(): array
    {
        return [
            'class_test' => 1,
            'bimonthly_test' => 2,
            'first_term' => 3,
            'final_term' => 4,
        ];
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

        return [
            Carbon::create($startYear, 7, 1)->startOfDay(),
            Carbon::create($endYear, 6, 30)->endOfDay(),
        ];
    }
}
