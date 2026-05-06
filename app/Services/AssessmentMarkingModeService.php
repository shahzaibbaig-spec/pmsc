<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Illuminate\Validation\ValidationException;

class AssessmentMarkingModeService
{
    public const MODE_NUMERIC = 'numeric';

    public const MODE_GRADE = 'grade';

    public const MODE_MIXED = 'mixed';

    public function __construct(
        private readonly ClassAssessmentModeService $classAssessmentModeService,
        private readonly ResultLockService $resultLockService
    ) {
    }

    public function classUsesEarlyYearsScheme(SchoolClass|int|string|null $class): bool
    {
        return $this->classAssessmentModeService->classUsesGradeSystem($class);
    }

    public function canUseGradeMode(SchoolClass|int|string|null $class): bool
    {
        return $this->classUsesEarlyYearsScheme($class);
    }

    public function resolveMarkingMode(mixed $examOrAssessment, SchoolClass|int|string|null $class = null): string
    {
        $explicitMode = null;

        if ($examOrAssessment instanceof Exam) {
            $explicitMode = $this->normalizeRawMode($examOrAssessment->marking_mode);
            $class ??= $examOrAssessment->classRoom ?? $examOrAssessment->class_id;
        } elseif (is_array($examOrAssessment)) {
            $explicitMode = $this->normalizeRawMode($examOrAssessment['marking_mode'] ?? null);
            $class ??= $examOrAssessment['class'] ?? $examOrAssessment['class_id'] ?? null;
        } elseif (is_object($examOrAssessment) && isset($examOrAssessment->marking_mode)) {
            $explicitMode = $this->normalizeRawMode($examOrAssessment->marking_mode);
        }

        if (! $this->canUseGradeMode($class)) {
            return self::MODE_NUMERIC;
        }

        return $explicitMode ?? self::MODE_GRADE;
    }

    public function normalizeMarkingMode(?string $mode, SchoolClass|int|string|null $class = null): string
    {
        $normalized = $this->normalizeRawMode($mode);

        if (! $this->canUseGradeMode($class)) {
            return self::MODE_NUMERIC;
        }

        return $normalized ?? self::MODE_NUMERIC;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{obtained_marks:int|null,grade:?string}
     */
    public function validateEntryPayloadByMode(array $data, string $markingMode): array
    {
        $resolvedMode = $this->normalizeMarkingMode($markingMode);
        $grade = $this->classAssessmentModeService->normalizeGrade(
            is_string($data['grade'] ?? null) ? (string) $data['grade'] : null
        );
        $obtained = $data['obtained_marks'] ?? null;

        if ($resolvedMode === self::MODE_GRADE) {
            return [
                'obtained_marks' => null,
                'grade' => $grade,
            ];
        }

        if ($obtained === '' || $obtained === null || ! is_numeric($obtained)) {
            return [
                'obtained_marks' => null,
                'grade' => null,
            ];
        }

        return [
            'obtained_marks' => (int) round((float) $obtained),
            'grade' => null,
        ];
    }

    public function resolveMarkingModeForExamContext(
        int $classId,
        string $session,
        string $examType,
        ?int $subjectId = null
    ): string {
        $examQuery = Exam::query()
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('exam_type', $examType);

        if ($subjectId !== null) {
            $examQuery->where('subject_id', $subjectId);
        }

        $exam = $examQuery
            ->orderBy('id')
            ->first();

        return $this->resolveMarkingMode($exam, $classId);
    }

    /**
     * @return array{
     *   class_id:int,
     *   class_name:string,
     *   session:string,
     *   exam_type:string,
     *   supports_grade_mode:bool,
     *   marking_mode:string,
     *   mode_options:array<int,array{value:string,label:string}>,
     *   configured_subjects:int,
     *   expected_subjects:int
     * }
     */
    public function classExamModeContext(int $classId, string $session, string $examType): array
    {
        $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
        if (! $classRoom) {
            throw new RuntimeException('Selected class was not found.');
        }

        $supportsGradeMode = $this->canUseGradeMode($classRoom);

        $assignmentSubjects = TeacherAssignment::query()
            ->where('class_id', $classId)
            ->where('session', $session)
            ->whereNotNull('subject_id')
            ->distinct('subject_id')
            ->pluck('subject_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        $existingExams = Exam::query()
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('exam_type', $examType)
            ->when(
                $assignmentSubjects->isNotEmpty(),
                fn ($builder) => $builder->whereIn('subject_id', $assignmentSubjects->all())
            )
            ->get(['id', 'class_id', 'marking_mode']);

        $modes = $existingExams
            ->map(fn (Exam $exam): string => $this->resolveMarkingMode($exam, $classRoom))
            ->unique()
            ->values();

        $resolvedMode = $supportsGradeMode ? self::MODE_GRADE : self::MODE_NUMERIC;
        if (! $supportsGradeMode) {
            $resolvedMode = self::MODE_NUMERIC;
        } elseif ($modes->count() === 1) {
            $resolvedMode = (string) $modes->first();
        } elseif ($modes->count() > 1) {
            $resolvedMode = self::MODE_MIXED;
        }

        return [
            'class_id' => (int) $classRoom->id,
            'class_name' => trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? '')),
            'session' => $session,
            'exam_type' => $examType,
            'supports_grade_mode' => $supportsGradeMode,
            'marking_mode' => $resolvedMode,
            'mode_options' => $this->modeOptionsForClass($classRoom),
            'configured_subjects' => $existingExams->count(),
            'expected_subjects' => $assignmentSubjects->count(),
        ];
    }

