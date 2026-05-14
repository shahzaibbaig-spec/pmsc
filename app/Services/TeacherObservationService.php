<?php

namespace App\Services;

use App\Models\LessonObservation;
use App\Models\LessonObservationItem;
use App\Models\NotebookObservation;
use App\Models\NotebookObservationItem;
use App\Models\SchoolClass;
use App\Models\SectionHeadAssignment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\ObservationPendingCommentNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TeacherObservationService
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService,
        private readonly SectionHeadAssignmentService $sectionHeadAssignmentService,
        private readonly TeacherRankingService $teacherRankingService,
        private readonly TeacherPerformanceService $teacherPerformanceService
    ) {
    }

    /**
     * @var array<string, array<int, string>>
     */
    private const LESSON_STANDARDS = [
        'Lesson Planning' => [
            'Uses KORT prescribed format',
            'Planning is innovative and purposeful',
            'Learning objectives are clearly stated with planned activities relevant to the LO',
            'All sections are realistically timed',
            'There are meaningful cross-curricular links',
            'Curriculum adapted/modified to meet the needs of all students',
            'Lesson evaluations are critical and inform subsequent planning',
        ],
        'Teaching' => [
            'Demonstrates excellent subject knowledge',
            'Uses variety of strategies to meet the needs of individuals and groups of students',
            'Communicates clear learning outcomes',
            'Provides clear instructions',
            'Provides challenging work and appropriate support',
            'Uses questioning and dialogue to promote higher order thinking and independent learning',
            'Relevant cross-curricular links managed well',
            'Models language that enables students to communicate effectively',
        ],
        'Classroom Management' => [
            'Organises enabling learning environment and resources',
            'Demonstrates withitness and monitors student activities vigilantly',
            'Demonstrates effective time management',
            'Ensures smooth transitions between sections',
            'Ensures equal opportunities and equality of access for all students',
            'Promotes positive relationships',
        ],
        'Student Engagement' => [
            'Students are actively engaged and focused',
            'Students are independent learners',
            'Students interact and collaborate effectively',
            'Students make meaningful connections',
            'Students communicate effectively with age and level appropriate vocabulary',
            'Students are innovative and enterprising',
            'Students demonstrate critical thinking and problem solving skills',
            'Students respond well to constructive feedback',
            'Students reflect on their learning',
            'Students are self-disciplined',
            'Relationships amongst students and staff are respectful',
        ],
        'Assessment For Learning' => [
            'Teacher sets high expectations for all students',
            'Teacher communicates success criteria for each task',
            'Teacher uses effective formative assessment methods',
            'Assessments are clearly linked with the LO',
            'Teacher provides constructive feedback',
            'Teacher provides opportunities for self and peer assessment',
        ],
        'Care & Classroom Routines' => [
            'Children have positive relationships with peers and adults',
            'Children model caring and sharing attributes',
            'Children use courtesy words',
            'Children listen and follow instructions',
            'Children take turns to express ideas',
            'Children demonstrate independence in hygiene and seeking help',
            'Teacher is sensitive towards children’s social, emotional, and personal needs',
        ],
    ];

    /**
     * @var array<int, string>
     */
    private const NOTEBOOK_CHECKLIST = [
        'Information on the title page complete',
        'Indices are regularly filled in by the students',
        'Indices are regularly signed by the teacher',
        'Indices are regularly signed by the parents',
        '1st term / 2nd term pointers made with term dates',
        'Date written',
        'Topic written and underlined',
        'Work presentation neat and tidy',
        'Corrections by the students being done regularly',
        'Does the notebook show progress of work?',
        'Marking criteria is pasted',
        'Quality of work: Errors circled/underlined',
        'Quality of work: All pages checked',
        'Quality of work: Overwriting',
        'Quality of work: Legible writing',
        'Quantity of work: Up to date',
        'Quantity of work: Work completed',
        'Quantity of work: Correction is done by the teacher',
        'Cleanliness: Soiled',
        'Cleanliness: Ink marks',
        'Cleanliness: Scribbling',
        'Cleanliness: Torn pages',
        'Cleanliness: Neat and tidy',
        'Teacher feedback: Traditional',
        'Teacher feedback: Specific',
        'Mistakes overlooked: Often',
        'Mistakes overlooked: Seldom',
        'Mistakes overlooked: Not overlooked',
        'Checking: Thorough',
        'Checking: Careful',
    ];

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array{id:int,name:string,email:string,teacher_profile_id:int,teacher_code:string,employee_code:string,assignment_classes:string}>
     */
    public function searchTeachersForObserver(User $observer, string $term, array $filters = []): array
    {
        $needle = trim($term);
        if ($needle === '' || mb_strlen($needle) < 2) {
            return [];
        }

        $session = $this->resolveSession(isset($filters['session']) ? (string) $filters['session'] : null);
        $limit = isset($filters['limit']) ? max(5, min((int) $filters['limit'], 50)) : 20;
        $contains = '%'.$needle.'%';

        $query = User::query()
            ->role('Teacher')
            ->where(function (Builder $builder) use ($contains): void {
                $builder->where('name', 'like', $contains)
                    ->orWhere('email', 'like', $contains)
                    ->orWhereHas('teacher', function (Builder $teacherQuery) use ($contains): void {
                        $teacherQuery->where('teacher_id', 'like', $contains)
                            ->orWhere('employee_code', 'like', $contains);
                    });
            });

        $allowedUserIds = $this->allowedTeacherUserIdsForObserver($observer, $session);
        if ($allowedUserIds !== null) {
            if ($allowedUserIds === []) {
                return [];
            }

            $query->whereIn('id', $allowedUserIds);
        }

        $users = $query
            ->with('teacher:id,user_id,teacher_id,employee_code')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email']);

        $teacherProfileIds = $users
            ->pluck('teacher.id')
            ->filter(static fn ($id): bool => $id !== null)
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        $classMap = $this->teacherClassLabelsForSession($teacherProfileIds, $session);

        return $users
            ->map(function (User $user) use ($classMap): array {
                $teacherProfileId = (int) ($user->teacher?->id ?? 0);
                $classes = $classMap[$teacherProfileId] ?? [];

                return [
                    'id' => (int) $user->id,
                    'name' => (string) ($user->name ?? ''),
                    'email' => (string) ($user->email ?? ''),
                    'teacher_profile_id' => $teacherProfileId,
                    'teacher_code' => (string) ($user->teacher?->teacher_id ?? ''),
                    'employee_code' => (string) ($user->teacher?->employee_code ?? ''),
                    'assignment_classes' => $classes !== [] ? implode(', ', $classes) : '-',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createLessonObservation(array $data, User $observer): LessonObservation
    {
        $session = $this->resolveSession(isset($data['session']) ? (string) $data['session'] : null);
        $teacher = User::query()
            ->with('teacher:id,user_id')
            ->findOrFail((int) $data['observed_teacher_id']);

        $this->assertObserverCanObserveTeacher($observer, $teacher, $session);

        $observationDate = isset($data['observation_date']) && trim((string) $data['observation_date']) !== ''
            ? Carbon::parse((string) $data['observation_date'])->toDateString()
            : now()->toDateString();

        $progress = isset($data['progress_percentage']) && $data['progress_percentage'] !== ''
            ? (float) $data['progress_percentage']
            : null;

        $itemsPayload = $this->sanitizeLessonItems(isset($data['items']) && is_array($data['items']) ? $data['items'] : []);
        if ($itemsPayload === []) {
            throw ValidationException::withMessages([
                'items' => 'Please provide at least one lesson observation standard row.',
            ]);
        }

        $score = $this->calculateLessonScore($itemsPayload, $progress);

        /** @var LessonObservation $observation */
        $observation = DB::transaction(function () use (
            $data,
            $observer,
            $teacher,
            $session,
            $observationDate,
            $progress,
            $itemsPayload,
            $score
        ): LessonObservation {
            $observerRole = $this->observerRoleLabel($observer);
            $classId = isset($data['class_id']) && $data['class_id'] !== '' ? (int) $data['class_id'] : null;

            $observation = LessonObservation::query()->create([
                'observed_teacher_id' => (int) $teacher->id,
                'observer_id' => (int) $observer->id,
                'observer_role' => $observerRole,
                'session' => $session,
                'observation_date' => $observationDate,
                'school' => $this->nullableString($data['school'] ?? null),
                'subject_topic' => $this->nullableString($data['subject_topic'] ?? null),
                'class_id' => $classId,
                'class_section' => $this->nullableString($data['class_section'] ?? null),
                'no_of_students' => isset($data['no_of_students']) && $data['no_of_students'] !== '' ? (int) $data['no_of_students'] : null,
                'learning_objectives' => $this->nullableString($data['learning_objectives'] ?? null),
                'previous_targets' => $this->nullableString($data['previous_targets'] ?? null),
                'what_went_well' => $this->nullableString($data['what_went_well'] ?? null),
                'even_better_if' => $this->nullableString($data['even_better_if'] ?? null),
                'progress_percentage' => $progress,
                'overall_judgment' => $score['overall_judgment'],
                'total_marks' => $score['total_marks'],
                'max_marks' => $score['max_marks'],
                'performance_score' => $score['performance_score'],
                'status' => LessonObservation::STATUS_SUBMITTED,
                'teacher_signature_acknowledged' => false,
                'observer_signature_acknowledged' => (bool) ($data['observer_signature_acknowledged'] ?? false),
                'created_by' => (int) $observer->id,
                'updated_by' => (int) $observer->id,
            ]);

            $observation->items()->createMany($itemsPayload);

            $this->teacherPerformanceService->recordObservationPerformance(
                (int) $teacher->id,
                \App\Models\TeacherPerformanceEvent::SOURCE_LESSON_OBSERVATION,
                (int) $observation->id,
                $session,
                (float) $score['total_marks'],
                (float) $score['max_marks'],
                (float) $score['performance_score'],
                (string) ($score['overall_judgment'] ?? ''),
                'Lesson observation recorded.',
                $observer
            );

            $this->notifyTeacherForComment('lesson', $observation, $observer);

            return $observation;
        });

        return $observation->fresh($this->lessonObservationRelations());
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createNotebookObservation(array $data, User $observer): NotebookObservation
    {
        $session = $this->resolveSession(isset($data['session']) ? (string) $data['session'] : null);
        $teacher = User::query()
            ->with('teacher:id,user_id')
            ->findOrFail((int) $data['observed_teacher_id']);

        $this->assertObserverCanObserveTeacher($observer, $teacher, $session);

        $observationDate = isset($data['observation_date']) && trim((string) $data['observation_date']) !== ''
            ? Carbon::parse((string) $data['observation_date'])->toDateString()
            : now()->toDateString();

        $itemsPayload = $this->sanitizeNotebookItems(isset($data['items']) && is_array($data['items']) ? $data['items'] : []);
        if ($itemsPayload === []) {
            throw ValidationException::withMessages([
                'items' => 'Please provide at least one notebook checklist row.',
            ]);
        }

        $score = $this->calculateNotebookScore($itemsPayload);

        /** @var NotebookObservation $observation */
        $observation = DB::transaction(function () use (
            $data,
            $observer,
            $teacher,
            $session,
            $observationDate,
            $itemsPayload,
            $score
        ): NotebookObservation {
            $observerRole = $this->observerRoleLabel($observer);
            $classId = isset($data['class_id']) && $data['class_id'] !== '' ? (int) $data['class_id'] : null;
            $subjectId = isset($data['subject_id']) && $data['subject_id'] !== '' ? (int) $data['subject_id'] : null;

            $observation = NotebookObservation::query()->create([
                'observed_teacher_id' => (int) $teacher->id,
                'observer_id' => (int) $observer->id,
                'observer_role' => $observerRole,
                'session' => $session,
                'observation_date' => $observationDate,
                'class_id' => $classId,
                'class_section' => $this->nullableString($data['class_section'] ?? null),
                'subject_id' => $subjectId,
                'total_students' => isset($data['total_students']) && $data['total_students'] !== '' ? (int) $data['total_students'] : null,
                'notebooks_provided' => isset($data['notebooks_provided']) && $data['notebooks_provided'] !== '' ? (int) $data['notebooks_provided'] : null,
                'covered_notebooks' => isset($data['covered_notebooks']) && $data['covered_notebooks'] !== '' ? (int) $data['covered_notebooks'] : null,
                'uncovered_notebooks' => isset($data['uncovered_notebooks']) && $data['uncovered_notebooks'] !== '' ? (int) $data['uncovered_notebooks'] : null,
                'well_maintained' => isset($data['well_maintained']) && $data['well_maintained'] !== '' ? (int) $data['well_maintained'] : null,
                'general_comments' => $this->nullableString($data['general_comments'] ?? null),
                'total_yes' => (int) $score['total_yes'],
                'total_no' => (int) $score['total_no'],
                'performance_score' => (float) $score['performance_score'],
                'status' => NotebookObservation::STATUS_SUBMITTED,
                'created_by' => (int) $observer->id,
                'updated_by' => (int) $observer->id,
            ]);

            $observation->items()->createMany($itemsPayload);

            $this->teacherPerformanceService->recordObservationPerformance(
                (int) $teacher->id,
                \App\Models\TeacherPerformanceEvent::SOURCE_NOTEBOOK_OBSERVATION,
                (int) $observation->id,
                $session,
                (float) $score['total_yes'],
                (float) $score['applicable_items'],
                (float) $score['performance_score'],
                null,
                'Notebook observation recorded.',
                $observer
            );

            $this->notifyTeacherForComment('notebook', $observation, $observer);

            return $observation;
        });

        return $observation->fresh($this->notebookObservationRelations());
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{
     *   total_marks:float,
     *   max_marks:float,
     *   performance_score:float,
     *   area_scores:array<string, array{score:int,max:int,judgment:string}>,
     *   progress_judgment:string,
     *   overall_judgment:string
     * }
     */
    public function calculateLessonScore(array $items, ?float $progressPercentage = null): array
    {
        $areaScores = [];
        $totalMarks = 0.0;
        $maxMarks = 0.0;

        foreach ($items as $item) {
            $area = trim((string) ($item['area'] ?? ''));
            if ($area === '') {
                continue;
            }

            $mark = max(0, (int) ($item['mark'] ?? 0));
            $maxMark = max(1, (int) ($item['max_mark'] ?? 1));

            if (! isset($areaScores[$area])) {
                $areaScores[$area] = [
                    'score' => 0,
                    'max' => 0,
                    'judgment' => LessonObservation::JUDGMENT_ACCEPTABLE,
                ];
            }

            $areaScores[$area]['score'] += $mark;
            $areaScores[$area]['max'] += $maxMark;
            $totalMarks += $mark;
            $maxMarks += $maxMark;
        }

        foreach ($areaScores as $area => $summary) {
            $areaScores[$area]['judgment'] = $this->areaJudgment($area, (int) $summary['score']);
        }

        $progress = $progressPercentage !== null ? max(0.0, min(100.0, $progressPercentage)) : null;
        $progressJudgment = $this->progressJudgment($progress);
        $overall = $this->overallLessonJudgment($areaScores, $progressJudgment);
        $performance = $maxMarks > 0 ? round(($totalMarks / $maxMarks) * 100, 2) : 0.0;

        return [
            'total_marks' => round($totalMarks, 2),
            'max_marks' => round($maxMarks, 2),
            'performance_score' => $performance,
            'area_scores' => $areaScores,
            'progress_judgment' => $progressJudgment,
            'overall_judgment' => $overall,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{total_yes:int,total_no:int,applicable_items:int,performance_score:float}
     */
    public function calculateNotebookScore(array $items): array
    {
        $yes = 0;
        $no = 0;
        $applicable = 0;

        foreach ($items as $item) {
            $response = trim(strtolower((string) ($item['response'] ?? '')));
            if ($response === NotebookObservation::RESPONSE_NA || $response === '') {
                continue;
            }

            $applicable++;
            if ($response === NotebookObservation::RESPONSE_YES) {
                $yes++;
            } elseif ($response === NotebookObservation::RESPONSE_NO) {
                $no++;
            }
        }

        $percentage = $applicable > 0 ? round(($yes / $applicable) * 100, 2) : 0.0;

        return [
            'total_yes' => $yes,
            'total_no' => $no,
            'applicable_items' => $applicable,
            'performance_score' => $percentage,
        ];
    }

    public function notifyTeacherForComment(string $type, LessonObservation|NotebookObservation $observation, User $observer): void
    {
        $teacher = $type === 'lesson'
            ? $observation->observedTeacher
            : $observation->observedTeacher;

        if (! $teacher instanceof User) {
            return;
        }

        $teacher->notify(new ObservationPendingCommentNotification($type, $observation, $observer));
    }

    /**
     * @return array<int, array{
     *   type:string,
     *   id:int,
     *   observation_type_label:string,
     *   observer_name:string,
     *   date:string,
     *   title:string
     * }>
     */
    public function getPendingObservationCommentsForTeacher(User $teacher): array
    {
        if (! Schema::hasTable('lesson_observations') || ! Schema::hasTable('notebook_observations')) {
            return [];
        }

        $pendingLesson = LessonObservation::query()
            ->with('observer:id,name')
            ->where('observed_teacher_id', (int) $teacher->id)
            ->whereNull('teacher_commented_at')
            ->orderByDesc('observation_date')
            ->orderByDesc('id')
            ->get(['id', 'observer_id', 'observation_date']);

        $pendingNotebook = NotebookObservation::query()
            ->with('observer:id,name')
            ->where('observed_teacher_id', (int) $teacher->id)
            ->whereNull('teacher_commented_at')
            ->orderByDesc('observation_date')
            ->orderByDesc('id')
            ->get(['id', 'observer_id', 'observation_date']);

        return collect()
            ->merge($pendingLesson->map(function (LessonObservation $observation): array {
                return [
                    'type' => 'lesson',
                    'id' => (int) $observation->id,
                    'observation_type_label' => 'Lesson Observation',
                    'observer_name' => (string) ($observation->observer?->name ?? 'Observer'),
                    'date' => optional($observation->observation_date)->toDateString() ?? now()->toDateString(),
                    'title' => 'Lesson Observation pending your comments',
                ];
            }))
            ->merge($pendingNotebook->map(function (NotebookObservation $observation): array {
                return [
                    'type' => 'notebook',
                    'id' => (int) $observation->id,
                    'observation_type_label' => 'Notebook Observation',
                    'observer_name' => (string) ($observation->observer?->name ?? 'Observer'),
                    'date' => optional($observation->observation_date)->toDateString() ?? now()->toDateString(),
                    'title' => 'Notebook Observation pending your comments',
                ];
            }))
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function submitTeacherComment(string $type, int $id, string $comment, User $teacher): LessonObservation|NotebookObservation
    {
        $trimmed = trim($comment);
        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'teacher_comments' => 'Comment is required.',
            ]);
        }

        if ($type === 'lesson') {
            $observation = LessonObservation::query()
                ->whereKey($id)
                ->where('observed_teacher_id', (int) $teacher->id)
                ->firstOrFail();

            $observation->forceFill([
                'teacher_comments' => $trimmed,
                'teacher_commented_at' => now(),
                'teacher_signature_acknowledged' => true,
                'status' => LessonObservation::STATUS_COMMENTED,
                'updated_by' => (int) $teacher->id,
            ])->save();

            return $observation->fresh($this->lessonObservationRelations());
        }

        if ($type === 'notebook') {
            $observation = NotebookObservation::query()
                ->whereKey($id)
                ->where('observed_teacher_id', (int) $teacher->id)
                ->firstOrFail();

            $observation->forceFill([
                'teacher_comments' => $trimmed,
                'teacher_commented_at' => now(),
                'status' => NotebookObservation::STATUS_COMMENTED,
                'updated_by' => (int) $teacher->id,
            ])->save();

            return $observation->fresh($this->notebookObservationRelations());
        }

        throw ValidationException::withMessages([
            'type' => 'Invalid observation type.',
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *   observations:LengthAwarePaginator|Collection<int, LessonObservation>,
     *   filters:array<string, mixed>,
     *   sessions:array<int, string>,
     *   classes:array<int, array{id:int,name:string}>,
     *   teachers:array<int, array{id:int,name:string}>,
     *   observers:array<int, array{id:int,name:string}>,
     *   area_options:array<int, string>
     * }
     */
    public function getLessonObservationsForObserver(User $observer, array $filters = []): array
    {
        $normalized = $this->normalizeObservationFilters($filters);
        $query = LessonObservation::query()->with($this->lessonObservationRelations());

        if (! $this->isPrincipalOrAdmin($observer)) {
            $query->where('observer_id', (int) $observer->id);
        }

        $this->applyObservationFilters($query, $normalized);

        $paginate = array_key_exists('paginate', $filters) ? (bool) $filters['paginate'] : true;
        $observations = $paginate
            ? $query->orderByDesc('observation_date')->orderByDesc('id')->paginate((int) $normalized['per_page'])->withQueryString()
            : $query->orderBy('observation_date')->orderBy('id')->get();

        return [
            'observations' => $observations,
            'filters' => $normalized,
            'sessions' => $this->sessionOptions(),
            'classes' => $this->classOptions(),
            'teachers' => $this->teacherOptions(),
            'observers' => $this->observerOptions(),
            'area_options' => array_keys(self::LESSON_STANDARDS),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *   observations:LengthAwarePaginator|Collection<int, NotebookObservation>,
     *   filters:array<string, mixed>,
     *   sessions:array<int, string>,
     *   classes:array<int, array{id:int,name:string}>,
     *   teachers:array<int, array{id:int,name:string}>,
     *   observers:array<int, array{id:int,name:string}>,
     *   subjects:array<int, array{id:int,name:string}>
     * }
     */
    public function getNotebookObservationsForObserver(User $observer, array $filters = []): array
    {
        $normalized = $this->normalizeObservationFilters($filters);
        $query = NotebookObservation::query()->with($this->notebookObservationRelations());

        if (! $this->isPrincipalOrAdmin($observer)) {
            $query->where('observer_id', (int) $observer->id);
        }

        $this->applyObservationFilters($query, $normalized);

        $paginate = array_key_exists('paginate', $filters) ? (bool) $filters['paginate'] : true;
        $observations = $paginate
            ? $query->orderByDesc('observation_date')->orderByDesc('id')->paginate((int) $normalized['per_page'])->withQueryString()
            : $query->orderBy('observation_date')->orderBy('id')->get();

        return [
            'observations' => $observations,
            'filters' => $normalized,
            'sessions' => $this->sessionOptions(),
            'classes' => $this->classOptions(),
            'teachers' => $this->teacherOptions(),
            'observers' => $this->observerOptions(),
            'subjects' => $this->subjectOptions(),
        ];
    }

    /**
     * @return array<int, array{area:string,standard_text:string,mark:int,max_mark:int,comments:?string,sort_order:int}>
     */
    public function lessonStandardTemplate(): array
    {
        $rows = [];
        $sortOrder = 0;
        foreach (self::LESSON_STANDARDS as $area => $standards) {
            foreach ($standards as $standard) {
                $rows[] = [
                    'area' => $area,
                    'standard_text' => $standard,
                    'mark' => 0,
                    'max_mark' => 1,
                    'comments' => null,
                    'sort_order' => $sortOrder++,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{checklist_text:string,response:string,comments:?string,sort_order:int}>
     */
    public function notebookChecklistTemplate(): array
    {
        return collect(self::NOTEBOOK_CHECKLIST)
            ->values()
            ->map(fn (string $text, int $index): array => [
                'checklist_text' => $text,
                'response' => NotebookObservation::RESPONSE_NA,
                'comments' => null,
                'sort_order' => $index,
            ])
            ->all();
    }

    public function findLessonObservationForUser(int $id, User $user): LessonObservation
    {
        $query = LessonObservation::query()->with($this->lessonObservationRelations());
        if (! $this->isPrincipalOrAdmin($user)) {
            $query->where('observer_id', (int) $user->id);
        }

        return $query->findOrFail($id);
    }

    public function findNotebookObservationForUser(int $id, User $user): NotebookObservation
    {
        $query = NotebookObservation::query()->with($this->notebookObservationRelations());
        if (! $this->isPrincipalOrAdmin($user)) {
            $query->where('observer_id', (int) $user->id);
        }

        return $query->findOrFail($id);
    }

    /**
     * @return array<int, string>
     */
    public function availableScopesForObserver(User $observer, ?string $session = null): array
    {
        return $this->sectionHeadAssignmentService->getObserverScopes($observer, $session);
    }

    /**
     * @param array<int, array<string, mixed>> $rawItems
     * @return array<int, array{area:string,standard_text:string,mark:int,max_mark:int,comments:?string,sort_order:int}>
     */
    private function sanitizeLessonItems(array $rawItems): array
    {
        return collect($rawItems)
            ->map(function ($item, int $index): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $area = trim((string) ($item['area'] ?? ''));
                $standard = trim((string) ($item['standard_text'] ?? ''));
                if ($area === '' || $standard === '') {
                    return null;
                }

                return [
                    'area' => $area,
                    'standard_text' => $standard,
                    'mark' => (int) ((int) ($item['mark'] ?? 0) > 0 ? 1 : 0),
                    'max_mark' => max(1, (int) ($item['max_mark'] ?? 1)),
                    'comments' => $this->nullableString($item['comments'] ?? null),
                    'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : $index,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $rawItems
     * @return array<int, array{checklist_text:string,response:string,comments:?string,sort_order:int}>
     */
    private function sanitizeNotebookItems(array $rawItems): array
    {
        return collect($rawItems)
            ->map(function ($item, int $index): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $checklist = trim((string) ($item['checklist_text'] ?? ''));
                if ($checklist === '') {
                    return null;
                }

                $response = trim(strtolower((string) ($item['response'] ?? NotebookObservation::RESPONSE_NA)));
                if (! in_array($response, NotebookObservation::RESPONSES, true)) {
                    $response = NotebookObservation::RESPONSE_NA;
                }

                return [
                    'checklist_text' => $checklist,
                    'response' => $response,
                    'comments' => $this->nullableString($item['comments'] ?? null),
                    'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : $index,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function areaJudgment(string $area, int $score): string
    {
        return match ($area) {
            'Lesson Planning' => $score >= 7
                ? LessonObservation::JUDGMENT_OUTSTANDING
                : ($score === 6
                    ? LessonObservation::JUDGMENT_GOOD
                    : ($score === 5 ? LessonObservation::JUDGMENT_ACCEPTABLE : LessonObservation::JUDGMENT_UNACCEPTABLE)),
            'Teaching' => $score >= 7
                ? LessonObservation::JUDGMENT_OUTSTANDING
                : ($score === 6
                    ? LessonObservation::JUDGMENT_GOOD
                    : ($score === 5 ? LessonObservation::JUDGMENT_ACCEPTABLE : LessonObservation::JUDGMENT_UNACCEPTABLE)),
            'Classroom Management' => $score >= 6
                ? LessonObservation::JUDGMENT_OUTSTANDING
                : ($score === 5
                    ? LessonObservation::JUDGMENT_GOOD
                    : ($score === 4 ? LessonObservation::JUDGMENT_ACCEPTABLE : LessonObservation::JUDGMENT_UNACCEPTABLE)),
            'Student Engagement' => $score >= 10
                ? LessonObservation::JUDGMENT_OUTSTANDING
                : (in_array($score, [8, 9], true)
                    ? LessonObservation::JUDGMENT_GOOD
                    : ($score === 7 ? LessonObservation::JUDGMENT_ACCEPTABLE : LessonObservation::JUDGMENT_UNACCEPTABLE)),
            'Assessment For Learning' => $score >= 6
                ? LessonObservation::JUDGMENT_OUTSTANDING
                : ($score === 5
                    ? LessonObservation::JUDGMENT_GOOD
                    : ($score === 4 ? LessonObservation::JUDGMENT_ACCEPTABLE : LessonObservation::JUDGMENT_UNACCEPTABLE)),
            'Care & Classroom Routines' => $score >= 7
                ? LessonObservation::JUDGMENT_OUTSTANDING
                : ($score === 6
                    ? LessonObservation::JUDGMENT_GOOD
                    : ($score === 5 ? LessonObservation::JUDGMENT_ACCEPTABLE : LessonObservation::JUDGMENT_UNACCEPTABLE)),
            default => LessonObservation::JUDGMENT_ACCEPTABLE,
        };
    }

    private function progressJudgment(?float $progressPercentage): string
    {
        if ($progressPercentage === null) {
            return LessonObservation::JUDGMENT_ACCEPTABLE;
        }

        if ($progressPercentage > 90) {
            return LessonObservation::JUDGMENT_OUTSTANDING;
        }
        if ($progressPercentage > 75) {
            return LessonObservation::JUDGMENT_GOOD;
        }
        if ($progressPercentage > 61) {
            return LessonObservation::JUDGMENT_ACCEPTABLE;
        }

        return LessonObservation::JUDGMENT_UNACCEPTABLE;
    }

    /**
     * @param array<string, array{score:int,max:int,judgment:string}> $areaScores
     */
    private function overallLessonJudgment(array $areaScores, string $progressJudgment): string
    {
        $judgments = collect($areaScores)
            ->pluck('judgment')
            ->values()
            ->push($progressJudgment)
            ->all();

        $outstanding = count(array_filter($judgments, fn ($j): bool => $j === LessonObservation::JUDGMENT_OUTSTANDING));
        $good = count(array_filter($judgments, fn ($j): bool => $j === LessonObservation::JUDGMENT_GOOD));
        $acceptable = count(array_filter($judgments, fn ($j): bool => $j === LessonObservation::JUDGMENT_ACCEPTABLE));
        $unacceptable = count(array_filter($judgments, fn ($j): bool => $j === LessonObservation::JUDGMENT_UNACCEPTABLE));

        $nonOutstanding = array_filter($judgments, fn ($j): bool => $j !== LessonObservation::JUDGMENT_OUTSTANDING);
        $nonGood = array_filter($judgments, fn ($j): bool => $j !== LessonObservation::JUDGMENT_GOOD);

        $restAreGood = $nonOutstanding === [] || count(array_filter($nonOutstanding, fn ($j): bool => $j === LessonObservation::JUDGMENT_GOOD)) === count($nonOutstanding);
        $restAtLeastAcceptable = $nonGood === []
            || count(array_filter($nonGood, fn ($j): bool => in_array($j, [LessonObservation::JUDGMENT_ACCEPTABLE, LessonObservation::JUDGMENT_GOOD, LessonObservation::JUDGMENT_OUTSTANDING], true))) === count($nonGood);

        if ($outstanding >= 4 && $restAreGood) {
            return LessonObservation::JUDGMENT_OUTSTANDING;
        }

        if ($good >= 4 && $restAtLeastAcceptable) {
            return LessonObservation::JUDGMENT_GOOD;
        }

        if ($acceptable >= 4 && $unacceptable <= 2) {
            return LessonObservation::JUDGMENT_ACCEPTABLE;
        }

        if ($unacceptable >= 3) {
            return LessonObservation::JUDGMENT_UNACCEPTABLE;
        }

        return LessonObservation::JUDGMENT_ACCEPTABLE;
    }

    private function observerRoleLabel(User $observer): string
    {
        if ($observer->hasRole('Admin')) {
            return 'Admin';
        }
        if ($observer->hasRole('Principal')) {
            return 'Principal';
        }
        if ($observer->hasRole(SectionHeadAssignment::TYPE_EARLY_YEARS_SECTION_HEAD)) {
            return SectionHeadAssignment::TYPE_EARLY_YEARS_SECTION_HEAD;
        }
        if ($observer->hasRole(SectionHeadAssignment::TYPE_MIDDLE_SCHOOL_SECTION_HEAD)) {
            return SectionHeadAssignment::TYPE_MIDDLE_SCHOOL_SECTION_HEAD;
        }
        if ($observer->hasRole(SectionHeadAssignment::TYPE_SENIOR_SCHOOL_SECTION_HEAD)) {
            return SectionHeadAssignment::TYPE_SENIOR_SCHOOL_SECTION_HEAD;
        }

        return (string) ($observer->getRoleNames()->first() ?? 'Observer');
    }

    private function assertObserverCanObserveTeacher(User $observer, User $teacher, string $session): void
    {
        if ((int) $observer->id === (int) $teacher->id) {
            throw ValidationException::withMessages([
                'observed_teacher_id' => 'You cannot observe yourself.',
            ]);
        }

        if ($this->isPrincipalOrAdmin($observer)) {
            return;
        }

        $allowedUserIds = $this->allowedTeacherUserIdsForObserver($observer, $session);
        if ($allowedUserIds === null || $allowedUserIds === []) {
            throw ValidationException::withMessages([
                'observed_teacher_id' => 'You are not assigned as section head for the selected session.',
            ]);
        }

        if (! in_array((int) $teacher->id, $allowedUserIds, true)) {
            throw ValidationException::withMessages([
                'observed_teacher_id' => 'You are not allowed to observe this teacher for the selected scope/session.',
            ]);
        }
    }

    /**
     * @return array<int, int>|null
     */
    private function allowedTeacherUserIdsForObserver(User $observer, string $session): ?array
    {
        if ($this->isPrincipalOrAdmin($observer)) {
            return null;
        }

        $scopes = $this->sectionHeadAssignmentService->getObserverScopes($observer, $session);
        if ($scopes === []) {
            return [];
        }

        return $this->teacherUserIdsByScopes($session, $scopes);
    }

    /**
     * @param array<int, string> $scopes
     * @return array<int, int>
     */
    private function teacherUserIdsByScopes(string $session, array $scopes): array
    {
        $assignments = TeacherAssignment::query()
            ->with('classRoom:id,name')
            ->where('session', $session)
            ->get(['teacher_id', 'class_id', 'session']);

        $allowedTeacherProfileIds = $assignments
            ->filter(function (TeacherAssignment $assignment) use ($scopes): bool {
                $className = (string) ($assignment->classRoom?->name ?? '');
                if ($className === '') {
                    return false;
                }

                $group = $this->teacherRankingService->resolveRankingGroupFromClass($className);

                return in_array($group, $scopes, true);
            })
            ->pluck('teacher_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($allowedTeacherProfileIds === []) {
            return [];
        }

        return Teacher::query()
            ->whereIn('id', $allowedTeacherProfileIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, int> $teacherProfileIds
     * @return array<int, array<int, string>>
     */
    private function teacherClassLabelsForSession(array $teacherProfileIds, string $session): array
    {
        if ($teacherProfileIds === []) {
            return [];
        }

        $assignments = TeacherAssignment::query()
            ->with('classRoom:id,name,section')
            ->whereIn('teacher_id', $teacherProfileIds)
            ->where('session', $session)
            ->orderBy('class_id')
            ->get(['teacher_id', 'class_id', 'session']);

        $map = [];
        foreach ($assignments as $assignment) {
            $teacherId = (int) $assignment->teacher_id;
            $label = trim((string) ($assignment->classRoom?->name ?? '').' '.(string) ($assignment->classRoom?->section ?? ''));
            if ($label === '') {
                continue;
            }

            $map[$teacherId] ??= [];
            $map[$teacherId][] = $label;
        }

        foreach ($map as $teacherId => $labels) {
            $map[$teacherId] = array_values(array_unique($labels));
        }

        return $map;
    }

    private function isPrincipalOrAdmin(User $user): bool
    {
        return $user->hasRole('Principal') || $user->hasRole('Admin');
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeObservationFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 20;
        $perPage = max(10, min($perPage, 100));

        return [
            'session' => trim((string) ($filters['session'] ?? '')) ?: null,
            'date' => trim((string) ($filters['date'] ?? '')) ?: null,
            'date_from' => trim((string) ($filters['date_from'] ?? '')) ?: null,
            'date_to' => trim((string) ($filters['date_to'] ?? '')) ?: null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
            'observed_teacher_id' => isset($filters['observed_teacher_id']) && $filters['observed_teacher_id'] !== '' ? (int) $filters['observed_teacher_id'] : null,
            'observer_id' => isset($filters['observer_id']) && $filters['observer_id'] !== '' ? (int) $filters['observer_id'] : null,
            'status' => trim((string) ($filters['status'] ?? '')) ?: null,
            'per_page' => $perPage,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyObservationFilters(Builder $query, array $filters): void
    {
        if ($filters['session'] !== null) {
            $query->where('session', (string) $filters['session']);
        }
        if ($filters['date'] !== null) {
            $query->whereDate('observation_date', Carbon::parse((string) $filters['date'])->toDateString());
        }
        if ($filters['date_from'] !== null) {
            $query->whereDate('observation_date', '>=', Carbon::parse((string) $filters['date_from'])->toDateString());
        }
        if ($filters['date_to'] !== null) {
            $query->whereDate('observation_date', '<=', Carbon::parse((string) $filters['date_to'])->toDateString());
        }
        if ($filters['class_id'] !== null) {
            $query->where('class_id', (int) $filters['class_id']);
        }
        if ($filters['observed_teacher_id'] !== null) {
            $query->where('observed_teacher_id', (int) $filters['observed_teacher_id']);
        }
        if ($filters['observer_id'] !== null) {
            $query->where('observer_id', (int) $filters['observer_id']);
        }
        if ($filters['status'] !== null) {
            $query->where('status', (string) $filters['status']);
        }
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        return collect(array_merge(
            LessonObservation::query()
                ->pluck('session')
                ->filter(static fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            NotebookObservation::query()
                ->pluck('session')
                ->filter(static fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            $this->dailyDiaryService->sessionOptions()
        ))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function resolveSession(?string $session): string
    {
        return $this->dailyDiaryService->resolveSession($session);
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function classOptions(): array
    {
        return SchoolClass::query()
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
     * @return array<int, array{id:int,name:string}>
     */
    private function teacherOptions(): array
    {
        return User::query()
            ->role('Teacher')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function observerOptions(): array
    {
        return User::query()
            ->where(function (Builder $query): void {
                $query->role('Principal')
                    ->orWhere(fn (Builder $q) => $q->role('Admin'))
                    ->orWhere(fn (Builder $q) => $q->role([
                        SectionHeadAssignment::TYPE_EARLY_YEARS_SECTION_HEAD,
                        SectionHeadAssignment::TYPE_MIDDLE_SCHOOL_SECTION_HEAD,
                        SectionHeadAssignment::TYPE_SENIOR_SCHOOL_SECTION_HEAD,
                    ]));
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->unique('id')
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function subjectOptions(): array
    {
        return Subject::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Subject $subject): array => [
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function lessonObservationRelations(): array
    {
        return [
            'observedTeacher:id,name,email',
            'observer:id,name,email',
            'classRoom:id,name,section',
            'items:id,lesson_observation_id,area,standard_text,mark,max_mark,comments,sort_order',
            'createdBy:id,name',
            'updatedBy:id,name',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function notebookObservationRelations(): array
    {
        return [
            'observedTeacher:id,name,email',
            'observer:id,name,email',
            'classRoom:id,name,section',
            'subject:id,name',
            'items:id,notebook_observation_id,checklist_text,response,comments,sort_order',
            'createdBy:id,name',
            'updatedBy:id,name',
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
