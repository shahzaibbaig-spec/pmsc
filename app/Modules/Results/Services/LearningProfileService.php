<?php

namespace App\Modules\Results\Services;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\ReportComment;
use App\Models\Student;
use App\Models\StudentLearningProfile;
use App\Modules\Exams\Enums\ExamType;
use Carbon\Carbon;

class LearningProfileService
{
    private const PASS_PERCENTAGE = 60.0;

    public function generateProfilesForClass(int $classId, string $session): array
    {
        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'name', 'student_id', 'class_id']);

        if ($students->isEmpty()) {
            return [
                'students_count' => 0,
                'profiles_generated' => 0,
            ];
        }

        $studentIds = $students->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $exams = Exam::query()
            ->with('subject:id,name')
            ->where('class_id', $classId)
            ->where('session', $session)
            ->get(['id', 'subject_id', 'exam_type', 'total_marks', 'class_id', 'session']);

        $examLookup = $exams->keyBy('id');
        $examIds = $exams->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $marks = Mark::query()
            ->when($examIds !== [], fn ($query) => $query->whereIn('exam_id', $examIds), fn ($query) => $query->whereRaw('1=0'))
            ->whereIn('student_id', $studentIds)
            ->get(['id', 'student_id', 'exam_id', 'obtained_marks', 'total_marks']);

        $marksByStudent = $marks->groupBy('student_id');
        $attendanceMap = $this->attendancePercentageMap($classId, $session, $studentIds);

        $profilesGenerated = 0;
        foreach ($students as $student) {
            $studentMarks = $marksByStudent->get((int) $student->id, collect());
            $metrics = $this->buildStudentMetrics($studentMarks, $examLookup, (float) ($attendanceMap[(int) $student->id] ?? 0.0));

            StudentLearningProfile::query()->updateOrCreate(
                [
                    'student_id' => (int) $student->id,
                    'session' => $session,
                ],
                [
                    'strengths' => $metrics['strengths'],
                    'support_areas' => $metrics['support_areas'],
                    'best_aptitude' => $metrics['best_aptitude'],
                    'learning_pattern' => $metrics['learning_pattern'],
                    'attendance_percentage' => $metrics['attendance_percentage'],
                    'overall_average' => $metrics['overall_average'],
                    'subject_scores' => $metrics['subject_scores'],
                ]
            );

            $profilesGenerated++;
        }

        return [
            'students_count' => $students->count(),
            'profiles_generated' => $profilesGenerated,
        ];
    }

    public function tableRowsForClass(int $classId, string $session, string $examType): array
    {
        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'name', 'student_id']);

        if ($students->isEmpty()) {
            return [];
        }

        $studentIds = $students->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $profiles = StudentLearningProfile::query()
            ->where('session', $session)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $comments = ReportComment::query()
            ->where('session', $session)
            ->where('exam_type', $examType)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        return $students->map(function (Student $student) use ($profiles, $comments): array {
            $profile = $profiles->get((int) $student->id);
            $comment = $comments->get((int) $student->id);

            $subjectScores = is_array($profile?->subject_scores) ? $profile->subject_scores : [];
            $meta = is_array($subjectScores['meta'] ?? null) ? $subjectScores['meta'] : [];

            return [
                'student_id' => (int) $student->id,
                'student_name' => (string) $student->name,
                'student_ref' => (string) $student->student_id,
                'average' => (float) ($profile?->overall_average ?? 0.0),
                'best_aptitude' => (string) ($profile?->best_aptitude ?: 'Undetermined'),
                'learning_pattern' => (string) ($profile?->learning_pattern ?: 'Profile not generated yet.'),
                'comment_status' => $this->commentStatus($comment),
                'strengths' => (string) ($profile?->strengths ?: 'Not available'),
                'support_areas' => (string) ($profile?->support_areas ?: 'Not available'),
                'attendance_percentage' => (float) ($profile?->attendance_percentage ?? 0.0),
                'trend' => (string) ($meta['trend'] ?? 'stable'),
                'consistency' => (string) ($meta['consistency'] ?? 'consistent'),
                'failed_subjects_count' => (int) ($meta['failed_subjects_count'] ?? 0),
                'auto_comment' => (string) ($comment?->auto_comment ?: ''),
                'final_comment' => (string) ($comment?->final_comment ?: ($comment?->auto_comment ?: '')),
                'is_edited' => (bool) ($comment?->is_edited ?? false),
            ];
        })->values()->all();
    }

    private function buildStudentMetrics($studentMarks, $examLookup, float $attendancePercentage): array
    {
        $subjectBuckets = [];
        $subjectNameByKey = [];
        $examTypeBuckets = [];
        $allPercentages = [];

        foreach ($studentMarks as $mark) {
            $exam = $examLookup->get((int) $mark->exam_id);
            if (! $exam) {
                continue;
            }

            $subjectName = (string) ($exam->subject?->name ?: ('Subject #'.$exam->subject_id));
            $subjectKey = $this->normalizeSubjectName($subjectName);
            $percentage = $this->markPercentage($mark->obtained_marks, ($mark->total_marks ?: $exam->total_marks));

            $subjectBuckets[$subjectKey] = $subjectBuckets[$subjectKey] ?? [];
            $subjectBuckets[$subjectKey][] = $percentage;
            $subjectNameByKey[$subjectKey] = $subjectName;

            $examTypeKey = $this->examTypeValue($exam->exam_type);
            if ($examTypeKey === '') {
                continue;
            }
            $examTypeBuckets[$examTypeKey] = $examTypeBuckets[$examTypeKey] ?? [];
            $examTypeBuckets[$examTypeKey][] = $percentage;

            $allPercentages[] = $percentage;
        }

        $subjectAverages = [];
        foreach ($subjectBuckets as $subjectKey => $values) {
            if ($values === []) {
                continue;
            }
            $subjectAverages[$subjectKey] = round(array_sum($values) / count($values), 2);
        }

        $overallAverage = $allPercentages === []
            ? 0.0
            : round(array_sum($allPercentages) / count($allPercentages), 2);

        $failedSubjects = collect($subjectAverages)
            ->filter(fn (float $score): bool => $score < self::PASS_PERCENTAGE)
            ->keys()
            ->map(fn (string $key): string => (string) ($subjectNameByKey[$key] ?? $key))
            ->values()
            ->all();
        $failedSubjectsCount = count($failedSubjects);

        $strengths = $this->strengthSubjects($subjectAverages, $subjectNameByKey);
        $supportAreas = $this->supportSubjects($subjectAverages, $subjectNameByKey);

        $trend = $this->trendLabel($examTypeBuckets);
        $consistency = $this->consistencyLabel($allPercentages);
        $aptitudeScores = $this->aptitudeScores($subjectAverages);
        $bestAptitude = $this->bestAptitude($aptitudeScores);

        $learningPattern = $this->learningPattern(
            $attendancePercentage,
            $trend,
            $consistency,
            $failedSubjectsCount
        );

        $subjectScoreRows = [];
        foreach ($subjectAverages as $key => $score) {
            $subjectScoreRows[(string) ($subjectNameByKey[$key] ?? $key)] = $score;
        }
        ksort($subjectScoreRows);

        return [
            'strengths' => $strengths !== [] ? implode(', ', $strengths) : null,
            'support_areas' => $supportAreas !== [] ? implode(', ', $supportAreas) : null,
            'best_aptitude' => $bestAptitude,
            'learning_pattern' => $learningPattern,
            'attendance_percentage' => round($attendancePercentage, 2),
            'overall_average' => $overallAverage,
            'subject_scores' => [
                'subjects' => $subjectScoreRows,
                'meta' => [
                    'trend' => $trend,
                    'consistency' => $consistency,
                    'failed_subjects_count' => $failedSubjectsCount,
                    'failed_subjects' => $failedSubjects,
                    'exam_type_averages' => $this->examTypeAverages($examTypeBuckets),
                    'aptitude_scores' => $aptitudeScores,
                ],
            ],
        ];
    }

    private function attendancePercentageMap(int $classId, string $session, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        [$startDate, $endDate] = $this->sessionDateRange($session);
        if (! $startDate || ! $endDate) {
            return [];
        }

        $rows = Attendance::query()
            ->where('class_id', $classId)
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['student_id', 'status']);

        $grouped = $rows->groupBy('student_id');
        $map = [];
        foreach ($studentIds as $studentId) {
            $studentRows = $grouped->get($studentId, collect());
            $total = $studentRows->count();
            $present = $studentRows
                ->filter(function ($row): bool {
                    $status = strtolower((string) $row->status);

                    return in_array($status, ['present', 'p'], true);
                })
                ->count();

            $map[$studentId] = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;
        }

        return $map;
    }

    private function markPercentage($obtainedMarks, $totalMarks): float
    {
        $total = (float) $totalMarks;
        $obtained = (float) $obtainedMarks;

        return $total > 0 ? round(($obtained / $total) * 100, 2) : 0.0;
    }

    private function strengthSubjects(array $subjectAverages, array $subjectNameByKey): array
    {
        $strong = collect($subjectAverages)
            ->filter(fn (float $score): bool => $score >= 75)
            ->sortByDesc(fn (float $score): float => $score)
            ->keys()
            ->map(fn (string $key): string => (string) ($subjectNameByKey[$key] ?? $key))
            ->take(3)
            ->values()
            ->all();

        if ($strong !== []) {
            return $strong;
        }

        return collect($subjectAverages)
            ->sortByDesc(fn (float $score): float => $score)
            ->keys()
            ->map(fn (string $key): string => (string) ($subjectNameByKey[$key] ?? $key))
            ->take(2)
            ->values()
            ->all();
    }

    private function supportSubjects(array $subjectAverages, array $subjectNameByKey): array
    {
        $support = collect($subjectAverages)
            ->filter(fn (float $score): bool => $score < self::PASS_PERCENTAGE)
            ->sortBy(fn (float $score): float => $score)
            ->keys()
            ->map(fn (string $key): string => (string) ($subjectNameByKey[$key] ?? $key))
            ->take(3)
            ->values()
            ->all();

        if ($support !== []) {
            return $support;
        }

        return collect($subjectAverages)
            ->sortBy(fn (float $score): float => $score)
            ->keys()
            ->map(fn (string $key): string => (string) ($subjectNameByKey[$key] ?? $key))
            ->take(2)
            ->values()
            ->all();
    }

    private function trendLabel(array $examTypeBuckets): string
    {
        $ordered = ['class_test', 'bimonthly_test', 'first_term', 'final_term'];
        $series = [];
        foreach ($ordered as $examType) {
            $values = $examTypeBuckets[$examType] ?? [];
            if ($values === []) {
                continue;
            }
            $series[] = round(array_sum($values) / count($values), 2);
        }

        if (count($series) < 2) {
            return 'stable';
        }

        $delta = $series[count($series) - 1] - $series[0];
        if ($delta >= 5.0) {
            return 'improving';
        }
        if ($delta <= -5.0) {
            return 'declining';
        }

        return 'stable';
    }

    private function consistencyLabel(array $percentages): string
    {
        if (count($percentages) < 2) {
            return 'consistent';
        }

        $average = array_sum($percentages) / count($percentages);
        $variance = 0.0;
        foreach ($percentages as $value) {
            $variance += ($value - $average) ** 2;
        }
        $variance /= count($percentages);
        $stdDev = sqrt($variance);

        return $stdDev <= 10.0 ? 'consistent' : 'inconsistent';
    }

    private function aptitudeScores(array $subjectAverages): array
    {
        $math = $this->subjectValue($subjectAverages, ['math', 'mathematics', 'generalmathematics']);
        $physics = $this->subjectValue($subjectAverages, ['physics']);
        $computer = $this->subjectValue($subjectAverages, ['computer', 'computerscience']);
        $biology = $this->subjectValue($subjectAverages, ['biology']);
        $chemistry = $this->subjectValue($subjectAverages, ['chemistry']);
        $english = $this->subjectValue($subjectAverages, ['english']);
        $urdu = $this->subjectValue($subjectAverages, ['urdu']);
        $sst = $this->subjectValue($subjectAverages, ['sst', 'socialstudies', 'socialscience']);
        $pakStudies = $this->subjectValue($subjectAverages, ['pakstudies', 'pakistanstudies', 'pakistanstudy']);
        $hg = $this->subjectValue($subjectAverages, ['hg', 'historygeography', 'history', 'geography']);

        return [
            'Analytical' => $this->weightedScore([
                ['score' => $math, 'weight' => 0.4],
                ['score' => $physics, 'weight' => 0.3],
                ['score' => $computer, 'weight' => 0.3],
            ]),
            'Science' => $this->weightedScore([
                ['score' => $biology, 'weight' => 0.4],
                ['score' => $chemistry, 'weight' => 0.3],
                ['score' => $physics, 'weight' => 0.3],
            ]),
            'Language' => $this->weightedScore([
                ['score' => $english, 'weight' => 0.5],
                ['score' => $urdu, 'weight' => 0.5],
            ]),
            'Technology' => $this->weightedScore([
                ['score' => $computer, 'weight' => 0.6],
                ['score' => $math, 'weight' => 0.4],
            ]),
            'Humanities' => $this->weightedScore([
                ['score' => $sst, 'weight' => 0.4],
                ['score' => $pakStudies, 'weight' => 0.35],
                ['score' => $hg, 'weight' => 0.25],
            ]),
        ];
    }

    private function bestAptitude(array $aptitudeScores): string
    {
        if ($aptitudeScores === []) {
            return 'Undetermined';
        }

        $maxScore = max($aptitudeScores);
        if ((float) $maxScore <= 0.0) {
            return 'Undetermined';
        }

        foreach ($aptitudeScores as $label => $score) {
            if ((float) $score === (float) $maxScore) {
                return (string) $label;
            }
        }

        return 'Undetermined';
    }

    private function weightedScore(array $rows): float
    {
        $weightedSum = 0.0;
        $weightSum = 0.0;
        foreach ($rows as $row) {
            $score = $row['score'] ?? null;
            $weight = (float) ($row['weight'] ?? 0);
            if ($score === null || $weight <= 0) {
                continue;
            }

            $weightedSum += ((float) $score) * $weight;
            $weightSum += $weight;
        }

        if ($weightSum <= 0.0) {
            return 0.0;
        }

        return round($weightedSum / $weightSum, 2);
    }

    private function subjectValue(array $subjectAverages, array $aliases): ?float
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $subjectAverages)) {
                return (float) $subjectAverages[$alias];
            }
        }

        return null;
    }

    private function learningPattern(float $attendance, string $trend, string $consistency, int $failedSubjectsCount): string
    {
        if ($failedSubjectsCount >= 2 || $trend === 'declining') {
            if ($attendance < 75) {
                return 'Needs targeted academic support with regular attendance follow-up and structured remediation in core subjects.';
            }

            return 'Requires focused reinforcement in low-performing subjects with close progress monitoring in upcoming assessments.';
        }

        if ($trend === 'improving') {
            if ($attendance >= 85) {
                return 'Shows a positive learning trajectory with strong classroom engagement and sustained improvement across assessments.';
            }

            return 'Demonstrates improving academic progress; better attendance consistency can further strengthen outcomes.';
        }

        if ($consistency === 'inconsistent') {
            return 'Demonstrates variable performance across assessments; regular revision planning and periodic feedback are recommended.';
        }

        if ($attendance >= 85) {
            return 'Maintains steady performance with strong attendance and dependable learning habits.';
        }

        return 'Maintains stable academic performance; improved attendance and structured practice can support higher achievement.';
    }

    private function examTypeAverages(array $examTypeBuckets): array
    {
        $order = ['class_test', 'bimonthly_test', 'first_term', 'final_term'];
        $rows = [];
        foreach ($order as $examType) {
            $values = $examTypeBuckets[$examType] ?? [];
            if ($values === []) {
                continue;
            }
            $rows[$examType] = round(array_sum($values) / count($values), 2);
        }

        return $rows;
    }

    private function normalizeSubjectName(string $name): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]/i', '', $name));
    }

    private function sessionDateRange(string $session): array
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $session, $matches)) {
            return [null, null];
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        if ($endYear !== ($startYear + 1)) {
            return [null, null];
        }

        return [
            Carbon::create($startYear, 7, 1)->startOfDay(),
            Carbon::create($endYear, 6, 30)->endOfDay(),
        ];
    }

    private function commentStatus(?ReportComment $comment): string
    {
        if (! $comment) {
            return 'Not Generated';
        }

        if ((bool) $comment->is_edited) {
            return 'Edited';
        }

        if ((string) ($comment->auto_comment ?? '') !== '') {
            return 'Auto';
        }

        return 'Draft';
    }

    private function examTypeValue(mixed $examType): string
    {
        if ($examType instanceof ExamType) {
            return $examType->value;
        }

        if (is_string($examType)) {
            return trim($examType);
        }

        if (is_scalar($examType)) {
            return (string) $examType;
        }

        return '';
    }
}
