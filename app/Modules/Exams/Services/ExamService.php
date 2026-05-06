<?php

namespace App\Modules\Exams\Services;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherResultEntryLog;
use App\Models\User;
use App\Services\AssessmentMarkingModeService;
use App\Services\ClassAssessmentModeService;
use App\Services\ResultLockService;
use App\Services\TeacherPerformanceSyncService;
use App\Services\TeacherStudentVisibilityService;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ExamService
{
    public function __construct(
        private readonly TeacherStudentVisibilityService $visibilityService,
        private readonly ClassAssessmentModeService $assessmentModeService,
        private readonly AssessmentMarkingModeService $markingModeService,
        private readonly TeacherPerformanceSyncService $teacherPerformanceSyncService,
        private readonly ResultLockService $resultLockService,
    ) {
    }

    public function optionsForTeacher(int $userId): array
    {
        $teacher = $this->resolveTeacher($userId);
        if (! $teacher) {
            return [
                'sessions' => [],
                'assignments' => [],
                'exam_types' => ExamType::options(),
            ];
        }

        $assignments = TeacherAssignment::query()
            ->with([
                'classRoom:id,name,section',
                'subject:id,name,code',
            ])
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('subject_id')
            ->orderByDesc('session')
            ->orderBy('class_id')
            ->get(['id', 'class_id', 'subject_id', 'session']);

        $classIds = $assignments->pluck('class_id')->unique()->values();
        $classActiveStudentCount = Student::query()
            ->whereIn('class_id', $classIds)
            ->where('status', 'active')
            ->selectRaw('class_id, count(*) as total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        return [
            'sessions' => $assignments->pluck('session')->unique()->values()->all(),
            'assignments' => $assignments->map(function (TeacherAssignment $assignment) use ($classActiveStudentCount, $teacher): array {
                $classRequiresFiltering = $this->visibilityService->classRequiresSubjectFiltering($assignment->classRoom);
                $classStudents = (int) ($classActiveStudentCount->get($assignment->class_id) ?? 0);
                $subjectStudents = $classStudents;

                if ($classRequiresFiltering) {
                    $subjectStudents = $this->visibilityService
                        ->getVisibleStudentsForSubjectTeacher(
                            (int) $teacher->id,
                            (int) $assignment->class_id,
                            (int) $assignment->subject_id,
                            (string) $assignment->session
                        )
                        ->count();
                }

                return [
                    'class_id' => $assignment->class_id,
                    'class_name' => trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')),
                    'subject_id' => $assignment->subject_id,
                    'subject_name' => $assignment->subject?->name ?? '',
                    'session' => $assignment->session,
                    'supports_grade_mode' => $this->markingModeService->canUseGradeMode($assignment->classRoom),
                    'uses_grade_system' => $this->markingModeService->canUseGradeMode($assignment->classRoom),
                    'class_students' => $classStudents,
                    'subject_students' => $subjectStudents,
                ];
            })->values()->all(),
            'exam_types' => ExamType::options(),
        ];
    }

    /**
     * @return array{
     *   exams:array<int, array{id:int,display_name:string,topic:?string,sequence_number:?int,total_marks:?int,marking_mode:?string}>,
     *   available_bimonthly_options:array<int, array{value:int,label:string,available:bool}>
     * }
     */
    public function contextOptionsForTeacher(
        int $userId,
        int $classId,
        int $subjectId,
        string $session,
        string $examType
    ): array {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->ensureTeacherAssignment((int) $teacher->id, $classId, $subjectId, $session);

        $type = ExamType::tryFrom($examType);
        if (! $type) {
            throw new RuntimeException('Invalid exam type selected.');
        }

        $exams = Exam::query()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', (int) $teacher->id)
            ->where('session', $session)
            ->where('exam_type', $examType)
            ->orderByRaw('CASE WHEN sequence_number IS NULL THEN 999 ELSE sequence_number END')
            ->orderBy('exam_label')
            ->orderByDesc('id')
            ->get([
                'id',
                'exam_type',
                'exam_label',
                'topic',
                'sequence_number',
                'total_marks',
                'marking_mode',
            ])
            ->map(fn (Exam $exam): array => [
                'id' => (int) $exam->id,
                'display_name' => $this->getExamDisplayName($exam),
                'topic' => $exam->topic ? (string) $exam->topic : null,
                'sequence_number' => $exam->sequence_number !== null ? (int) $exam->sequence_number : null,
                'total_marks' => $exam->total_marks !== null ? (int) $exam->total_marks : null,
                'marking_mode' => $exam->marking_mode ? (string) $exam->marking_mode : null,
            ])
            ->values()
            ->all();

        return [
            'exams' => $exams,
            'available_bimonthly_options' => $type === ExamType::BimonthlyTest
                ? $this->getAvailableBimonthlyOptions($classId, $subjectId, (int) $teacher->id, $session)
                : [],
        ];
    }

    /**
     * @return array<int, array{value:int,label:string,available:bool}>
     */
    public function getAvailableBimonthlyOptions(int $classId, int $subjectId, int $teacherId, string $session): array
    {
        $used = Exam::query()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->where('session', $session)
            ->where('exam_type', ExamType::BimonthlyTest->value)
            ->whereNotNull('sequence_number')
            ->pluck('sequence_number')
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value >= 1 && $value <= 4)
            ->unique()
            ->values()
            ->all();
        $usedMap = array_flip($used);

        $options = [];
        foreach ([1, 2, 3, 4] as $sequence) {
            $options[] = [
                'value' => $sequence,
                'label' => $this->bimonthlyLabel($sequence),
                'available' => ! isset($usedMap[$sequence]),
            ];
        }

        return $options;
    }

    public function buildExamLabel(array $data): string
    {
        $examType = (string) ($data['exam_type'] ?? '');
        $type = ExamType::tryFrom($examType);
        if (! $type) {
            throw new RuntimeException('Invalid exam type selected.');
        }

        return match ($type) {
            ExamType::ClassTest => $this->buildClassTestLabel($data),
            ExamType::BimonthlyTest => $this->buildBimonthlyLabel($data),
            ExamType::FirstTerm => 'Midterm',
            ExamType::FinalTerm => 'Final Term',
        };
    }

    public function getExamDisplayName(Exam $exam): string
    {
        return trim((string) $exam->display_name);
    }

    public function validateExamUniqueness(array $data): void
    {
        $classId = (int) ($data['class_id'] ?? 0);
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $teacherId = (int) ($data['teacher_id'] ?? 0);
        $session = trim((string) ($data['session'] ?? ''));
        $examType = trim((string) ($data['exam_type'] ?? ''));
        $label = trim((string) ($data['exam_label'] ?? ''));
        $ignoreExamId = isset($data['ignore_exam_id']) ? (int) $data['ignore_exam_id'] : null;

        if ($classId <= 0 || $subjectId <= 0 || $teacherId <= 0 || $session === '' || $examType === '' || $label === '') {
            throw new RuntimeException('Incomplete exam scope for uniqueness validation.');
        }

        $duplicate = Exam::query()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->where('session', $session)
            ->where('exam_type', $examType)
            ->whereRaw('LOWER(exam_label) = ?', [mb_strtolower($label)])
            ->when($ignoreExamId !== null && $ignoreExamId > 0, fn ($query) => $query->where('id', '!=', $ignoreExamId))
            ->exists();

        if (! $duplicate) {
            return;
        }

        $type = ExamType::tryFrom($examType);
        $errorMessage = match ($type) {
            ExamType::ClassTest => 'A class test with this topic already exists for the selected class/subject/session.',
            ExamType::BimonthlyTest => 'The selected bimonthly number already exists for the selected class/subject/session.',
            ExamType::FirstTerm => 'Midterm already exists for the selected class/subject/session.',
            ExamType::FinalTerm => 'Final Term already exists for the selected class/subject/session.',
            default => 'An exam with this scope already exists.',
        };

        throw ValidationException::withMessages([
            'exam_type' => $errorMessage,
        ]);
    }

    public function createExam(array $data, User $user): Exam
    {
        $teacher = $this->resolveTeacherOrFail((int) $user->id);

        $classId = (int) ($data['class_id'] ?? 0);
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $session = trim((string) ($data['session'] ?? ''));
        $examType = trim((string) ($data['exam_type'] ?? ''));
        $markingMode = trim((string) ($data['marking_mode'] ?? ''));
        $totalMarks = isset($data['total_marks']) ? (int) $data['total_marks'] : null;
        $topic = $this->normalizeTopic(isset($data['topic']) ? (string) $data['topic'] : null);
        $sequenceNumber = isset($data['sequence_number']) ? (int) $data['sequence_number'] : null;
        $label = $this->buildExamLabel([
            'exam_type' => $examType,
            'topic' => $topic,
            'sequence_number' => $sequenceNumber,
        ]);
        $group = $this->resolveExamGroup($examType);

        $payload = [
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => (int) $teacher->id,
            'session' => $session,
            'exam_type' => $examType,
            'exam_group' => $group,
            'exam_label' => $label,
            'topic' => $topic !== '' ? $topic : null,
            'sequence_number' => $sequenceNumber,
            'marking_mode' => $markingMode !== '' ? $markingMode : null,
            'total_marks' => $totalMarks,
        ];

        return DB::transaction(function () use ($payload): Exam {
            $existing = Exam::query()
                ->where('class_id', (int) $payload['class_id'])
                ->where('subject_id', (int) $payload['subject_id'])
                ->where('teacher_id', (int) $payload['teacher_id'])
                ->where('session', (string) $payload['session'])
                ->where('exam_type', (string) $payload['exam_type'])
                ->where('exam_label', (string) $payload['exam_label'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $this->validateExamUniqueness($payload);

            return Exam::query()->create([
                'class_id' => (int) $payload['class_id'],
                'subject_id' => (int) $payload['subject_id'],
                'teacher_id' => (int) $payload['teacher_id'],
                'session' => (string) $payload['session'],
                'exam_type' => (string) $payload['exam_type'],
                'exam_group' => (string) $payload['exam_group'],
                'exam_label' => (string) $payload['exam_label'],
                'topic' => $payload['topic'],
                'sequence_number' => $payload['sequence_number'],
                'marking_mode' => $payload['marking_mode'],
                'total_marks' => $payload['total_marks'],
            ]);
        });
    }

    public function sheet(
        int $userId,
        int $classId,
        int $subjectId,
        string $session,
        string $examType,
        ?int $examId = null,
        ?string $topic = null,
        ?int $sequenceNumber = null
    ): array {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->ensureTeacherAssignment((int) $teacher->id, $classId, $subjectId, $session);

        $exam = $this->resolveExamFromContext(
            (int) $teacher->id,
            $classId,
            $subjectId,
            $session,
            $examType,
            $examId,
            $topic,
            $sequenceNumber
        );

        $students = $this->studentsForExam((int) $teacher->id, $classId, $subjectId, $session);
        $studentIds = $students->pluck('id');
        $requiresSubjectFiltering = $this->visibilityService->classRequiresSubjectFiltering($classId);
        $markingMode = $this->markingModeService->resolveMarkingMode($exam, $classId);
        $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;

        $marksMap = collect();
        if ($exam && $studentIds->isNotEmpty()) {
            $marksMap = Mark::query()
                ->where('exam_id', $exam->id)
                ->whereIn('student_id', $studentIds)
                ->get(['student_id', 'obtained_marks', 'grade'])
                ->keyBy('student_id');
        }

        $lockStatus = $this->resultLockService->statusForScope($session, $classId, $exam?->id ? (int) $exam->id : null);
        $lockedByResultLock = (bool) $lockStatus['is_locked'];
        $lockedByEditWindow = $exam ? $this->isExamLocked($exam) : false;
        $lockedMessage = $lockedByResultLock
            ? $lockStatus['message']
            : (($exam && $lockedByEditWindow) ? 'This exam is locked. Edit window (7 days) has expired.' : null);

        return [
            'exam' => [
                'id' => $exam?->id,
                'display_name' => $exam ? $this->getExamDisplayName($exam) : null,
                'exam_type' => $examType,
                'topic' => $exam?->topic,
                'sequence_number' => $exam?->sequence_number !== null ? (int) $exam->sequence_number : null,
                'total_marks' => $usesGradeSystem ? null : $exam?->total_marks,
                'marking_mode' => $markingMode,
                'locked' => $lockedByResultLock || $lockedByEditWindow,
                'locked_message' => $lockedMessage,
                'lock_type' => $lockStatus['lock_type'],
            ],
            'marking_mode' => $markingMode,
            'uses_grade_system' => $usesGradeSystem,
            'supports_grade_mode' => $this->markingModeService->canUseGradeMode($classId),
            'grade_options' => $this->assessmentModeService->gradeScale(),
            'students' => $students->map(function (Student $student) use ($marksMap): array {
                /** @var Mark|null $mark */
                $mark = $marksMap->get($student->id);

                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'father_name' => $student->father_name,
                    'obtained_marks' => $mark?->obtained_marks !== null ? (int) $mark->obtained_marks : null,
                    'grade' => $mark?->grade,
                ];
            })->values()->all(),
            'message' => $students->isEmpty() && $requiresSubjectFiltering
                ? 'No students are currently assigned to this subject in the selected class.'
                : null,
        ];
    }

    public function saveMarks(
        int $userId,
        int $classId,
        int $subjectId,
        string $session,
        string $examType,
        ?int $totalMarks,
        array $records,
        ?int $examId = null,
        ?string $topic = null,
        ?int $sequenceNumber = null
    ): void {
        $teacher = $this->resolveTeacherOrFail($userId);
        $this->ensureTeacherAssignment((int) $teacher->id, $classId, $subjectId, $session);

        $allowedStudents = $this->studentsForExam((int) $teacher->id, $classId, $subjectId, $session)->keyBy('id');
        if ($allowedStudents->isEmpty()) {
            $message = $this->visibilityService->classRequiresSubjectFiltering($classId)
                ? 'No students are currently assigned to this subject in the selected class.'
                : 'No students found for this class/subject/session.';

            throw new RuntimeException($message);
        }

        $recordStudentIds = collect($records)->pluck('student_id')->map(fn ($id) => (int) $id)->unique();
        if ($recordStudentIds->diff($allowedStudents->keys())->isNotEmpty()) {
            throw new RuntimeException('Invalid student records submitted for this exam sheet.');
        }

        $provisionalMode = $this->markingModeService->resolveMarkingModeForExamContext($classId, $session, $examType, $subjectId);
        $provisionalGradeMode = $provisionalMode === AssessmentMarkingModeService::MODE_GRADE;

        if (! $provisionalGradeMode && ($totalMarks === null || $totalMarks <= 0)) {
            throw new RuntimeException('Total marks are required and must be greater than 0.');
        }

        $user = User::query()->findOrFail($userId);
        if (! $this->resultLockService->canEditResult($user, $session, $classId, $examId)) {
            throw ValidationException::withMessages([
                'error' => 'Results are locked and cannot be modified.',
            ]);
        }

        DB::transaction(function () use (
            $teacher,
            $classId,
            $subjectId,
            $session,
            $examType,
            $totalMarks,
            $records,
            $userId,
            $user,
            $examId,
            $topic,
            $sequenceNumber
        ): void {
            $exam = $this->resolveExamFromContext(
                (int) $teacher->id,
                $classId,
                $subjectId,
                $session,
                $examType,
                $examId,
                $topic,
                $sequenceNumber,
                true
            );

            if (! $exam) {
                $markingMode = $this->markingModeService->resolveMarkingModeForExamContext(
                    $classId,
                    $session,
                    $examType,
                    $subjectId
                );
                $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;

                $exam = $this->createExam([
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'session' => $session,
                    'exam_type' => $examType,
                    'topic' => $topic,
                    'sequence_number' => $sequenceNumber,
                    'marking_mode' => $markingMode,
                    'total_marks' => $usesGradeSystem ? null : $totalMarks,
                ], $user);
            }

            if (! $this->resultLockService->canEditResult($user, $session, $classId, (int) $exam->id)) {
                throw ValidationException::withMessages([
                    'error' => 'Results are locked and cannot be modified.',
                ]);
            }

            $markingMode = $this->markingModeService->resolveMarkingMode($exam, $classId);
            $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;

            if ($this->isExamLocked($exam)) {
                throw new RuntimeException('Exam is locked after 7 days. You cannot edit marks.');
            }

            $examUpdates = [];
            if ((string) $exam->marking_mode !== $markingMode) {
                $examUpdates['marking_mode'] = $markingMode;
            }

            if ($usesGradeSystem) {
                if ($exam->total_marks !== null) {
                    $examUpdates['total_marks'] = null;
                }
            } else {
                if ($totalMarks === null || $totalMarks <= 0) {
                    throw new RuntimeException('Total marks are required and must be greater than 0.');
                }

                if ($exam->total_marks === null) {
                    $examUpdates['total_marks'] = $totalMarks;
                } elseif ((int) $exam->total_marks !== $totalMarks) {
                    throw new RuntimeException('Total marks are already set for this exam and cannot be changed.');
                }
            }

            if ($examUpdates !== []) {
                $exam->forceFill($examUpdates)->save();
            }

            $effectiveTotalMarks = ! $usesGradeSystem
                ? (int) ($exam->total_marks ?? $totalMarks ?? 0)
                : null;

            foreach ($records as $row) {
                $studentId = (int) $row['student_id'];
                $normalizedPayload = $this->markingModeService->validateEntryPayloadByMode((array) $row, $markingMode);
                $obtainedRaw = $normalizedPayload['obtained_marks'];
                $grade = $normalizedPayload['grade'];
                $actionAt = now();

                $existingMark = Mark::query()
                    ->where('exam_id', $exam->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($existingMark && $existingMark->created_at && $existingMark->created_at->lt(now()->subDays(7))) {
                    throw new RuntimeException('Some marks are older than 7 days and cannot be edited.');
                }

                if ($usesGradeSystem) {
                    if ($grade === null) {
                        if ($existingMark) {
                            $this->storeTeacherResultEntryLog([
                                'teacher_id' => (int) $teacher->id,
                                'student_id' => $studentId,
                                'class_id' => $classId,
                                'subject_id' => $subjectId,
                                'session' => $session,
                                'exam_type' => $examType,
                                'old_marks' => $existingMark->obtained_marks,
                                'new_marks' => null,
                                'old_grade' => $existingMark->grade,
                                'new_grade' => null,
                                'action_type' => 'deleted',
                                'action_at' => $actionAt,
                                'acted_by' => $userId,
                                'remarks' => 'Result entry removed from teacher exam sheet.',
                            ]);
                        }

                        Mark::query()
                            ->where('exam_id', $exam->id)
                            ->where('student_id', $studentId)
                            ->delete();
                        continue;
                    }

                    if (! $this->assessmentModeService->isValidGrade($grade)) {
                        throw new RuntimeException('One or more grades are invalid for the selected class.');
                    }

                    Mark::query()->updateOrCreate(
                        [
                            'exam_id' => $exam->id,
                            'student_id' => $studentId,
                        ],
                        [
                            'obtained_marks' => null,
                            'grade' => $grade,
                            'total_marks' => null,
                            'teacher_id' => $teacher->id,
                            'session' => $session,
                        ]
                    );

                    $actionType = $existingMark ? 'updated' : 'created';
                    $oldGrade = $existingMark?->grade;
                    $oldMarks = $existingMark?->obtained_marks;
                    $shouldLog = $actionType === 'created'
                        || $oldGrade !== $grade
                        || $oldMarks !== null;

                    if ($shouldLog) {
                        $this->storeTeacherResultEntryLog([
                            'teacher_id' => (int) $teacher->id,
                            'student_id' => $studentId,
                            'class_id' => $classId,
                            'subject_id' => $subjectId,
                            'session' => $session,
                            'exam_type' => $examType,
                            'old_marks' => $oldMarks,
                            'new_marks' => null,
                            'old_grade' => $oldGrade,
                            'new_grade' => $grade,
                            'action_type' => $actionType,
                            'action_at' => $actionAt,
                            'acted_by' => $userId,
                            'remarks' => $actionType === 'created'
                                ? 'Result entry created from teacher exam sheet.'
                                : 'Result entry updated from teacher exam sheet.',
                        ]);
                    }

                    continue;
                }

                if ($obtainedRaw === null) {
                    if ($existingMark) {
                        $this->storeTeacherResultEntryLog([
                            'teacher_id' => (int) $teacher->id,
                            'student_id' => $studentId,
                            'class_id' => $classId,
                            'subject_id' => $subjectId,
                            'session' => $session,
                            'exam_type' => $examType,
                            'old_marks' => $existingMark->obtained_marks,
                            'new_marks' => null,
                            'old_grade' => $existingMark->grade,
                            'new_grade' => null,
                            'action_type' => 'deleted',
                            'action_at' => $actionAt,
                            'acted_by' => $userId,
                            'remarks' => 'Result entry removed from teacher exam sheet.',
                        ]);
                    }

                    Mark::query()
                        ->where('exam_id', $exam->id)
                        ->where('student_id', $studentId)
                        ->delete();
                    continue;
                }

                $obtained = (int) $obtainedRaw;
                if ($obtained < 0 || $effectiveTotalMarks === null || $effectiveTotalMarks <= 0 || $obtained > $effectiveTotalMarks) {
                    throw new RuntimeException('Obtained marks must be between 0 and total marks.');
                }

                Mark::query()->updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'obtained_marks' => $obtained,
                        'grade' => null,
                        'total_marks' => $effectiveTotalMarks,
                        'teacher_id' => $teacher->id,
                        'session' => $session,
                    ]
                );

                $actionType = $existingMark ? 'updated' : 'created';
                $oldMarks = $existingMark?->obtained_marks;
                $oldGrade = $existingMark?->grade;
                $shouldLog = $actionType === 'created'
                    || (int) ($oldMarks ?? -1) !== $obtained
                    || $oldGrade !== null;

                if ($shouldLog) {
                    $this->storeTeacherResultEntryLog([
                        'teacher_id' => (int) $teacher->id,
                        'student_id' => $studentId,
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                        'session' => $session,
                        'exam_type' => $examType,
                        'old_marks' => $oldMarks,
                        'new_marks' => $obtained,
                        'old_grade' => $oldGrade,
                        'new_grade' => null,
                        'action_type' => $actionType,
                        'action_at' => $actionAt,
                        'acted_by' => $userId,
                        'remarks' => $actionType === 'created'
                            ? 'Result entry created from teacher exam sheet.'
                            : 'Result entry updated from teacher exam sheet.',
                    ]);
                }
            }

            if (! $exam->locked_at && $exam->created_at && $exam->created_at->lt(now()->subDays(7))) {
                $exam->forceFill(['locked_at' => now()])->save();
            }
        });

        $this->teacherPerformanceSyncService->syncAfterMarksChange((int) $teacher->id, $session, $examType);
    }

    private function resolveTeacher(int $userId): ?Teacher
    {
        return Teacher::query()->where('user_id', $userId)->first();
    }

    private function resolveTeacherOrFail(int $userId): Teacher
    {
        $teacher = $this->resolveTeacher($userId);
        if (! $teacher) {
            throw new RuntimeException('Teacher profile not found.');
        }

        return $teacher;
    }

    private function ensureTeacherAssignment(int $teacherId, int $classId, int $subjectId, string $session): void
    {
        $allowed = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You are not assigned to this class/subject/session.');
        }
    }

    private function studentsForExam(int $teacherId, int $classId, int $subjectId, string $session): Collection
    {
        return $this->visibilityService
            ->getVisibleStudentsForSubjectTeacher($teacherId, $classId, $subjectId, $session);
    }

    private function isExamLocked(Exam $exam): bool
    {
        if ($exam->locked_at !== null) {
            return true;
        }

        $createdAt = $exam->created_at instanceof Carbon ? $exam->created_at : Carbon::parse($exam->created_at);

        return $createdAt->lt(now()->subDays(7));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function storeTeacherResultEntryLog(array $payload): void
    {
        TeacherResultEntryLog::query()->create($payload);
    }

    private function resolveExamFromContext(
        int $teacherId,
        int $classId,
        int $subjectId,
        string $session,
        string $examType,
        ?int $examId,
        ?string $topic,
        ?int $sequenceNumber,
        bool $forUpdate = false
    ): ?Exam {
        $query = Exam::query()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->where('session', $session)
            ->where('exam_type', $examType);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        if ($examId !== null && $examId > 0) {
            $exam = (clone $query)->where('id', $examId)->first();
            if (! $exam) {
                throw new RuntimeException('Selected exam record was not found for this class/subject/session.');
            }

            return $exam;
        }

        $type = ExamType::tryFrom($examType);
        if (! $type) {
            throw new RuntimeException('Invalid exam type selected.');
        }

        if ($type === ExamType::ClassTest) {
            $normalizedTopic = $this->normalizeTopic($topic);
            if ($normalizedTopic === '') {
                throw ValidationException::withMessages([
                    'topic' => 'Class Test Topic is required.',
                ]);
            }

            $label = $this->buildExamLabel([
                'exam_type' => $examType,
                'topic' => $normalizedTopic,
            ]);

            return (clone $query)
                ->whereRaw('LOWER(exam_label) = ?', [mb_strtolower($label)])
                ->orderByDesc('id')
                ->first();
        }

        if ($type === ExamType::BimonthlyTest) {
            if ($sequenceNumber === null || ! in_array($sequenceNumber, [1, 2, 3, 4], true)) {
                throw ValidationException::withMessages([
                    'sequence_number' => 'Select bimonthly number from 1st to 4th.',
                ]);
            }

            return (clone $query)
                ->where('sequence_number', $sequenceNumber)
                ->orderByDesc('id')
                ->first();
        }

        return (clone $query)
            ->orderByDesc('id')
            ->first();
    }

    private function buildClassTestLabel(array $data): string
    {
        $topic = $this->normalizeTopic(isset($data['topic']) ? (string) $data['topic'] : null);
        if ($topic === '') {
            throw ValidationException::withMessages([
                'topic' => 'Class Test Topic is required.',
            ]);
        }

        return 'Class Test - '.$topic;
    }

    private function buildBimonthlyLabel(array $data): string
    {
        $sequenceNumber = isset($data['sequence_number']) ? (int) $data['sequence_number'] : null;
        if (! in_array($sequenceNumber, [1, 2, 3, 4], true)) {
            throw ValidationException::withMessages([
                'sequence_number' => 'Bimonthly must be 1st, 2nd, 3rd, or 4th.',
            ]);
        }

        return $this->bimonthlyLabel($sequenceNumber);
    }

    private function normalizeTopic(?string $topic): string
    {
        $value = trim((string) $topic);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function bimonthlyLabel(int $sequenceNumber): string
    {
        return match ($sequenceNumber) {
            1 => '1st Bimonthly',
            2 => '2nd Bimonthly',
            3 => '3rd Bimonthly',
            4 => '4th Bimonthly',
            default => 'Bimonthly',
        };
    }

    private function resolveExamGroup(string $examType): string
    {
        return match ($examType) {
            ExamType::ClassTest->value => 'class_test',
            ExamType::BimonthlyTest->value => 'bimonthly',
            default => 'terminal',
        };
    }
}