    /**
     * @return array{marking_mode:string,created_exams:int,updated_exams:int,total_exams:int}
     */
    public function configureClassExamMarkingMode(
        int $classId,
        string $session,
        string $examType,
        string $markingMode
    ): array {
        $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
        if (! $classRoom) {
            throw new RuntimeException('Selected class was not found.');
        }

        $resolvedMode = $this->normalizeMarkingMode($markingMode, $classRoom);
        if ($resolvedMode === self::MODE_GRADE && ! $this->canUseGradeMode($classRoom)) {
            throw new RuntimeException('Grade mode is only allowed for Early Years classes (PG, Prep, Nursery, and Class 1).');
        }

        return DB::transaction(function () use ($classId, $session, $examType, $resolvedMode): array {
            $user = auth()->user();
            if ($user instanceof User && ! $this->resultLockService->canEditResult($user, $session, $classId, null)) {
                throw ValidationException::withMessages([
                    'error' => 'Results are locked and cannot be modified.',
                ]);
            }

            $assignments = TeacherAssignment::query()
                ->where('class_id', $classId)
                ->where('session', $session)
                ->whereNotNull('subject_id')
                ->whereNotNull('teacher_id')
                ->orderByDesc('id')
                ->get(['teacher_id', 'subject_id'])
                ->unique('subject_id')
                ->values();

            if ($assignments->isEmpty()) {
                throw new RuntimeException('Assign teachers for this class/session before configuring assessment mode.');
            }

            $subjectIds = $assignments
                ->pluck('subject_id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->values();

            $existingExams = Exam::query()
                ->where('class_id', $classId)
                ->where('session', $session)
                ->where('exam_type', $examType)
                ->whereIn('subject_id', $subjectIds->all())
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (Exam $exam): int => (int) $exam->subject_id);

            $createdCount = 0;
            $updatedCount = 0;

            foreach ($assignments as $assignment) {
                $subjectId = (int) $assignment->subject_id;
                $teacherId = (int) $assignment->teacher_id;
                /** @var Exam|null $exam */
                $exam = $existingExams->get($subjectId);

                if (! $exam) {
                    $defaults = $this->defaultExamMetadata($examType);

                    Exam::query()->create([
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                        'exam_type' => $examType,
                        'exam_group' => $defaults['exam_group'],
                        'exam_label' => $defaults['exam_label'],
                        'session' => $session,
                        'marking_mode' => $resolvedMode,
                        'total_marks' => null,
                        'teacher_id' => $teacherId,
                    ]);
                    $createdCount++;

                    continue;
                }

                $currentMode = $this->resolveMarkingMode($exam, $classId);
                $modeWillChange = $currentMode !== $resolvedMode;

                if ($user instanceof User && ! $this->resultLockService->canEditResult($user, $session, $classId, (int) $exam->id)) {
                    throw ValidationException::withMessages([
                        'error' => 'Results are locked and cannot be modified.',
                    ]);
                }

                if ($modeWillChange && Mark::query()->where('exam_id', (int) $exam->id)->exists()) {
                    throw new RuntimeException('Marking mode cannot be changed because result entries already exist for one or more subjects.');
                }

                $updates = [];
                if ((string) $exam->marking_mode !== $resolvedMode) {
                    $updates['marking_mode'] = $resolvedMode;
                }

                if ($resolvedMode === self::MODE_GRADE && $exam->total_marks !== null) {
                    $updates['total_marks'] = null;
                }

                if ($updates !== []) {
                    $exam->forceFill($updates)->save();
                    $updatedCount++;
                }
            }

            return [
                'marking_mode' => $resolvedMode,
                'created_exams' => $createdCount,
                'updated_exams' => $updatedCount,
                'total_exams' => $assignments->count(),
            ];
        });
    }

