<?php

namespace App\Services;

use App\Models\CognitiveAssessment;
use App\Models\CognitiveAssessmentAttempt;
use App\Models\CognitiveAssessmentAttemptReset;
use App\Models\CognitiveAssessmentQuestion;
use App\Models\CognitiveAssessmentResponse;
use App\Models\CognitiveAssessmentSection;
use App\Models\CognitiveAssessmentSectionQuestion;
use App\Models\CognitiveAssessmentStudentAssignment;
use App\Models\CognitiveBankQuestion;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CognitiveAssessmentSeeder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class CognitiveAssessmentService
{
    /**
     * @return array<int, int>
     */
    public function eligibleGrades(): array
    {
        return [8, 9, 10, 11, 12];
    }

    public function resolveAssessment(): CognitiveAssessment
    {
        $assessment = CognitiveAssessment::query()
            ->where('slug', CognitiveAssessment::LEVEL_4_SLUG)
            ->with($this->assessmentRelations())
            ->first();

        if (! $assessment) {
            app(CognitiveAssessmentSeeder::class)->run();

            $assessment = CognitiveAssessment::query()
                ->where('slug', CognitiveAssessment::LEVEL_4_SLUG)
                ->with($this->assessmentRelations())
                ->first();
        }

        if (! $assessment) {
            throw new RuntimeException('Cognitive Skills Assessment Test Level 4 is not configured yet.');
        }

        return $assessment;
    }

    public function resolveStudentForUser(User $user): ?Student
    {
        $normalizedName = mb_strtolower(trim((string) $user->name));
        $emailLocal = mb_strtolower(trim(Str::before((string) $user->email, '@')));

        if ($emailLocal !== '') {
            $byStudentId = Student::query()
                ->with('classRoom:id,name,section')
                ->whereRaw('LOWER(student_id) = ?', [$emailLocal])
                ->first();

            if ($byStudentId) {
                return $byStudentId;
            }
        }

        if ($normalizedName !== '') {
            $byName = Student::query()
                ->with('classRoom:id,name,section')
                ->whereRaw('LOWER(name) = ?', [$normalizedName])
                ->orderByDesc('id')
                ->get();

            if ($byName->count() === 1) {
                return $byName->first();
            }
        }

        return null;
    }

    public function studentEligibleForAssessment(Student|int $student): bool
    {
        $student = $this->resolveStudentModel($student);
        if (! $student) {
            return false;
        }

        $student->loadMissing('classRoom:id,name,section');

        $className = trim((string) ($student->classRoom?->name ?? ''));
        $section = trim((string) ($student->classRoom?->section ?? ''));
        $grade = $this->extractGradeFromClassLabel(trim($className.' '.$section));

        return $grade !== null && in_array($grade, $this->eligibleGrades(), true);
    }

    public function studentHasEnabledAssignment(int $studentId, int $assessmentId): bool
    {
        if (! Schema::hasTable('cognitive_assessment_student_assignments')) {
            return false;
        }

        return CognitiveAssessmentStudentAssignment::query()
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->where('is_enabled', true)
            ->exists();
    }

    /**
     * @return array{assessment:CognitiveAssessment,eligible:bool,enabled:bool,visible:bool,message:?string,assignment:?CognitiveAssessmentStudentAssignment}
     */
    public function studentAssessmentAccessState(Student|int $student, CognitiveAssessment|int|null $assessment = null): array
    {
        $studentModel = $this->resolveStudentModel($student);
        if (! $studentModel) {
            throw new RuntimeException('Student record could not be resolved for the assessment.');
        }

        $assessmentModel = $this->resolveAssessmentModel($assessment);
        $eligible = $this->studentEligibleForAssessment($studentModel);
        $enabled = $eligible && $this->studentHasEnabledAssignment((int) $studentModel->id, (int) $assessmentModel->id);
        $visible = $assessmentModel->is_active && $eligible && $enabled;

        $message = match (true) {
            ! $eligible => 'Cognitive Skills Assessment Test Level 4 is available only for students in Grades 8, 9, 10, 11, and 12.',
            ! $assessmentModel->is_active => 'Cognitive Skills Assessment Test Level 4 is currently unavailable.',
            ! $enabled => 'Cognitive Skills Assessment Test Level 4 will appear in your panel after the Principal enables it for your student record.',
            default => null,
        };

        return [
            'assessment' => $assessmentModel,
            'eligible' => $eligible,
            'enabled' => $enabled,
            'visible' => $visible,
            'message' => $message,
            'assignment' => $this->findStudentAssignment((int) $assessmentModel->id, (int) $studentModel->id),
        ];
    }

    public function studentCanAccessAssessment(Student|int $student, CognitiveAssessment|int|null $assessment = null): bool
    {
        return $this->studentAssessmentAccessState($student, $assessment)['visible'];
    }

    public function getOrCreateStudentAssignment(int $assessmentId, int $studentId): CognitiveAssessmentStudentAssignment
    {
        return CognitiveAssessmentStudentAssignment::query()->firstOrCreate(
            [
                'assessment_id' => $assessmentId,
                'student_id' => $studentId,
            ],
            [
                'is_enabled' => false,
            ]
        );
    }

    public function enableAssessmentForStudent(
        int $assessmentId,
        int $studentId,
        int $principalUserId,
        ?string $note = null
    ): CognitiveAssessmentStudentAssignment {
        $student = $this->resolveStudentModel($studentId);
        if (! $student || ! $this->studentEligibleForAssessment($student)) {
            throw new RuntimeException('Only students in Grades 8, 9, 10, 11, and 12 can be enabled for Cognitive Skills Assessment Test Level 4.');
        }

        return DB::transaction(function () use ($assessmentId, $studentId, $principalUserId, $note): CognitiveAssessmentStudentAssignment {
            $assignment = CognitiveAssessmentStudentAssignment::query()
                ->where('assessment_id', $assessmentId)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->first()
                ?? $this->getOrCreateStudentAssignment($assessmentId, $studentId);

            $assignment->forceFill([
                'is_enabled' => true,
                'enabled_by' => $principalUserId,
                'enabled_at' => now(),
                'disabled_by' => null,
                'disabled_at' => null,
                'principal_note' => $this->nullableString($note) ?? $assignment->principal_note,
            ])->save();

            return $assignment->fresh(['enabledBy', 'disabledBy']) ?? $assignment;
        });
    }

    public function disableAssessmentForStudent(
        int $assessmentId,
        int $studentId,
        int $principalUserId,
        ?string $note = null
    ): CognitiveAssessmentStudentAssignment {
        return DB::transaction(function () use ($assessmentId, $studentId, $principalUserId, $note): CognitiveAssessmentStudentAssignment {
            $assignment = CognitiveAssessmentStudentAssignment::query()
                ->where('assessment_id', $assessmentId)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->first()
                ?? $this->getOrCreateStudentAssignment($assessmentId, $studentId);

            $assignment->forceFill([
                'is_enabled' => false,
                'disabled_by' => $principalUserId,
                'disabled_at' => now(),
                'principal_note' => $this->nullableString($note) ?? $assignment->principal_note,
            ])->save();

            return $assignment->fresh(['enabledBy', 'disabledBy']) ?? $assignment;
        });
    }

    public function resetStudentAssessment(
        int $assessmentId,
        int $studentId,
        int $principalUserId,
        ?string $reason = null
    ): CognitiveAssessmentAttempt {
        return DB::transaction(function () use ($assessmentId, $studentId, $principalUserId, $reason): CognitiveAssessmentAttempt {
            $attempt = CognitiveAssessmentAttempt::query()
                ->where('assessment_id', $assessmentId)
                ->where('student_id', $studentId)
                ->where('status', '!=', CognitiveAssessmentAttempt::STATUS_RESET)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $attempt) {
                throw new RuntimeException('No assessment attempt is available to reset for this student.');
            }

            $attempt->forceFill([
                'status' => CognitiveAssessmentAttempt::STATUS_RESET,
            ])->save();

            CognitiveAssessmentAttemptReset::query()->create([
                'attempt_id' => $attempt->id,
                'student_id' => $studentId,
                'reset_by' => $principalUserId,
                'reason' => $this->nullableString($reason),
                'reset_at' => now(),
            ]);

            $assignment = CognitiveAssessmentStudentAssignment::query()
                ->where('assessment_id', $assessmentId)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->first()
                ?? $this->getOrCreateStudentAssignment($assessmentId, $studentId);

            $assignment->forceFill([
                'is_enabled' => true,
                'enabled_by' => $principalUserId,
                'enabled_at' => $assignment->enabled_at ?? now(),
                'disabled_by' => null,
                'disabled_at' => null,
                'principal_note' => $this->nullableString($reason) ?? $assignment->principal_note,
            ])->save();

            return $attempt->fresh($this->attemptRelations()) ?? $attempt;
        });
    }

    public function startAttempt(Student $student): CognitiveAssessmentAttempt
    {
        $assessment = $this->resolveAssessment();

        if (! $assessment->is_active) {
            throw new RuntimeException('Cognitive Skills Assessment Test Level 4 is currently unavailable.');
        }

        if (! $this->studentEligibleForAssessment($student)) {
            throw new RuntimeException('Only students in Grades 8, 9, 10, 11, and 12 can take Cognitive Skills Assessment Test Level 4.');
        }

        if (! $this->studentHasEnabledAssignment((int) $student->id, (int) $assessment->id)) {
            throw new RuntimeException('Cognitive Skills Assessment Test Level 4 is available in your panel only after the Principal enables it for your student record.');
        }

        return DB::transaction(function () use ($assessment, $student): CognitiveAssessmentAttempt {
            $attempt = CognitiveAssessmentAttempt::query()
                ->where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->where('status', '!=', CognitiveAssessmentAttempt::STATUS_RESET)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $attempt) {
                $attempt = CognitiveAssessmentAttempt::query()->create([
                    'assessment_id' => $assessment->id,
                    'student_id' => $student->id,
                    'status' => CognitiveAssessmentAttempt::STATUS_NOT_STARTED,
                ]);
            }

            if ($attempt->status === CognitiveAssessmentAttempt::STATUS_GRADED) {
                return $attempt->fresh($this->attemptRelations()) ?? $attempt;
            }

            if ($attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS && $this->attemptExpired($attempt)) {
                return $this->submitAttemptLocked($attempt, true);
            }

            if (in_array($attempt->status, [
                CognitiveAssessmentAttempt::STATUS_SUBMITTED,
                CognitiveAssessmentAttempt::STATUS_AUTO_SUBMITTED,
            ], true)) {
                return $this->scoreAttemptLocked($attempt);
            }

            if ($attempt->status === CognitiveAssessmentAttempt::STATUS_NOT_STARTED || $attempt->started_at === null) {
                $startedAt = now();
                $attempt->forceFill([
                    'status' => CognitiveAssessmentAttempt::STATUS_IN_PROGRESS,
                    'started_at' => $startedAt,
                    'expires_at' => $startedAt->copy()->addSeconds($this->assessmentDurationSeconds($assessment)),
                    'submitted_at' => null,
                ])->save();
            }

            return $attempt->fresh($this->attemptRelations()) ?? $attempt;
        });
    }

    /**
     * @param array<int, array{question_id?:int|null,bank_question_id?:int|null,selected_answer:string|null}> $responses
     */
    public function saveResponses(CognitiveAssessmentAttempt $attempt, array $responses): CognitiveAssessmentAttempt
    {
        return DB::transaction(function () use ($attempt, $responses): CognitiveAssessmentAttempt {
            $lockedAttempt = CognitiveAssessmentAttempt::query()
                ->with($this->attemptAssessmentRelations())
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($lockedAttempt->status !== CognitiveAssessmentAttempt::STATUS_IN_PROGRESS) {
                throw new RuntimeException('Only in-progress attempts can be updated.');
            }

            if ($this->attemptExpired($lockedAttempt)) {
                $this->submitAttemptLocked($lockedAttempt, true);

                throw new RuntimeException('The timer expired. Your assessment was auto-submitted.');
            }

            $availableQuestions = $this->assessmentQuestionMap($lockedAttempt->assessment);

            foreach ($responses as $responsePayload) {
                $identifiers = $this->questionIdentifiersFromPayload($responsePayload);
                $responseKey = $identifiers['response_key'];

                if ($responseKey === null || ! $availableQuestions->has($responseKey)) {
                    throw new RuntimeException('One or more answers do not belong to this assessment.');
                }

                CognitiveAssessmentResponse::query()->updateOrCreate(
                    [
                        'attempt_id' => $lockedAttempt->id,
                        'question_id' => $identifiers['question_id'],
                        'bank_question_id' => $identifiers['bank_question_id'],
                    ],
                    [
                        'selected_answer' => $this->nullableString($responsePayload['selected_answer'] ?? null),
                        'locked_at' => null,
                    ]
                );
            }

            return $lockedAttempt->fresh($this->attemptRelations()) ?? $lockedAttempt;
        });
    }

    public function submitAttempt(CognitiveAssessmentAttempt $attempt, bool $autoSubmitted = false): CognitiveAssessmentAttempt
    {
        return DB::transaction(function () use ($attempt, $autoSubmitted): CognitiveAssessmentAttempt {
            $lockedAttempt = CognitiveAssessmentAttempt::query()
                ->with(array_merge($this->attemptAssessmentRelations(), ['responses']))
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            return $this->submitAttemptLocked($lockedAttempt, $autoSubmitted);
        });
    }

    /**
     * @return array{expired_attempts:int,auto_submitted:int}
     */
    public function autoSubmitExpiredAttempts(): array
    {
        $expiredAttempts = CognitiveAssessmentAttempt::query()
            ->where('status', CognitiveAssessmentAttempt::STATUS_IN_PROGRESS)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get(['id']);

        $submitted = 0;
        foreach ($expiredAttempts as $attempt) {
            $this->submitAttempt($attempt, true);
            $submitted++;
        }

        return [
            'expired_attempts' => $expiredAttempts->count(),
            'auto_submitted' => $submitted,
        ];
    }

    public function scoreAttempt(CognitiveAssessmentAttempt $attempt): CognitiveAssessmentAttempt
    {
        return DB::transaction(function () use ($attempt): CognitiveAssessmentAttempt {
            $lockedAttempt = CognitiveAssessmentAttempt::query()
                ->with(array_merge($this->attemptAssessmentRelations(), ['responses']))
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            return $this->scoreAttemptLocked($lockedAttempt);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAttemptViewData(CognitiveAssessmentAttempt $attempt): array
    {
        $attempt->loadMissing($this->attemptRelations());
        $responsesByKey = $this->mapResponsesByQuestionKey($attempt);
        $sections = $attempt->assessment->sections
            ->map(function (CognitiveAssessmentSection $section) use ($responsesByKey): array {
                $questions = $this->sectionQuestionItems($section)
                    ->map(function (array $question) use ($responsesByKey): array {
                        return array_merge($question, [
                            'selected_answer' => $responsesByKey->get($question['response_key'])?->selected_answer,
                        ]);
                    })
                    ->values();

                return [
                    'id' => (int) $section->id,
                    'skill' => (string) $section->skill,
                    'title' => (string) $section->title,
                    'duration_seconds' => (int) $section->duration_seconds,
                    'question_count' => (int) $questions->count(),
                    'available_marks' => (int) $questions->sum('marks'),
                    'questions' => $questions->all(),
                ];
            })
            ->values();

        return [
            'sections' => $sections->all(),
            'initial_answers' => $responsesByKey
                ->mapWithKeys(fn (CognitiveAssessmentResponse $response, string $key): array => [$key => $response->selected_answer])
                ->all(),
            'total_questions' => (int) $sections->sum('question_count'),
            'total_marks' => (int) $sections->sum('available_marks'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildStudentResult(CognitiveAssessmentAttempt $attempt): array
    {
        $attempt->loadMissing($this->attemptRelations());
        $responsesByKey = $this->mapResponsesByQuestionKey($attempt);

        $sectionRows = $attempt->assessment->sections
            ->map(function (CognitiveAssessmentSection $section) use ($responsesByKey): array {
                $questions = $this->sectionQuestionItems($section);
                $availableMarks = (int) $questions->sum('marks');
                $awardedMarks = (int) $questions->sum(fn (array $question): int => (int) ($responsesByKey->get($question['response_key'])?->awarded_marks ?? 0));
                $answeredCount = (int) $questions->filter(fn (array $question): bool => $responsesByKey->get($question['response_key'])?->selected_answer !== null)->count();
                $correctCount = (int) $questions->filter(fn (array $question): bool => (bool) ($responsesByKey->get($question['response_key'])?->is_correct ?? false))->count();

                return [
                    'skill' => (string) $section->skill,
                    'title' => (string) $section->title,
                    'duration_seconds' => (int) $section->duration_seconds,
                    'available_marks' => $availableMarks,
                    'awarded_marks' => $awardedMarks,
                    'answered_count' => $answeredCount,
                    'correct_count' => $correctCount,
                    'question_count' => (int) $questions->count(),
                    'percentage' => $availableMarks > 0 ? round(($awardedMarks / $availableMarks) * 100, 2) : 0.0,
                ];
            })
            ->values();

        return [
            'assessment' => [
                'title' => (string) $attempt->assessment->title,
                'slug' => (string) $attempt->assessment->slug,
                'description' => $attempt->assessment->description,
            ],
            'student' => [
                'name' => (string) ($attempt->student?->name ?? 'Student'),
                'student_id' => (string) ($attempt->student?->student_id ?? '-'),
                'class' => trim((string) (($attempt->student?->classRoom?->name ?? '').' '.($attempt->student?->classRoom?->section ?? ''))),
            ],
            'attempt' => [
                'status' => (string) $attempt->status,
                'started_at' => optional($attempt->started_at)->format('Y-m-d H:i:s'),
                'expires_at' => optional($attempt->expires_at)->format('Y-m-d H:i:s'),
                'submitted_at' => optional($attempt->submitted_at)->format('Y-m-d H:i:s'),
            ],
            'sections' => $sectionRows->all(),
            'summary' => [
                'verbal_score' => (int) ($attempt->verbal_score ?? 0),
                'non_verbal_score' => (int) ($attempt->non_verbal_score ?? 0),
                'quantitative_score' => (int) ($attempt->quantitative_score ?? 0),
                'spatial_score' => (int) ($attempt->spatial_score ?? 0),
                'overall_score' => (int) ($attempt->overall_score ?? 0),
                'overall_percentage' => round((float) ($attempt->overall_percentage ?? 0), 2),
                'performance_band' => (string) ($attempt->performance_band ?? 'Not Graded'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildAttemptReview(CognitiveAssessmentAttempt $attempt): array
    {
        $attempt->loadMissing($this->attemptRelations());
        $responsesByKey = $this->mapResponsesByQuestionKey($attempt);

        return $attempt->assessment->sections
            ->map(function (CognitiveAssessmentSection $section) use ($responsesByKey): array {
                $rows = $this->sectionQuestionItems($section)
                    ->map(function (array $question) use ($responsesByKey): array {
                        $response = $responsesByKey->get($question['response_key']);

                        return [
                            'question_id' => $question['source_id'],
                            'source_type' => $question['source_type'],
                            'question_type' => $question['question_type'],
                            'question_text' => $question['question_text'],
                            'question_image' => $question['question_image'],
                            'question_image_url' => $question['question_image_url'],
                            'question_bank_title' => $question['question_bank_title'],
                            'difficulty_level' => $question['difficulty_level'],
                            'options' => $question['options'],
                            'selected_answer' => $response?->selected_answer,
                            'correct_answer' => $question['correct_answer'],
                            'is_correct' => $response?->is_correct,
                            'awarded_marks' => $response?->awarded_marks,
                            'available_marks' => $question['marks'],
                            'locked_at' => optional($response?->locked_at)->format('Y-m-d H:i:s'),
                            'explanation' => $question['explanation'],
                        ];
                    })
                    ->values();

                return [
                    'skill' => (string) $section->skill,
                    'title' => (string) $section->title,
                    'duration_seconds' => (int) $section->duration_seconds,
                    'total_marks' => (int) $rows->sum('available_marks'),
                    'rows' => $rows->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array{class_id?:mixed,student_id?:mixed,date_from?:mixed,date_to?:mixed,per_page?:mixed} $filters
     * @return array{
     *   assessment:CognitiveAssessment,
     *   attempts:LengthAwarePaginator,
     *   classes:EloquentCollection<int, SchoolClass>,
     *   students:EloquentCollection<int, Student>,
     *   filters:array<string, mixed>
     * }
     */
    public function buildAdminReport(array $filters): array
    {
        $assessment = $this->resolveAssessment();
        $perPage = max((int) ($filters['per_page'] ?? 15), 10);

        $query = CognitiveAssessmentAttempt::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
            ])
            ->where('assessment_id', $assessment->id)
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($builder) use ($filters): void {
                $builder->whereHas('student', function ($studentQuery) use ($filters): void {
                    $studentQuery->where('class_id', (int) $filters['class_id']);
                });
            })
            ->when(($filters['student_id'] ?? null) !== null && $filters['student_id'] !== '', function ($builder) use ($filters): void {
                $builder->where('student_id', (int) $filters['student_id']);
            })
            ->when(($filters['date_from'] ?? null) !== null && $filters['date_from'] !== '', function ($builder) use ($filters): void {
                $builder->whereDate(DB::raw('COALESCE(submitted_at, created_at)'), '>=', (string) $filters['date_from']);
            })
            ->when(($filters['date_to'] ?? null) !== null && $filters['date_to'] !== '', function ($builder) use ($filters): void {
                $builder->whereDate(DB::raw('COALESCE(submitted_at, created_at)'), '<=', (string) $filters['date_to']);
            })
            ->orderByDesc(DB::raw('COALESCE(submitted_at, created_at)'))
            ->orderByDesc('id');

        $attempts = $query->paginate($perPage)->withQueryString();
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);
        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($builder) use ($filters): void {
                $builder->where('class_id', (int) $filters['class_id']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'student_id', 'class_id']);

        return [
            'assessment' => $assessment,
            'attempts' => $attempts,
            'classes' => $classes,
            'students' => $students,
            'filters' => [
                'class_id' => $filters['class_id'] ?? '',
                'student_id' => $filters['student_id'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * @param array{class_id?:mixed,search?:mixed,enabled_status?:mixed,per_page?:mixed} $filters
     * @return array{
     *   assessment:CognitiveAssessment,
     *   rows:LengthAwarePaginator,
     *   classes:EloquentCollection<int, SchoolClass>,
     *   filters:array<string, mixed>
     * }
     */
    public function buildStudentAccessManagement(array $filters): array
    {
        $assessment = $this->resolveAssessment();
        $perPage = max((int) ($filters['per_page'] ?? 15), 10);
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $enabledStatus = (string) ($filters['enabled_status'] ?? 'all');

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($builder) use ($filters): void {
                $builder->where('class_id', (int) $filters['class_id']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'student_id', 'class_id'])
            ->filter(function (Student $student) use ($search): bool {
                if (! $this->studentEligibleForAssessment($student)) {
                    return false;
                }

                if ($search === '') {
                    return true;
                }

                $haystack = mb_strtolower(trim(implode(' ', [
                    (string) $student->name,
                    (string) $student->student_id,
                    (string) ($student->classRoom?->name ?? ''),
                    (string) ($student->classRoom?->section ?? ''),
                ])));

                return str_contains($haystack, $search);
            })
            ->values();

        $studentIds = $students->pluck('id')->all();
        $assignments = collect();
        $attempts = collect();

        if ($studentIds !== []) {
            $assignments = CognitiveAssessmentStudentAssignment::query()
                ->with(['enabledBy:id,name', 'disabledBy:id,name'])
                ->where('assessment_id', $assessment->id)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');

            $attempts = CognitiveAssessmentAttempt::query()
                ->where('assessment_id', $assessment->id)
                ->whereIn('student_id', $studentIds)
                ->with('resets.resetBy:id,name')
                ->orderByDesc('id')
                ->get()
                ->groupBy('student_id')
                ->map(fn (Collection $items): ?CognitiveAssessmentAttempt => $items->first());
        }

        $rows = $students
            ->map(function (Student $student) use ($assessment, $assignments, $attempts): array {
                /** @var CognitiveAssessmentStudentAssignment|null $assignment */
                $assignment = $assignments->get($student->id);
                /** @var CognitiveAssessmentAttempt|null $attempt */
                $attempt = $attempts->get($student->id);
                $classLabel = trim((string) (($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')));

                return [
                    'student' => $student,
                    'class_label' => $classLabel !== '' ? $classLabel : '-',
                    'assignment' => $assignment,
                    'is_enabled' => (bool) ($assignment?->is_enabled ?? false),
                    'attempt' => $attempt,
                    'attempt_status_label' => $this->attemptStatusLabel($attempt?->status),
                    'overall_percentage' => $attempt && $attempt->overall_percentage !== null && $attempt->status !== CognitiveAssessmentAttempt::STATUS_RESET
                        ? round((float) $attempt->overall_percentage, 2)
                        : null,
                    'performance_band' => $attempt && $attempt->status !== CognitiveAssessmentAttempt::STATUS_RESET
                        ? $attempt->performance_band
                        : null,
                    'completed_score' => $attempt && $attempt->status !== CognitiveAssessmentAttempt::STATUS_RESET
                        ? $attempt->overall_score
                        : null,
                    'report_available' => $attempt !== null && $attempt->overall_percentage !== null,
                    'assessment' => $assessment,
                ];
            })
            ->when($enabledStatus === 'enabled', fn (Collection $collection): Collection => $collection->where('is_enabled', true)->values())
            ->when($enabledStatus === 'disabled', fn (Collection $collection): Collection => $collection->where('is_enabled', false)->values())
            ->values();

        return [
            'assessment' => $assessment,
            'rows' => $this->paginateCollection($rows, $perPage),
            'classes' => $classes,
            'filters' => [
                'class_id' => $filters['class_id'] ?? '',
                'search' => $filters['search'] ?? '',
                'enabled_status' => $enabledStatus,
                'per_page' => $perPage,
            ],
        ];
    }

    public function attemptBelongsToStudent(User $user, CognitiveAssessmentAttempt $attempt): bool
    {
        $student = $this->resolveStudentForUser($user);

        return $student !== null
            && (int) $attempt->student_id === (int) $student->id
            && (int) $attempt->assessment_id === (int) $this->resolveAssessment()->id;
    }

    /**
     * @return array<int, string>
     */
    private function assessmentRelations(): array
    {
        $relations = ['sections.questions'];

        if ($this->questionBankTablesAvailable()) {
            $relations[] = 'sections.questionAssignments.bankQuestion.questionBank';
        }

        return $relations;
    }

    /**
     * @return array<int, string>
     */
    private function attemptRelations(): array
    {
        return array_merge($this->attemptAssessmentRelations(), [
            'student.classRoom',
            'responses.question.section',
            ...($this->questionBankTablesAvailable() ? ['responses.bankQuestion.questionBank'] : []),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function attemptAssessmentRelations(): array
    {
        return collect($this->assessmentRelations())
            ->map(fn (string $relation): string => 'assessment.'.$relation)
            ->values()
            ->all();
    }

    private function assessmentDurationSeconds(CognitiveAssessment $assessment): int
    {
        return max((int) $assessment->sections->sum('duration_seconds'), 600);
    }

    private function attemptExpired(CognitiveAssessmentAttempt $attempt): bool
    {
        return $attempt->expires_at !== null && $attempt->expires_at->lessThanOrEqualTo(now());
    }

    private function submitAttemptLocked(CognitiveAssessmentAttempt $attempt, bool $autoSubmitted): CognitiveAssessmentAttempt
    {
        $attempt->loadMissing(array_merge($this->attemptAssessmentRelations(), ['responses']));

        if ($attempt->status === CognitiveAssessmentAttempt::STATUS_GRADED) {
            return $attempt->fresh($this->attemptRelations()) ?? $attempt;
        }

        $submittedAt = now();

        foreach ($this->assessmentQuestionMap($attempt->assessment) as $question) {
            $response = CognitiveAssessmentResponse::query()->firstOrNew([
                'attempt_id' => $attempt->id,
                'question_id' => $question['question_id'],
                'bank_question_id' => $question['bank_question_id'],
            ]);

            $response->forceFill([
                'selected_answer' => $response->selected_answer,
                'locked_at' => $submittedAt,
            ])->save();
        }

        $attempt->forceFill([
            'status' => $autoSubmitted ? CognitiveAssessmentAttempt::STATUS_AUTO_SUBMITTED : CognitiveAssessmentAttempt::STATUS_SUBMITTED,
            'submitted_at' => $submittedAt,
        ])->save();

        $attempt->unsetRelation('responses');

        return $this->scoreAttemptLocked($attempt);
    }

    private function scoreAttemptLocked(CognitiveAssessmentAttempt $attempt): CognitiveAssessmentAttempt
    {
        $attempt->load(array_merge($this->attemptAssessmentRelations(), ['responses.question', 'responses.bankQuestion']));

        $responsesByKey = $this->mapResponsesByQuestionKey($attempt);
        $sectionScores = [
            CognitiveAssessmentSection::SKILL_VERBAL => 0,
            CognitiveAssessmentSection::SKILL_NON_VERBAL => 0,
            CognitiveAssessmentSection::SKILL_QUANTITATIVE => 0,
            CognitiveAssessmentSection::SKILL_SPATIAL => 0,
        ];

        $totalPossibleMarks = 0;
        foreach ($attempt->assessment->sections as $section) {
            $sectionQuestions = $this->sectionQuestionItems($section);
            $sectionTotalMarks = 0;

            foreach ($sectionQuestions as $question) {
                $totalPossibleMarks += (int) $question['marks'];
                $sectionTotalMarks += (int) $question['marks'];

                $response = $responsesByKey->get($question['response_key']);
                if (! $response) {
                    $response = CognitiveAssessmentResponse::query()->create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question['question_id'],
                        'bank_question_id' => $question['bank_question_id'],
                        'selected_answer' => null,
                        'is_correct' => false,
                        'awarded_marks' => 0,
                        'locked_at' => $attempt->submitted_at ?? now(),
                    ]);
                    $responsesByKey->put($question['response_key'], $response);
                }

                $isCorrect = $this->answersMatch($response->selected_answer, $question['correct_answer']);
                $awardedMarks = $isCorrect ? (int) $question['marks'] : 0;

                $response->forceFill([
                    'is_correct' => $isCorrect,
                    'awarded_marks' => $awardedMarks,
                    'locked_at' => $response->locked_at ?? $attempt->submitted_at ?? now(),
                ])->save();

                $sectionScores[$section->skill] = (int) ($sectionScores[$section->skill] ?? 0) + $awardedMarks;
            }

            if ((int) $section->total_marks !== $sectionTotalMarks) {
                $section->forceFill(['total_marks' => $sectionTotalMarks])->save();
            }
        }

        $overallScore = (int) array_sum($sectionScores);
        $percentage = $totalPossibleMarks > 0 ? round(($overallScore / $totalPossibleMarks) * 100, 2) : 0.0;

        $attempt->forceFill([
            'verbal_score' => (int) ($sectionScores[CognitiveAssessmentSection::SKILL_VERBAL] ?? 0),
            'non_verbal_score' => (int) ($sectionScores[CognitiveAssessmentSection::SKILL_NON_VERBAL] ?? 0),
            'quantitative_score' => (int) ($sectionScores[CognitiveAssessmentSection::SKILL_QUANTITATIVE] ?? 0),
            'spatial_score' => (int) ($sectionScores[CognitiveAssessmentSection::SKILL_SPATIAL] ?? 0),
            'overall_score' => $overallScore,
            'overall_percentage' => $percentage,
            'performance_band' => $this->performanceBand($percentage),
            'status' => CognitiveAssessmentAttempt::STATUS_GRADED,
            'submitted_at' => $attempt->submitted_at ?? now(),
        ])->save();

        return $attempt->fresh($this->attemptRelations()) ?? $attempt;
    }

    private function performanceBand(float $percentage): string
    {
        return match (true) {
            $percentage >= 85 => 'Very Strong',
            $percentage >= 70 => 'Strong',
            $percentage >= 55 => 'Average',
            $percentage >= 40 => 'Developing',
            default => 'Needs Support',
        };
    }

    private function answersMatch(?string $selectedAnswer, ?string $correctAnswer): bool
    {
        return $this->normalizeAnswerValue($selectedAnswer) !== ''
            && $this->normalizeAnswerValue($selectedAnswer) === $this->normalizeAnswerValue($correctAnswer);
    }

    private function normalizeAnswerValue(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function nullableString(mixed $value): ?string
    {
        $resolved = trim((string) $value);

        return $resolved === '' ? null : $resolved;
    }

    private function extractGradeFromClassLabel(string $label): ?int
    {
        if ($label === '') {
            return null;
        }

        if (preg_match('/(?:^|[^0-9])(8|9|10|11|12)(?:$|[^0-9])/', $label, $matches) === 1) {
            return (int) ($matches[1] ?? 0);
        }

        return null;
    }

    private function questionBankTablesAvailable(): bool
    {
        return Schema::hasTable('cognitive_assessment_section_questions')
            && Schema::hasTable('cognitive_bank_questions');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function sectionQuestionItems(CognitiveAssessmentSection $section): Collection
    {
        if ($this->questionBankTablesAvailable()) {
            $assignments = $section->relationLoaded('questionAssignments')
                ? $section->questionAssignments
                : $section->questionAssignments()->with('bankQuestion.questionBank')->get();

            if ($assignments->isNotEmpty()) {
                return $assignments
                    ->map(function (CognitiveAssessmentSectionQuestion $assignment): ?array {
                        return $assignment->bankQuestion
                            ? $this->bankQuestionItem($assignment->bankQuestion, (int) $assignment->sort_order)
                            : null;
                    })
                    ->filter()
                    ->values();
            }
        }

        $questions = $section->relationLoaded('questions') ? $section->questions : $section->questions()->get();

        return $questions
            ->map(fn (CognitiveAssessmentQuestion $question): array => $this->legacyQuestionItem($question))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function bankQuestionItem(CognitiveBankQuestion $question, int $assignmentSortOrder): array
    {
        return [
            'source_type' => 'bank',
            'source_id' => (int) $question->id,
            'question_id' => null,
            'bank_question_id' => (int) $question->id,
            'response_key' => $this->buildResponseKey(null, (int) $question->id),
            'question_type' => (string) $question->question_type,
            'difficulty_level' => $question->difficulty_level,
            'question_text' => (string) ($question->question_text ?? 'Question'),
            'question_image' => $question->question_image,
            'question_image_url' => $question->question_image_url,
            'question_bank_title' => $question->questionBank?->title,
            'options' => collect($question->options ?? [])->map(fn ($option): string => (string) $option)->values()->all(),
            'correct_answer' => $question->correct_answer,
            'marks' => (int) $question->marks,
            'sort_order' => $assignmentSortOrder,
            'explanation' => $question->explanation,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyQuestionItem(CognitiveAssessmentQuestion $question): array
    {
        return [
            'source_type' => 'legacy',
            'source_id' => (int) $question->id,
            'question_id' => (int) $question->id,
            'bank_question_id' => null,
            'response_key' => $this->buildResponseKey((int) $question->id, null),
            'question_type' => (string) $question->question_type,
            'difficulty_level' => $question->difficulty_level,
            'question_text' => (string) ($question->question_text ?? 'Question'),
            'question_image' => $question->question_image,
            'question_image_url' => $question->question_image_url,
            'question_bank_title' => null,
            'options' => collect($question->options ?? [])->map(fn ($option): string => (string) $option)->values()->all(),
            'correct_answer' => $question->correct_answer,
            'marks' => (int) $question->marks,
            'sort_order' => (int) $question->sort_order,
            'explanation' => $question->explanation,
        ];
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function assessmentQuestionMap(CognitiveAssessment $assessment): Collection
    {
        return $assessment->sections
            ->flatMap(fn (CognitiveAssessmentSection $section): Collection => $this->sectionQuestionItems($section))
            ->mapWithKeys(fn (array $question): array => [$question['response_key'] => $question]);
    }

    /**
     * @return Collection<string, CognitiveAssessmentResponse>
     */
    private function mapResponsesByQuestionKey(CognitiveAssessmentAttempt $attempt): Collection
    {
        return $attempt->responses
            ->mapWithKeys(function (CognitiveAssessmentResponse $response): array {
                $key = $response->responseKey();

                return $key !== null ? [$key => $response] : [];
            });
    }

    /**
     * @param array{question_id?:int|null,bank_question_id?:int|null} $payload
     * @return array{question_id:int|null,bank_question_id:int|null,response_key:string|null}
     */
    private function questionIdentifiersFromPayload(array $payload): array
    {
        $questionId = isset($payload['question_id']) && $payload['question_id'] !== null ? (int) $payload['question_id'] : null;
        $bankQuestionId = isset($payload['bank_question_id']) && $payload['bank_question_id'] !== null ? (int) $payload['bank_question_id'] : null;

        return [
            'question_id' => $questionId,
            'bank_question_id' => $bankQuestionId,
            'response_key' => $this->buildResponseKey($questionId, $bankQuestionId),
        ];
    }

    private function buildResponseKey(?int $questionId, ?int $bankQuestionId): ?string
    {
        if ($bankQuestionId !== null) {
            return 'bank:'.$bankQuestionId;
        }

        if ($questionId !== null) {
            return 'legacy:'.$questionId;
        }

        return null;
    }

    private function resolveStudentModel(Student|int $student): ?Student
    {
        if ($student instanceof Student) {
            return $student;
        }

        return Student::query()
            ->with('classRoom:id,name,section')
            ->find($student);
    }

    private function resolveAssessmentModel(CognitiveAssessment|int|null $assessment = null): CognitiveAssessment
    {
        if ($assessment instanceof CognitiveAssessment) {
            return $assessment;
        }

        if (is_int($assessment)) {
            return CognitiveAssessment::query()->findOrFail($assessment);
        }

        return $this->resolveAssessment();
    }

    private function findStudentAssignment(int $assessmentId, int $studentId): ?CognitiveAssessmentStudentAssignment
    {
        if (! Schema::hasTable('cognitive_assessment_student_assignments')) {
            return null;
        }

        return CognitiveAssessmentStudentAssignment::query()
            ->with(['enabledBy:id,name', 'disabledBy:id,name'])
            ->where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->first();
    }

    private function attemptStatusLabel(?string $status): string
    {
        return match ($status) {
            CognitiveAssessmentAttempt::STATUS_IN_PROGRESS => 'In Progress',
            CognitiveAssessmentAttempt::STATUS_GRADED => 'Completed',
            CognitiveAssessmentAttempt::STATUS_AUTO_SUBMITTED => 'Auto Submitted',
            CognitiveAssessmentAttempt::STATUS_SUBMITTED => 'Submitted',
            CognitiveAssessmentAttempt::STATUS_RESET => 'Reset',
            default => 'Not Started',
        };
    }

    /**
     * @param Collection<int, mixed> $items
     */
    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $currentPage = max((int) request()->query('page', 1), 1);

        return new PaginationLengthAwarePaginator(
            $items->forPage($currentPage, $perPage)->values(),
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
