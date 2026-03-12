<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionAnalyzerController extends Controller
{
    private const PASS_PERCENTAGE = 60.0;

    public function index(Request $request): View
    {
        $user = $request->user();
        $isPrincipal = $user?->hasRole('Principal') ?? false;
        $isTeacher = $user?->hasRole('Teacher') ?? false;

        if (! $isPrincipal && ! $isTeacher) {
            abort(403, 'You are not authorized to access the promotion analyzer.');
        }

        $teacher = null;
        if ($isTeacher) {
            $teacher = Teacher::query()->where('user_id', (int) $user->id)->first();
            if (! $teacher) {
                abort(403, 'Teacher profile not found.');
            }
        }

        $classes = $this->classesForUser($isPrincipal, $teacher?->id);
        if (! $isPrincipal && $classes->isEmpty()) {
            abort(403, 'Only class teachers can access the promotion analyzer.');
        }

        $sessions = $this->availableSessions();
        $selectedSession = $request->filled('session') && in_array((string) $request->input('session'), $sessions, true)
            ? (string) $request->input('session')
            : ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        if ($selectedClassId !== null && ! $classes->contains('id', $selectedClassId)) {
            abort(403, 'You are not allowed to analyze this class.');
        }

        if ($selectedClassId === null && $classes->isNotEmpty()) {
            $selectedClassId = (int) $classes->first()->id;
        }

        $selectedClass = $selectedClassId !== null
            ? $classes->firstWhere('id', $selectedClassId)
            : null;

        if (
            $selectedClass !== null
            && ! $isPrincipal
            && $isTeacher
            && (int) ($teacher?->id ?? 0) !== (int) ($selectedClass->class_teacher_id ?? 0)
        ) {
            abort(403, 'You can access analyzer only for classes where you are class teacher.');
        }

        $analysis = $selectedClassId !== null
            ? $this->analyze((int) $selectedClassId, $selectedSession)
            : $this->emptyAnalysis();

        return view('modules.results.promotion-analyzer.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'selectedClassId' => $selectedClassId,
            'selectedClassLabel' => $selectedClass ? trim($selectedClass->name.' '.($selectedClass->section ?? '')) : null,
            'analysis' => $analysis,
        ]);
    }

    private function classesForUser(bool $isPrincipal, ?int $teacherId)
    {
        $query = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section');

        if (! $isPrincipal && $teacherId !== null) {
            $query->where('class_teacher_id', (int) $teacherId);
        }

        return $query->get(['id', 'name', 'section', 'class_teacher_id']);
    }

    private function availableSessions(): array
    {
        $sessions = Exam::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();

        if ($sessions !== []) {
            return $sessions;
        }

        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        $fallback = [];
        for ($year = $startYear - 1; $year <= $startYear + 3; $year++) {
            $fallback[] = $year.'-'.($year + 1);
        }

        return array_reverse($fallback);
    }

    private function emptyAnalysis(): array
    {
        return [
            'summary' => [
                'students' => 0,
                'promote' => 0,
                'conditional' => 0,
                'repeat' => 0,
            ],
            'has_final_term_results' => false,
            'rows' => [],
            'chart' => [
                'labels' => ['Pre-Medical', 'Pre-Engineering', 'ICS', 'Undetermined'],
                'values' => [0, 0, 0, 0],
            ],
        ];
    }

    private function analyze(int $classId, string $session): array
    {
        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'name', 'student_id', 'class_id']);

        $studentIds = $students->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $finalExams = Exam::query()
            ->with('subject:id,name')
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('exam_type', 'final_term')
            ->orderBy('subject_id')
            ->get(['id', 'subject_id', 'total_marks']);

        if ($studentIds === [] || $finalExams->isEmpty()) {
            return $this->emptyAnalysis();
        }

        $finalExamIds = $finalExams->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $marks = Mark::query()
            ->whereIn('exam_id', $finalExamIds)
            ->whereIn('student_id', $studentIds)
            ->get(['id', 'exam_id', 'student_id', 'obtained_marks', 'total_marks']);

        $marksByStudent = $marks
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->keyBy('exam_id'));

        $streamDistribution = [
            'Pre-Medical' => 0,
            'Pre-Engineering' => 0,
            'ICS' => 0,
            'Undetermined' => 0,
        ];

        $rows = $students->map(function (Student $student) use (
            $marksByStudent,
            $finalExams,
            &$streamDistribution
        ): array {
            $studentExamMarks = $marksByStudent->get((int) $student->id, collect());

            $obtainedTotal = 0.0;
            $totalMarks = 0.0;
            $failedSubjects = [];
            $subjectPercentagesByName = [];
            $subjectRows = [];

            foreach ($finalExams as $exam) {
                $subjectName = (string) ($exam->subject?->name ?: ('Subject #'.$exam->subject_id));
                $subjectKey = $this->normalizeSubjectName($subjectName);
                $mark = $studentExamMarks->get((int) $exam->id);

                $subjectTotal = (float) ($mark?->total_marks ?: $exam->total_marks ?: 0);
                $subjectObtained = (float) ($mark?->obtained_marks ?? 0);
                $subjectPercentage = $subjectTotal > 0
                    ? round(($subjectObtained / $subjectTotal) * 100, 2)
                    : 0.0;

                $obtainedTotal += $subjectObtained;
                $totalMarks += $subjectTotal;
                $subjectPercentagesByName[$subjectKey] = $subjectPercentage;
                $subjectRows[] = [
                    'subject' => $subjectName,
                    'percentage' => $subjectPercentage,
                ];

                if ($subjectPercentage < self::PASS_PERCENTAGE) {
                    $failedSubjects[] = $subjectName;
                }
            }

            $averagePercentage = $totalMarks > 0
                ? round(($obtainedTotal / $totalMarks) * 100, 2)
                : 0.0;
            $failedSubjects = array_values(array_unique($failedSubjects));
            $failedSubjectsCount = count($failedSubjects);

            $promotionRecommendation = match (true) {
                $failedSubjectsCount === 0 => 'Promote',
                $failedSubjectsCount === 1 => 'Conditional',
                default => 'Repeat',
            };

            $streamScores = $this->streamScores($subjectPercentagesByName);
            $streamRecommendation = $this->topStream($streamScores);
            $streamDistribution[$streamRecommendation] = ($streamDistribution[$streamRecommendation] ?? 0) + 1;

            return [
                'student_id' => (int) $student->id,
                'student_name' => (string) $student->name,
                'student_ref' => (string) $student->student_id,
                'average_percentage' => $averagePercentage,
                'failed_subjects_count' => $failedSubjectsCount,
                'promotion_recommendation' => $promotionRecommendation,
                'stream_recommendation' => $streamRecommendation,
                'detail' => [
                    'student_name' => (string) $student->name,
                    'student_ref' => (string) $student->student_id,
                    'average_percentage' => $averagePercentage,
                    'failed_subjects_count' => $failedSubjectsCount,
                    'failed_subjects' => $failedSubjects,
                    'promotion_recommendation' => $promotionRecommendation,
                    'promotion_reason' => $this->promotionReason($failedSubjectsCount),
                    'stream_recommendation' => $streamRecommendation,
                    'stream_scores' => $streamScores,
                    'subject_percentages' => $subjectRows,
                ],
            ];
        })->values();

        $promoteCount = $rows->where('promotion_recommendation', 'Promote')->count();
        $conditionalCount = $rows->where('promotion_recommendation', 'Conditional')->count();
        $repeatCount = $rows->where('promotion_recommendation', 'Repeat')->count();

        return [
            'summary' => [
                'students' => $rows->count(),
                'promote' => $promoteCount,
                'conditional' => $conditionalCount,
                'repeat' => $repeatCount,
            ],
            'has_final_term_results' => true,
            'rows' => $rows->all(),
            'chart' => [
                'labels' => array_keys($streamDistribution),
                'values' => array_values($streamDistribution),
            ],
        ];
    }

    private function streamScores(array $subjectPercentagesByName): array
    {
        $biology = $this->subjectScore($subjectPercentagesByName, ['biology']);
        $chemistry = $this->subjectScore($subjectPercentagesByName, ['chemistry']);
        $physics = $this->subjectScore($subjectPercentagesByName, ['physics']);
        $math = $this->subjectScore($subjectPercentagesByName, ['math', 'mathematics', 'generalmathematics']);
        $computer = $this->subjectScore($subjectPercentagesByName, ['computer', 'computerscience']);

        return [
            'Pre-Medical' => round(($biology * 0.4) + ($chemistry * 0.3) + ($physics * 0.3), 2),
            'Pre-Engineering' => round(($math * 0.4) + ($physics * 0.3) + ($chemistry * 0.3), 2),
            'ICS' => round(($computer * 0.5) + ($math * 0.3) + ($physics * 0.2), 2),
        ];
    }

    private function subjectScore(array $subjectPercentagesByName, array $aliases): float
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $subjectPercentagesByName)) {
                return (float) $subjectPercentagesByName[$alias];
            }
        }

        return 0.0;
    }

    private function topStream(array $streamScores): string
    {
        if ($streamScores === []) {
            return 'Undetermined';
        }

        $maxScore = max($streamScores);
        if ((float) $maxScore <= 0) {
            return 'Undetermined';
        }

        foreach ($streamScores as $stream => $score) {
            if ((float) $score === (float) $maxScore) {
                return (string) $stream;
            }
        }

        return 'Undetermined';
    }

    private function normalizeSubjectName(string $name): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]/i', '', $name));
    }

    private function promotionReason(int $failedSubjectsCount): string
    {
        return match (true) {
            $failedSubjectsCount === 0 => '0 failed subjects, student is eligible for promotion.',
            $failedSubjectsCount === 1 => '1 failed subject, student qualifies for conditional promotion.',
            default => '2 or more failed subjects, student should repeat the class.',
        };
    }
}
