<?php

namespace App\Modules\Results\Services;

use BackedEnum;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Services\AssessmentMarkingModeService;
use App\Services\ClassAssessmentModeService;
use App\Modules\Exams\Enums\ExamType;
use RuntimeException;

class ResultSheetService
{
    public function __construct(
        private readonly ResultService $resultService,
        private readonly AssessmentMarkingModeService $markingModeService,
        private readonly ClassAssessmentModeService $assessmentModeService
    )
    {
    }

    /**
     * @return array{
     *   class:array{id:int,name:string},
     *   exam:array{session:string,exam_type:string,exam_type_label:string,generated_at:string},
     *   subjects:array<int,array{id:int,name:string,total_marks:int}>,
     *   rows:array<int,array{
     *     student_id:int,student_code:string,student_name:string,position:int,
     *     subject_marks:array<int,array{obtained:int,total:int}>,
     *     total_marks:int,obtained_marks:int,percentage:float,grade:string
     *   }>,
     *   summary:array{
     *     students_count:int,
     *     subjects_count:int,
     *     total_marks_per_student:int,
     *     class_average_percentage:float
     *   }
     * }
     */
    public function classSheet(int $classId, string $session): array
    {
        $classRoom = SchoolClass::query()
            ->find($classId, ['id', 'name', 'section']);

        if (! $classRoom) {
            throw new RuntimeException('Class not found.');
        }

        $examScope = $this->resolveExamScope($classId, $session);
        $resolvedExamType = $examScope['exam_type'];
        $resolvedExamLabel = $examScope['exam_label'];

        $exams = Exam::query()
            ->with('subject:id,name')
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('exam_type', $resolvedExamType)
            ->when(
                $resolvedExamLabel !== null && trim($resolvedExamLabel) !== '',
                fn ($builder) => $builder->where('exam_label', (string) $resolvedExamLabel)
            )
            ->orderBy('subject_id')
            ->get(['id', 'subject_id', 'exam_type', 'exam_label', 'session', 'total_marks', 'marking_mode']);

        if ($exams->isEmpty()) {
            throw new RuntimeException('No exams found for selected class and session.');
        }

        $markingMode = $this->markingModeService->resolveMarkingModeForExamContext($classId, $session, $resolvedExamType);
        $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;

        $subjects = $exams->map(function (Exam $exam) use ($usesGradeSystem): array {
            return [
                'id' => (int) $exam->subject_id,
                'name' => (string) ($exam->subject?->name ?? 'Subject'),
                'total_marks' => $usesGradeSystem ? null : (int) $exam->total_marks,
            ];
        })->sortBy('name')->values();

        $examsBySubjectId = $exams
            ->keyBy(fn (Exam $exam): int => (int) $exam->subject_id);

        $historyStudentIds = StudentClassHistory::query()
            ->where('class_id', $classId)
            ->where('session', $session)
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $students = ($historyStudentIds->isNotEmpty()
            ? Student::query()->whereIn('id', $historyStudentIds)
            : Student::query()->where('class_id', $classId)->where('status', 'active'))
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name']);

        if ($students->isEmpty()) {
            throw new RuntimeException('No active students found for selected class.');
        }

        $marks = Mark::query()
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get(['exam_id', 'student_id', 'obtained_marks', 'total_marks', 'grade']);

        $markLookup = $marks
            ->groupBy(fn (Mark $mark): int => (int) $mark->student_id)
            ->map(function ($rows) {
                return $rows->keyBy(fn (Mark $mark): int => (int) $mark->exam_id);
            });

        $rows = [];
        foreach ($students as $student) {
            $studentMarks = $markLookup->get((int) $student->id, collect());

            $subjectMarks = [];
            $totalMarks = $usesGradeSystem ? null : 0;
            $obtainedMarks = $usesGradeSystem ? null : 0;
            $gradeBucket = [];

            foreach ($subjects as $subject) {
                $subjectId = (int) $subject['id'];
                /** @var Exam|null $exam */
                $exam = $examsBySubjectId->get($subjectId);
                if (! $exam) {
                    continue;
                }

                /** @var Mark|null $mark */
                $mark = $studentMarks->get((int) $exam->id);
                if ($usesGradeSystem) {
                    $grade = $this->assessmentModeService->normalizeGrade($mark?->grade);
                    $subjectMarks[$subjectId] = [
                        'grade' => $grade,
                        'label' => $this->assessmentModeService->gradeLabel($grade),
                    ];

                    if ($grade !== null) {
                        $gradeBucket[] = $grade;
                    }
                } else {
                    $subjectTotal = (int) ($mark?->total_marks ?? $exam->total_marks);
                    $subjectObtained = (int) ($mark?->obtained_marks ?? 0);

                    $subjectMarks[$subjectId] = [
                        'obtained' => $subjectObtained,
                        'total' => $subjectTotal,
                    ];

                    $totalMarks += $subjectTotal;
                    $obtainedMarks += $subjectObtained;
                }
            }

            $percentage = ! $usesGradeSystem && $totalMarks > 0
                ? round(($obtainedMarks / $totalMarks) * 100, 2)
                : null;
            $overallGrade = $usesGradeSystem
                ? $this->assessmentModeService->dominantGrade($gradeBucket)
                : $this->resultService->computeGrade((float) $percentage);

            $rows[] = [
                'student_id' => (int) $student->id,
                'student_code' => (string) ($student->student_id ?: $student->id),
                'student_name' => (string) $student->name,
                'position' => $usesGradeSystem ? null : 0,
                'subject_marks' => $subjectMarks,
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
                'percentage' => $percentage,
                'grade' => $overallGrade,
                'grade_label' => $usesGradeSystem ? $this->assessmentModeService->gradeLabel($overallGrade) : null,
            ];
        }

        $rankedRows = $usesGradeSystem ? $rows : $this->assignPositions($rows);

        return [
            'class' => [
                'id' => (int) $classRoom->id,
                'name' => trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? '')),
            ],
            'exam' => [
                'session' => $session,
                'exam_type' => $resolvedExamType,
                'exam_type_label' => $resolvedExamLabel !== null && trim($resolvedExamLabel) !== ''
                    ? (string) $resolvedExamLabel
                    : $this->examTypeLabel($resolvedExamType),
                'exam_label' => $resolvedExamLabel,
                'generated_at' => now()->toDateString(),
            ],
            'marking_mode' => $markingMode,
            'uses_grade_system' => $usesGradeSystem,
            'subjects' => $subjects->all(),
            'rows' => $rankedRows,
            'summary' => [
                'students_count' => count($rankedRows),
                'subjects_count' => $subjects->count(),
                'total_marks_per_student' => $usesGradeSystem ? null : (int) ($subjects->sum('total_marks')),
                'class_average_percentage' => ! $usesGradeSystem && count($rankedRows) > 0
                    ? round((float) collect($rankedRows)->avg('percentage'), 2)
                    : null,
            ],
        ];
    }

    /**
     * @return array{exam_type:string,exam_label:?string}
     */
    private function resolveExamScope(int $classId, string $session): array
    {
        $availableTypes = Exam::query()
            ->where('class_id', $classId)
            ->where('session', $session)
            ->pluck('exam_type')
            ->map(fn ($type): string => $this->normalizeExamType($type))
            ->unique()
            ->values();

        if ($availableTypes->isEmpty()) {
            throw new RuntimeException('No exam data found for selected class and session.');
        }

        $priority = [
            ExamType::FinalTerm->value => 4,
            ExamType::FirstTerm->value => 3,
            ExamType::BimonthlyTest->value => 2,
            ExamType::ClassTest->value => 1,
        ];

        $examType = (string) $availableTypes
            ->sortByDesc(fn (string $type): int => $priority[$type] ?? 0)
            ->first();

        $examLabel = null;
        if (in_array($examType, [ExamType::ClassTest->value, ExamType::BimonthlyTest->value], true)) {
            $labelQuery = Exam::query()
                ->where('class_id', $classId)
                ->where('session', $session)
                ->where('exam_type', $examType);

            if ($examType === ExamType::BimonthlyTest->value) {
                $labelQuery->orderByDesc('sequence_number');
            }

            $examLabel = $labelQuery
                ->orderByDesc('id')
                ->value('exam_label');

            $examLabel = $examLabel !== null ? trim((string) $examLabel) : null;
            if ($examLabel === '') {
                $examLabel = null;
            }
        }

        return [
            'exam_type' => $examType,
            'exam_label' => $examLabel,
        ];
    }

    private function normalizeExamType(mixed $type): string
    {
        if ($type instanceof BackedEnum) {
            return (string) $type->value;
        }

        return (string) $type;
    }

    /**
     * @param array<int, array{
     *   student_id:int,student_code:string,student_name:string,position:int,
     *   subject_marks:array<int,array{obtained:int,total:int}>,
     *   total_marks:int,obtained_marks:int,percentage:float,grade:string
     * }> $rows
     * @return array<int, array{
     *   student_id:int,student_code:string,student_name:string,position:int,
     *   subject_marks:array<int,array{obtained:int,total:int}>,
     *   total_marks:int,obtained_marks:int,percentage:float,grade:string
     * }>
     */
    private function assignPositions(array $rows): array
    {
        usort($rows, function (array $left, array $right): int {
            if ($left['obtained_marks'] !== $right['obtained_marks']) {
                return $right['obtained_marks'] <=> $left['obtained_marks'];
            }

            if ($left['percentage'] !== $right['percentage']) {
                return $right['percentage'] <=> $left['percentage'];
            }

            return strcasecmp($left['student_name'], $right['student_name']);
        });

        $position = 0;
        $index = 0;
        $lastScore = null;

        foreach ($rows as &$row) {
            $index++;
            if ($lastScore === null || $row['obtained_marks'] !== $lastScore) {
                $position = $index;
                $lastScore = $row['obtained_marks'];
            }

            $row['position'] = $position;
        }
        unset($row);

        return $rows;
    }

    private function examTypeLabel(string $examType): string
    {
        $type = ExamType::tryFrom($examType);

        return $type?->label() ?? str_replace('_', ' ', ucfirst($examType));
    }
}
