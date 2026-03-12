<?php

namespace App\Modules\Analytics\Services;

use App\Models\Attendance;
use App\Models\Mark;
use App\Models\StudentPerformanceFeature;
use App\Models\StudentRiskPrediction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PredictionService
{
    private const WEIGHT_ATTENDANCE = 0.20;
    private const WEIGHT_RECENT = 0.35;
    private const WEIGHT_AVG = 0.40;
    private const WEIGHT_TREND = 0.05;

    public function generate(string $session, string $targetExam, ?int $classId = null): array
    {
        [$startDate, $endDate] = $this->sessionBounds($session);

        $featureQuery = StudentPerformanceFeature::query()
            ->with('student:id,class_id')
            ->where('session', $session)
            ->when($classId !== null, fn (Builder $q) => $q->whereHas('student', fn ($sq) => $sq->where('class_id', $classId)));

        $features = $featureQuery->get();
        if ($features->isEmpty()) {
            return [
                'session' => $session,
                'target_exam' => $targetExam,
                'class_id' => $classId,
                'predicted_count' => 0,
                'low_count' => 0,
                'medium_count' => 0,
                'high_count' => 0,
                'average_predicted' => null,
            ];
        }

        $studentIds = $features->pluck('student_id')->filter()->unique()->values();

        $attendanceCounts = Attendance::query()
            ->select('student_id')
            ->selectRaw('COUNT(*) as total_days')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $markCounts = Mark::query()
            ->select('student_id')
            ->selectRaw('COUNT(*) as marks_count')
            ->where('session', $session)
            ->whereIn('student_id', $studentIds)
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $rows = [];
        $predictedPercentages = [];
        $riskCounts = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
        ];
        $now = now();

        foreach ($features as $feature) {
            $avgScore = $this->averageScore($feature, $targetExam);
            $recentScore = $feature->last_assessment_score !== null
                ? (float) $feature->last_assessment_score
                : ($avgScore ?? 0.0);

            $attendanceRatePercent = (float) $feature->attendance_rate;
            $trend = (float) $feature->trend_slope;

            $predicted = $this->clamp(
                (self::WEIGHT_ATTENDANCE * $attendanceRatePercent)
                + (self::WEIGHT_RECENT * $recentScore)
                + (self::WEIGHT_AVG * ($avgScore ?? 0.0))
                + (self::WEIGHT_TREND * $trend),
                0.0,
                100.0
            );

            $riskLevel = $predicted < 60.0
                ? 'high'
                : ($predicted < 70.0 ? 'medium' : 'low');

            $attendanceRecords = (int) ($attendanceCounts->get($feature->student_id)?->total_days ?? 0);
            $examRecords = (int) ($markCounts->get($feature->student_id)?->marks_count ?? 0);
            $dataCompleteness = $this->dataCompleteness($feature, $attendanceRecords, $examRecords);
            $confidence = round($this->clamp(0.35 + (0.60 * $dataCompleteness), 0.35, 0.95), 2);

            $explanation = [
                'attendance_rate' => round($attendanceRatePercent / 100, 2),
                'recent_score' => round($recentScore, 2),
                'trend' => round($trend, 2),
                'average_score' => $avgScore !== null ? round($avgScore, 2) : null,
                'weights' => [
                    'attendance' => self::WEIGHT_ATTENDANCE,
                    'recent' => self::WEIGHT_RECENT,
                    'average' => self::WEIGHT_AVG,
                    'trend' => self::WEIGHT_TREND,
                ],
                'notes' => $this->notes($attendanceRatePercent, $recentScore, $trend, $avgScore),
            ];

            $rows[] = [
                'session' => $session,
                'student_id' => (int) $feature->student_id,
                'target_exam' => $targetExam,
                'predicted_percentage' => round($predicted, 2),
                'risk_level' => $riskLevel,
                'confidence' => $confidence,
                'explanation' => $explanation,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $predictedPercentages[] = $predicted;
            $riskCounts[$riskLevel]++;
        }

        StudentRiskPrediction::query()->upsert(
            $rows,
            ['session', 'student_id', 'target_exam'],
            ['predicted_percentage', 'risk_level', 'confidence', 'explanation', 'updated_at']
        );

        $averagePredicted = ! empty($predictedPercentages)
            ? round(array_sum($predictedPercentages) / count($predictedPercentages), 2)
            : null;

        return [
            'session' => $session,
            'target_exam' => $targetExam,
            'class_id' => $classId,
            'predicted_count' => count($rows),
            'low_count' => $riskCounts['low'],
            'medium_count' => $riskCounts['medium'],
            'high_count' => $riskCounts['high'],
            'average_predicted' => $averagePredicted,
        ];
    }

    private function averageScore(StudentPerformanceFeature $feature, string $targetExam): ?float
    {
        $values = $targetExam === 'first_term'
            ? [$feature->avg_class_test, $feature->avg_bimonthly]
            : [$feature->avg_class_test, $feature->avg_bimonthly, $feature->avg_first_term];

        $valid = array_values(array_filter($values, fn ($value): bool => $value !== null));
        if (empty($valid)) {
            return null;
        }

        return (float) (array_sum($valid) / count($valid));
    }

    private function dataCompleteness(StudentPerformanceFeature $feature, int $attendanceRecords, int $examRecords): float
    {
        $featureFields = [
            $feature->avg_class_test,
            $feature->avg_bimonthly,
            $feature->avg_first_term,
            $feature->last_assessment_score,
        ];

        $featureCoverage = count(array_filter($featureFields, fn ($value): bool => $value !== null)) / 4.0;
        $attendanceCoverage = min($attendanceRecords / 120.0, 1.0);
        $examCoverage = min($examRecords / 12.0, 1.0);

        return $this->clamp(
            (0.50 * $featureCoverage) + (0.25 * $attendanceCoverage) + (0.25 * $examCoverage),
            0.0,
            1.0
        );
    }

    private function notes(float $attendanceRatePercent, float $recentScore, float $trend, ?float $avgScore): array
    {
        $notes = [];

        if ($attendanceRatePercent < 75.0) {
            $notes[] = 'Low attendance is increasing risk';
        }

        if ($recentScore < 60.0) {
            $notes[] = 'Recent scores below passing';
        }

        if ($trend < -0.2) {
            $notes[] = 'Downward trend detected in assessments';
        } elseif ($trend > 0.2) {
            $notes[] = 'Positive trend is improving outlook';
        }

        if (($avgScore ?? 0.0) >= 70.0) {
            $notes[] = 'Average score suggests stable core performance';
        }

        if (empty($notes)) {
            $notes[] = 'Performance indicators are within expected range';
        }

        return $notes;
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

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
