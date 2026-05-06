<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\StudentResult;
use App\Services\AssessmentMarkingModeService;
use App\Services\ClassAssessmentModeService;
use App\Services\StudentUserResolverService;
use App\Services\StudentResultService;
use App\Modules\Exams\Enums\ExamType;
use App\Modules\Results\Requests\StudentResultIndexRequest;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentResultController extends Controller
{
    public function __construct(
        private readonly StudentResultService $studentResultService,
        private readonly ClassAssessmentModeService $assessmentModeService,
        private readonly AssessmentMarkingModeService $markingModeService,
        private readonly StudentUserResolverService $studentUserResolver
    ) {}

    public function index(StudentResultIndexRequest $request): View
    {
        $user = auth()->user();
        $student = $user ? $this->studentUserResolver->resolveForUser($user) : null;

        if (! $student) {
            return view('modules.student.results', [
                'student' => null,
                'sessions' => $this->studentResultService->sessionOptions(),
                'selectedSession' => null,
                'sessionClassName' => null,
                'groupedResults' => collect(),
                'message' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        $sessions = $this->studentResultService->availableSessionsForStudent((int) $student->id);
        $selectedSession = $this->studentResultService->resolveRequestedSession(
            $request->validated('session'),
            $sessions
        );

        $legacyResults = $this->studentResultService->getStudentResults((int) $student->id, $selectedSession);

        if ($legacyResults->isNotEmpty()) {
            $groupedResults = $this->groupLegacyResults(
                $legacyResults,
                $this->assessmentModeService->classUsesGradeSystem($legacyResults->first()?->classRoom)
            );
        } else {
            $marks = Mark::query()
                ->with([
                    'exam:id,class_id,subject_id,exam_type,session,total_marks,marking_mode,created_at',
                    'exam.classRoom:id,name,section',
                    'exam.subject:id,name',
                ])
                ->where('student_id', (int) $student->id)
                ->where('session', $selectedSession)
                ->whereHas('exam', fn ($query) => $query->where('session', $selectedSession))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            $groupedResults = $this->groupMarksResults($marks);
        }

        return view('modules.student.results', [
            'student' => $student,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'sessionClassName' => $this->studentResultService->sessionClassNameForStudent((int) $student->id, $selectedSession),
            'groupedResults' => $groupedResults,
            'message' => null,
        ]);
    }

    private function groupLegacyResults(Collection $results, bool $usesGradeSystem): Collection
    {
        return $results
            ->groupBy(fn (StudentResult $row): string => (string) $row->exam_name)
            ->map(function (Collection $items) use ($usesGradeSystem): array {
                $rows = $items->map(function (StudentResult $result) use ($usesGradeSystem): array {
                    $grade = $usesGradeSystem
                        ? $this->assessmentModeService->normalizeGrade($result->grade)
                        : null;
                    $total = $usesGradeSystem ? null : (int) $result->total_marks;
                    $obtained = $usesGradeSystem ? null : (int) $result->obtained_marks;
                    $percentage = $usesGradeSystem
                        ? null
                        : ($total > 0 ? round(($obtained / $total) * 100, 2) : 0.0);

                    return [
                        'subject' => (string) ($result->subject?->name ?? 'Subject'),
                        'class_name' => trim((string) ($result->classRoom?->name ?? '').' '.(string) ($result->classRoom?->section ?? '')) ?: null,
                        'total_marks' => $total,
                        'obtained_marks' => $obtained,
                        'percentage' => $percentage,
                        'grade' => $usesGradeSystem ? $grade : $this->grade((float) $percentage),
                        'grade_label' => $usesGradeSystem ? $this->assessmentModeService->gradeLabel($grade) : null,
                        'result_date' => optional($result->result_date)?->format('Y-m-d'),
                    ];
                })->values();

                return [
                    'uses_grade_system' => $usesGradeSystem,
                    'class_name' => $rows->pluck('class_name')->filter()->first(),
                    'rows' => $rows,
                    'summary' => $this->summaryFromRows($rows, $usesGradeSystem),
                ];
            });
    }

    private function groupMarksResults(Collection $marks): Collection
    {
        return $marks
            ->groupBy(function (Mark $mark): string {
                $examType = $mark->exam?->exam_type;
                $examTypeValue = $examType instanceof ExamType ? $examType->value : (string) $examType;
                $examLabel = $examType instanceof ExamType ? $examType->label() : $this->examTypeLabel($examTypeValue);
                $session = (string) ($mark->session ?: $mark->exam?->session ?: 'Session');

                $parts = array_values(array_filter([$examLabel, $session], static fn ($value): bool => $value !== ''));

                return implode(' | ', $parts);
            })
            ->map(function (Collection $items): array {
                $first = $items->first();
                $examType = $first?->exam?->exam_type;
                $examTypeValue = $examType instanceof ExamType ? $examType->value : (string) $examType;
                $session = (string) ($first?->session ?: $first?->exam?->session ?: '');
                $classId = (int) ($first?->exam?->class_id ?? 0);
                $usesGradeSystem = $session !== '' && $examTypeValue !== ''
                    ? $this->markingModeService->resolveMarkingModeForExamContext($classId, $session, $examTypeValue) === AssessmentMarkingModeService::MODE_GRADE
                    : false;

                $rows = $items->map(function (Mark $mark) use ($usesGradeSystem): array {
                    $grade = $usesGradeSystem
                        ? $this->assessmentModeService->normalizeGrade($mark->grade)
                        : null;
                    $total = $usesGradeSystem ? null : (int) ($mark->total_marks ?: $mark->exam?->total_marks ?: 0);
                    $obtained = $usesGradeSystem ? null : (int) $mark->obtained_marks;
                    $percentage = $usesGradeSystem
                        ? null
                        : ($total > 0 ? round(($obtained / $total) * 100, 2) : 0.0);

                    return [
                        'subject' => (string) ($mark->exam?->subject?->name ?? 'Subject'),
                        'class_name' => trim((string) ($mark->exam?->classRoom?->name ?? '').' '.(string) ($mark->exam?->classRoom?->section ?? '')) ?: null,
                        'total_marks' => $total,
                        'obtained_marks' => $obtained,
                        'percentage' => $percentage,
                        'grade' => $usesGradeSystem ? $grade : $this->grade((float) $percentage),
                        'grade_label' => $usesGradeSystem ? $this->assessmentModeService->gradeLabel($grade) : null,
                        'result_date' => optional($mark->created_at)?->format('Y-m-d'),
                    ];
                })->values();

                return [
                    'uses_grade_system' => $usesGradeSystem,
                    'class_name' => $rows->pluck('class_name')->filter()->first(),
                    'rows' => $rows,
                    'summary' => $this->summaryFromRows($rows, $usesGradeSystem),
                ];
            });
    }

    private function summaryFromRows(Collection $rows, bool $usesGradeSystem): array
    {
        if ($usesGradeSystem) {
            $dominantGrade = $this->assessmentModeService->dominantGrade($rows->pluck('grade')->all());

            return [
                'total_marks' => null,
                'obtained_marks' => null,
                'percentage' => null,
                'grade' => $dominantGrade,
                'grade_label' => $this->assessmentModeService->gradeLabel($dominantGrade),
                'overall_performance' => $this->assessmentModeService->overallPerformanceLabel($rows->pluck('grade')->all()),
            ];
        }

        $totalMarks = (int) $rows->sum('total_marks');
        $obtainedMarks = (int) $rows->sum('obtained_marks');
        $overall = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0.0;

        return [
            'total_marks' => $totalMarks,
            'obtained_marks' => $obtainedMarks,
            'percentage' => $overall,
            'grade' => $this->grade($overall),
            'grade_label' => null,
            'overall_performance' => null,
        ];
    }

    private function examTypeLabel(string $value): string
    {
        $type = ExamType::tryFrom($value);
        if ($type) {
            return $type->label();
        }

        return str_replace('_', ' ', ucfirst($value));
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