    /**
     * @return array{exam_id:int,marking_mode:string,created:bool}
     */
    public function configureExamMarkingMode(
        int $classId,
        int $subjectId,
        string $session,
        string $examType,
        string $markingMode
    ): array {
        $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
        if (! $classRoom) {
            throw new RuntimeException('Selected class was not found.');
        }

        $resolvedMode = $this->normalizeMarkingMode($markingMode, $classRoom);

        if ($resolvedMode === self::MODE_GRADE && ! $this->canUseGradeMode($classRoom)) {
            throw new RuntimeException('Grade mode is only allowed for Early Years classes (PG, Prep, Nursery, and Class 1).');
        }

        return DB::transaction(function () use (
            $classId,
            $subjectId,
            $session,
            $examType,
            $resolvedMode
        ): array {
            $user = auth()->user();

            $exam = Exam::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('session', $session)
                ->where('exam_type', $examType)
                ->lockForUpdate()
                ->first();

            if ($exam) {
                if ($user instanceof User && ! $this->resultLockService->canEditResult($user, $session, $classId, (int) $exam->id)) {
                    throw ValidationException::withMessages([
                        'error' => 'Results are locked and cannot be modified.',
                    ]);
                }

                $currentMode = $this->resolveMarkingMode($exam, $classId);
                if ($currentMode !== $resolvedMode) {
                    $hasEntries = Mark::query()
                        ->where('exam_id', $exam->id)
                        ->exists();

                    if ($hasEntries) {
                        throw new RuntimeException('Marking mode cannot be changed after result entries exist for this assessment.');
                    }
                }

                $updatePayload = ['marking_mode' => $resolvedMode];
                if ($resolvedMode === self::MODE_GRADE) {
                    $updatePayload['total_marks'] = null;
                }

                $exam->forceFill($updatePayload)->save();

                return [
                    'exam_id' => (int) $exam->id,
                    'marking_mode' => $resolvedMode,
                    'created' => false,
                ];
            }

            $assignment = TeacherAssignment::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('session', $session)
                ->orderByDesc('id')
                ->first(['teacher_id']);

            if (! $assignment || ! $assignment->teacher_id) {
                throw new RuntimeException('Assign a teacher to this class/subject/session before configuring assessment mode.');
            }

            $createdExam = Exam::query()->create([
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'exam_type' => $examType,
                'exam_group' => $this->defaultExamMetadata($examType)['exam_group'],
                'exam_label' => $this->defaultExamMetadata($examType)['exam_label'],
                'session' => $session,
                'marking_mode' => $resolvedMode,
                'total_marks' => null,
                'teacher_id' => (int) $assignment->teacher_id,
            ]);

            return [
                'exam_id' => (int) $createdExam->id,
                'marking_mode' => $resolvedMode,
                'created' => true,
            ];
        });
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    public function modeOptionsForClass(SchoolClass|int|string|null $class): array
    {
        $options = [
            ['value' => self::MODE_NUMERIC, 'label' => 'Numeric Marks'],
        ];

        if ($this->canUseGradeMode($class)) {
            $options[] = ['value' => self::MODE_GRADE, 'label' => 'Grades'];
        }

        return $options;
    }

    private function normalizeRawMode(mixed $mode): ?string
    {
        $candidate = strtolower(trim((string) $mode));

        return in_array($candidate, [self::MODE_NUMERIC, self::MODE_GRADE], true)
            ? $candidate
            : null;
    }

    /**
     * @return array{exam_group:string,exam_label:string}
     */
    private function defaultExamMetadata(string $examType): array
    {
        return match ($examType) {
            'class_test' => ['exam_group' => 'class_test', 'exam_label' => 'Class Test'],
            'bimonthly_test' => ['exam_group' => 'bimonthly', 'exam_label' => '1st Bimonthly'],
            'first_term' => ['exam_group' => 'terminal', 'exam_label' => 'Midterm'],
            'final_term' => ['exam_group' => 'terminal', 'exam_label' => 'Final Term'],
            default => ['exam_group' => 'terminal', 'exam_label' => str_replace('_', ' ', ucfirst($examType))],
        };
    }
}
