<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentResult;
use App\Services\ClassAssessmentModeService;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentResultController extends Controller
{
    public function __construct(private readonly ClassAssessmentModeService $assessmentModeService)
    {
    }

    public function index(): View
    {
        $user = auth()->user();
        $student = $user ? $this->resolveStudentForUser((string) $user->name, (string) $user->email) : null;

        if (! $student) {
            return view('modules.student.results', [
                'student' => null,
                'groupedResults' => collect(),
                'message' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        $usesGradeSystem = $this->assessmentModeService->classUsesGradeSystem($student->classRoom);

        $legacyResults = StudentResult::query()
            ->with('subject:id,name')
            ->where('student_id', (int) $student->id)
            ->orderByDesc('result_date')
            ->orderByDesc('id')
            ->get();

        if ($legacyResults->isNotEmpty()) {
            $groupedResults = $this->groupLegacyResults($legacyResults, $usesGradeSystem);
        } else {
            $marks = Mark::query()
                ->with([
                    'exam:id,subject_id,exam_type,session,total_marks,created_at',
                    'exam.subject:id,name',
                ])
                ->where('student_id', (int) $student->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            $groupedResults = $this->groupMarksResults($marks, $usesGradeSystem);
        }

        return view('modules.student.results', [
            'student' => $student,
            'groupedResults' => $groupedResults,
            'message' => null,
        ]);
    }

    private function resolveStudentForUser(string $userName, string $email): ?Student
    {
        $normalizedName = mb_strtolower(trim($userName));
        $emailLocal = mb_strtolower(trim(Str::before($email, '@')));

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
                    'rows' => $rows,
                    'summary' => $this->summaryFromRows($rows, $usesGradeSystem),
                ];
            });
    }

    private function groupMarksResults(Collection $marks, bool $usesGradeSystem): Collection
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
            ->map(function (Collection $items) use ($usesGradeSystem): array {
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
