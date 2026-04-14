<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\AssessmentMarkingModeService;
use App\Services\ClassAssessmentModeService;
use App\Services\TeacherStudentVisibilityService;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherResultController extends Controller
{
    public function __construct(
        private readonly TeacherStudentVisibilityService $visibilityService,
        private readonly AssessmentMarkingModeService $markingModeService,
        private readonly ClassAssessmentModeService $assessmentModeService
    ) {
    }

    public function classResults(Request $request): View
    {
        $teacher = Teacher::query()
            ->where('user_id', (int) $request->user()->id)
            ->first();

        $examTypes = ExamType::options();
        $examTypeValues = array_column($examTypes, 'value');
        $defaultExamType = in_array('final_term', $examTypeValues, true)
            ? 'final_term'
            : ($examTypeValues[0] ?? 'first_term');

        if (! $teacher) {
            return view('modules.teacher.results.class', [
                'sessions' => $this->sessionOptions(),
                'classes' => collect(),
                'examTypes' => $examTypes,
                'selectedSession' => $this->sessionOptions()[1] ?? ($this->sessionOptions()[0] ?? now()->year.'-'.(now()->year + 1)),
                'selectedClassId' => null,
                'selectedExamType' => $defaultExamType,
                'isClassTeacherView' => false,
                'usesGradeSystem' => false,
                'gradeOptions' => $this->assessmentModeService->gradeScale(),
                'teacherSubjectIds' => [],
                'mySubjectResults' => ['subjects' => [], 'rows' => [], 'total_rows' => 0],
                'classResults' => null,
                'message' => 'Teacher profile not found for this account.',
            ]);
        }

        $classes = $this->classesForTeacher((int) $teacher->id);
        $sessions = $this->sessionsForTeacher((int) $teacher->id);

        $selectedSession = $request->filled('session') && in_array((string) $request->input('session'), $sessions, true)
            ? (string) $request->input('session')
            : ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $selectedExamType = $request->filled('exam_type') && in_array((string) $request->input('exam_type'), $examTypeValues, true)
            ? (string) $request->input('exam_type')
            : $defaultExamType;

        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        if ($selectedClassId !== null && ! $classes->contains('id', $selectedClassId)) {
            $selectedClassId = null;
        }
        if ($selectedClassId === null && $classes->isNotEmpty()) {
            $selectedClassId = (int) $classes->first()->id;
        }

        $selectedClass = $selectedClassId !== null
            ? $classes->firstWhere('id', $selectedClassId)
            : null;
        $selectedClassRequiresFiltering = $selectedClass
            ? $this->visibilityService->classRequiresSubjectFiltering($selectedClass)
            : false;

        $isClassTeacherView = $selectedClass
            ? (int) ($selectedClass->class_teacher_id ?? 0) === (int) $teacher->id
            : false;
        $markingMode = $selectedClass
            ? $this->markingModeService->resolveMarkingModeForExamContext(
                (int) $selectedClass->id,
                $selectedSession,
                $selectedExamType
            )
            : AssessmentMarkingModeService::MODE_NUMERIC;
        $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;

        $teacherSubjectIds = [];
        $mySubjectResults = ['subjects' => [], 'rows' => [], 'total_rows' => 0];
        $classResults = null;
        $message = null;

        if ($selectedClassId !== null) {
            $teacherSubjectIds = TeacherAssignment::query()
                ->where('teacher_id', (int) $teacher->id)
                ->where('class_id', (int) $selectedClassId)
                ->where('session', $selectedSession)
                ->whereNotNull('subject_id')
                ->pluck('subject_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($isClassTeacherView) {
                $classSubjectIds = SchoolClass::query()
                    ->find((int) $selectedClassId)?->subjects()
                    ->pluck('subjects.id')
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all() ?? [];

                if ($selectedClassRequiresFiltering) {
                    $classSubjectIds = $teacherSubjectIds;

                    if ($teacherSubjectIds === []) {
                        $message = 'You are not assigned any subject for this class and session.';
                    }
                }

                $mySubjectResults = $this->buildDataset(
                    (int) $teacher->id,
                    (int) $selectedClassId,
                    $selectedSession,
                    $selectedExamType,
                    $markingMode,
                    $teacherSubjectIds,
                    $teacherSubjectIds,
                    $selectedClassRequiresFiltering
                );

                $classResults = $this->buildDataset(
                    (int) $teacher->id,
                    (int) $selectedClassId,
                    $selectedSession,
                    $selectedExamType,
                    $markingMode,
                    $classSubjectIds,
                    $teacherSubjectIds,
                    $selectedClassRequiresFiltering
                );
            } else {
                if ($teacherSubjectIds === []) {
                    $message = 'You are not assigned any subject for this class and session.';
                } else {
                    $mySubjectResults = $this->buildDataset(
                        (int) $teacher->id,
                        (int) $selectedClassId,
                        $selectedSession,
                        $selectedExamType,
                        $markingMode,
                        $teacherSubjectIds,
                        $teacherSubjectIds,
                        $selectedClassRequiresFiltering
                    );
                }
            }
        } else {
            $message = 'No class is assigned to your account yet.';
        }

        return view('modules.teacher.results.class', [
            'sessions' => $sessions,
            'classes' => $classes,
            'examTypes' => $examTypes,
            'selectedSession' => $selectedSession,
            'selectedClassId' => $selectedClassId,
            'selectedExamType' => $selectedExamType,
            'markingMode' => $markingMode,
            'isClassTeacherView' => $isClassTeacherView,
            'usesGradeSystem' => $usesGradeSystem,
            'gradeOptions' => $this->assessmentModeService->gradeScale(),
            'teacherSubjectIds' => $teacherSubjectIds,
            'mySubjectResults' => $mySubjectResults,
            'classResults' => $classResults,
            'message' => $message,
        ]);
    }

    private function classesForTeacher(int $teacherId)
    {
        $assignmentClassIds = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $classTeacherClassIds = SchoolClass::query()
            ->where('class_teacher_id', $teacherId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $classIds = $assignmentClassIds
            ->merge($classTeacherClassIds)
            ->unique()
            ->values();

        if ($classIds->isEmpty()) {
            return collect();
        }

        return SchoolClass::query()
            ->whereIn('id', $classIds->all())
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section', 'class_teacher_id']);
    }

    private function sessionsForTeacher(int $teacherId): array
    {
        $sessions = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->pluck('session')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($sessions !== []) {
            return $sessions;
        }

        $classTeacherClassIds = SchoolClass::query()
            ->where('class_teacher_id', $teacherId)
            ->pluck('id');

        if ($classTeacherClassIds->isNotEmpty()) {
            $examSessions = Exam::query()
                ->whereIn('class_id', $classTeacherClassIds)
                ->pluck('session')
                ->filter()
                ->unique()
                ->sortDesc()
                ->values()
                ->all();

            if ($examSessions !== []) {
                return $examSessions;
            }
        }

        return $this->sessionOptions();
    }

    /**
     * @param array<int, int> $subjectIds
     * @param array<int, int> $editableSubjectIds
     */
    private function buildDataset(
        int $teacherId,
        int $classId,
        string $session,
        string $examType,
        string $markingMode,
        array $subjectIds,
        array $editableSubjectIds,
        bool $requiresSubjectFiltering = false
    ): array {
        $normalizedSubjectIds = collect($subjectIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;

        if ($normalizedSubjectIds === []) {
            return [
                'subjects' => [],
                'rows' => [],
                'total_rows' => 0,
                'marking_mode' => $markingMode,
                'uses_grade_system' => $usesGradeSystem,
            ];
        }

        $subjectCollection = Subject::query()
            ->whereIn('id', $normalizedSubjectIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjectLookup = $subjectCollection
            ->mapWithKeys(fn (Subject $subject): array => [
                (int) $subject->id => (string) $subject->name,
            ]);

        $editableLookup = collect($editableSubjectIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->flip()
            ->all();

        $marks = Mark::query()
            ->with([
                'student:id,name,student_id,class_id',
                'exam:id,class_id,subject_id,exam_type,session,total_marks',
            ])
            ->where('session', $session)
            ->whereHas('exam', function ($query) use ($classId, $examType, $normalizedSubjectIds, $session): void {
                $query->where('class_id', $classId)
                    ->where('exam_type', $examType)
                    ->where('session', $session)
                    ->whereIn('subject_id', $normalizedSubjectIds);
            })
            ->whereHas('student', function ($query) use ($classId): void {
                $query->where('class_id', $classId);
            })
            ->get(['id', 'exam_id', 'student_id', 'obtained_marks', 'total_marks', 'grade', 'session']);

        $rows = $marks->map(function (Mark $mark) use (
            $teacherId,
            $subjectLookup,
            $editableLookup,
            $classId,
            $session,
            $examType,
            $requiresSubjectFiltering,
            $usesGradeSystem
        ): ?array {
            $subjectId = (int) ($mark->exam?->subject_id ?? 0);
            if ($subjectId <= 0) {
                return null;
            }

            if ($requiresSubjectFiltering) {
                $canAccess = $this->visibilityService->teacherCanAccessStudentForSubject(
                    $teacherId,
                    (int) $mark->student_id,
                    $subjectId,
                    $session
                );

                if (! $canAccess) {
                    return null;
                }
            }

            $grade = $usesGradeSystem ? $this->assessmentModeService->normalizeGrade($mark->grade) : null;
            $totalMarks = $usesGradeSystem ? null : (float) ($mark->total_marks ?: $mark->exam?->total_marks ?: 0);
            $obtained = $usesGradeSystem ? null : (float) $mark->obtained_marks;
            $percentage = $usesGradeSystem
                ? null
                : ($totalMarks > 0 ? round(($obtained / $totalMarks) * 100, 2) : 0.0);

            return [
                'mark_id' => (int) $mark->id,
                'subject_id' => $subjectId,
                'subject_name' => (string) ($subjectLookup->get($subjectId) ?? 'Subject'),
                'student_name' => (string) ($mark->student?->name ?? 'Student'),
                'student_id' => (string) ($mark->student?->student_id ?? '-'),
                'obtained_marks' => $obtained,
                'total_marks' => $totalMarks,
                'percentage' => $percentage,
                'grade' => $usesGradeSystem ? $grade : $this->grade((float) $percentage),
                'grade_label' => $usesGradeSystem ? $this->assessmentModeService->gradeLabel($grade) : null,
                'can_edit' => isset($editableLookup[$subjectId]),
                'edit_url' => route('teacher.exams.index', [
                    'session' => $session,
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'exam_type' => $examType,
                ]),
            ];
        })
            ->filter()
            ->sortBy(fn (array $row): string => $row['subject_name'].'|'.$row['student_name'])
            ->values();

        $subjects = $subjectCollection->map(function (Subject $subject) use ($rows): array {
            $subjectRows = $rows->where('subject_id', (int) $subject->id);
            $dominantGrade = $this->assessmentModeService->dominantGrade($subjectRows->pluck('grade')->all());
            $avgPercentage = $subjectRows->isEmpty()
                ? null
                : round((float) $subjectRows->avg('percentage'), 2);

            return [
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
                'rows_count' => $subjectRows->count(),
                'avg_percentage' => $avgPercentage,
                'dominant_grade' => $dominantGrade,
                'dominant_grade_label' => $this->assessmentModeService->gradeLabel($dominantGrade),
            ];
        })->values()->all();

        return [
            'subjects' => $subjects,
            'rows' => $rows->all(),
            'total_rows' => $rows->count(),
            'marking_mode' => $markingMode,
            'uses_grade_system' => $usesGradeSystem,
        ];
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

    private function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        $sessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return array_reverse($sessions);
    }
}
