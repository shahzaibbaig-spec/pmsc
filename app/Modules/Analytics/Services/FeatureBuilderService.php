<?php

namespace App\Modules\Analytics\Services;

use App\Models\Attendance;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentPerformanceFeature;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FeatureBuilderService
{
    public function buildForSession(string $session): array
    {
        [$startDate, $endDate] = $this->sessionBounds($session);

        $studentIds = Student::query()
            ->where(function ($query): void {
                $query->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return [
                'session' => $session,
                'date_range' => [$startDate, $endDate],
                'students_count' => 0,
                'features_upserted' => 0,
                'predictions_upserted' => 0,
            ];
        }

        $totalDaysInSession = Attendance::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->distinct('date')
            ->count('date');

        $attendanceStats = Attendance::query()
            ->select('student_id')
            ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days")
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $examAverages = Mark::query()
            ->join('exams', 'exams.id', '=', 'marks.exam_id')
            ->select('marks.student_id', 'exams.exam_type')
            ->selectRaw('AVG((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as avg_percentage')
            ->where('marks.session', $session)
            ->whereIn('marks.student_id', $studentIds)
            ->groupBy('marks.student_id', 'exams.exam_type')
            ->get()
            ->groupBy('student_id');

        $chronologicalScores = Mark::query()
            ->join('exams', 'exams.id', '=', 'marks.exam_id')
            ->select('marks.student_id')
            ->selectRaw('((marks.obtained_marks * 100.0) / NULLIF(marks.total_marks, 0)) as score_percentage')
            ->where('marks.session', $session)
            ->whereIn('marks.student_id', $studentIds)
            ->orderBy('marks.student_id')
            ->orderBy('exams.created_at')
            ->orderBy('marks.id')
            ->get()
            ->groupBy('student_id');

        $featureRows = [];
        $now = now();

        foreach ($studentIds as $studentId) {
            $attendanceRow = $attendanceStats->get($studentId);
            $presentDays = (int) ($attendanceRow?->present_days ?? 0);
            $attendanceRate = $totalDaysInSession > 0
                ? round(($presentDays / $totalDaysInSession) * 100, 2)
                : 0.0;

            $examRows = $examAverages->get($studentId, collect());
            $avgClassTest = $this->examAverage($examRows, 'class_test');
            $avgBimonthly = $this->examAverage($examRows, 'bimonthly_test');
            $avgFirstTerm = $this->examAverage($examRows, 'first_term');

            $scores = $chronologicalScores->get($studentId, collect())
                ->pluck('score_percentage')
                ->map(fn ($score): float => round((float) $score, 2))
                ->values()
                ->all();

            $trendSlope = round($this->trendSlope($scores), 4);
            $lastAssessmentScore = ! empty($scores)
                ? round((float) end($scores), 2)
                : null;

            $featureRows[] = [
                'session' => $session,
                'student_id' => (int) $studentId,
                'attendance_rate' => $attendanceRate,
                'avg_class_test' => $avgClassTest,
                'avg_bimonthly' => $avgBimonthly,
                'avg_first_term' => $avgFirstTerm,
                'trend_slope' => $trendSlope,
                'last_assessment_score' => $lastAssessmentScore,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        StudentPerformanceFeature::query()->upsert(
            $featureRows,
            ['session', 'student_id'],
            [
                'attendance_rate',
                'avg_class_test',
                'avg_bimonthly',
                'avg_first_term',
                'trend_slope',
                'last_assessment_score',
                'updated_at',
            ]
        );

        return [
            'session' => $session,
            'date_range' => [$startDate, $endDate],
            'students_count' => $studentIds->count(),
            'features_upserted' => count($featureRows),
            'predictions_upserted' => 0,
        ];
    }

    private function sessionBounds(string $session): array
    {
        if (preg_match('/^(\d{4})-\d{4}$/', $session, $matches) === 1) {
            $startYear = (int) $matches[1];
        } else {
            $now = now();
            $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        }

        $start = Carbon::create($startYear, 7, 1)->startOfDay();
        $end = Carbon::create($startYear + 1, 6, 30)->endOfDay();
        if (now()->between($start, $end)) {
            $end = now()->endOfDay();
        } elseif (now()->lessThan($start)) {
            $end = $start->copy()->endOfDay();
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    private function examAverage(Collection $examRows, string $examType): ?float
    {
        /** @var object|null $row */
        $row = $examRows->firstWhere('exam_type', $examType);
        if (! $row) {
            return null;
        }

        return round((float) $row->avg_percentage, 2);
    }

    private function trendSlope(array $scores): float
    {
        $n = count($scores);
        if ($n < 2) {
            return 0.0;
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;

        foreach ($scores as $index => $score) {
            $x = (float) ($index + 1);
            $y = (float) $score;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if (abs($denominator) < 0.000001) {
            return 0.0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }
}
