<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class PrincipalAnalyticsExport implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(private readonly array $report)
    {
    }

    public function array(): array
    {
        $filters = $this->report['filters'] ?? [];
        $summary = $this->report['summary'] ?? [];
        $topPerformers = $this->report['top_performers'] ?? [];
        $weakStudents = $this->report['weak_students'] ?? [];
        $subjectPerformance = $this->report['subject_performance'] ?? [];
        $teacherPerformance = $this->report['teacher_performance'] ?? [];
        $attendanceTrend = $this->report['attendance_trend'] ?? ['labels' => [], 'values' => []];
        $feeDefaulterSummary = $this->report['fee_defaulter_summary'] ?? [];
        $classComparison = $this->report['class_comparison'] ?? [];

        $rows = [];

        $rows[] = ['Principal Analytics Report'];
        $rows[] = ['Generated At', (string) ($filters['generated_at'] ?? now()->toDateTimeString())];
        $rows[] = ['Session', (string) ($filters['session'] ?? '-')];
        $rows[] = ['Exam', (string) ($filters['exam_label'] ?? 'All Exams')];
        $rows[] = ['Class', (string) ($filters['class_name'] ?? 'All Classes')];
        $rows[] = [];

        $rows[] = ['KPI Summary'];
        $rows[] = ['Metric', 'Value'];
        $rows[] = ['Total Students', (string) ($summary['total_students'] ?? 0)];
        $rows[] = ['Pass Rate (%)', $this->formatNumber($summary['pass_rate'] ?? null)];
        $rows[] = ['Average Attendance (%)', $this->formatNumber($summary['average_attendance'] ?? null)];
        $rows[] = ['Fee Defaulters', (string) ($summary['fee_defaulters'] ?? 0)];
        $rows[] = ['Average Result (%)', $this->formatNumber($summary['average_result_percentage'] ?? null)];
        $rows[] = ['Active Teachers', (string) ($summary['active_teachers'] ?? 0)];
        $rows[] = [];

        $rows[] = ['Top Performers'];
        $rows[] = ['Student', 'Class', 'Percentage', 'Rank'];
        foreach ($topPerformers as $row) {
            $rows[] = [
                (string) ($row['student_name'] ?? ''),
                (string) ($row['class_name'] ?? ''),
                $this->formatNumber($row['percentage'] ?? null),
                (string) ($row['rank'] ?? ''),
            ];
        }
        $rows[] = [];

        $rows[] = ['Weak Students / At-Risk'];
        $rows[] = ['Student', 'Class', 'Result %', 'Attendance %', 'Risk Level'];
        foreach ($weakStudents as $row) {
            $rows[] = [
                (string) ($row['student_name'] ?? ''),
                (string) ($row['class_name'] ?? ''),
                $this->formatNumber($row['result_percentage'] ?? null),
                $this->formatNumber($row['attendance_percentage'] ?? null),
                ucfirst((string) ($row['risk_level'] ?? '')),
            ];
        }
        $rows[] = [];

        $rows[] = ['Subject Performance'];
        $rows[] = ['Subject', 'Avg %', 'Pass %', 'Difficulty', 'Entries'];
        foreach ($subjectPerformance as $row) {
            $rows[] = [
                (string) ($row['subject_name'] ?? ''),
                $this->formatNumber($row['average_percentage'] ?? null),
                $this->formatNumber($row['pass_percentage'] ?? null),
                ucfirst((string) ($row['difficulty'] ?? '')),
                (string) ($row['entries'] ?? 0),
            ];
        }
        $rows[] = [];

        $rows[] = ['Teacher Performance'];
        $rows[] = ['Teacher', 'Teacher Code', 'Avg Score %', 'Pass %', 'Rank', 'Entries', 'Classes'];
        foreach ($teacherPerformance as $row) {
            $rows[] = [
                (string) ($row['teacher_name'] ?? ''),
                (string) ($row['teacher_code'] ?? ''),
                $this->formatNumber($row['average_score'] ?? null),
                $this->formatNumber($row['pass_percentage'] ?? null),
                (string) ($row['rank'] ?? ''),
                (string) ($row['entries'] ?? 0),
                (string) ($row['classes_count'] ?? 0),
            ];
        }
        $rows[] = [];

        $rows[] = ['Attendance Trend'];
        $rows[] = ['Month', 'Attendance %'];
        $trendLabels = $attendanceTrend['labels'] ?? [];
        $trendValues = $attendanceTrend['values'] ?? [];
        foreach ($trendLabels as $index => $label) {
            $rows[] = [
                (string) $label,
                $this->formatNumber($trendValues[$index] ?? null),
            ];
        }
        $rows[] = [];

        $rows[] = ['Fee Defaulter Summary'];
        $rows[] = ['Active Defaulters', (string) ($feeDefaulterSummary['active_count'] ?? 0)];
        $rows[] = ['Total Due', $this->formatCurrency($feeDefaulterSummary['total_due'] ?? 0)];
        $rows[] = ['Oldest Due Date', (string) ($feeDefaulterSummary['oldest_due_date'] ?? 'N/A')];
        $rows[] = [];

        $rows[] = ['Class Comparison'];
        $rows[] = ['Class', 'Avg %', 'Pass %', 'Attendance %', 'Students', 'Rank'];
        foreach ($classComparison as $row) {
            $rows[] = [
                (string) ($row['class_name'] ?? ''),
                $this->formatNumber($row['average_percentage'] ?? null),
                $this->formatNumber($row['pass_percentage'] ?? null),
                $this->formatNumber($row['attendance_percentage'] ?? null),
                (string) ($row['students_count'] ?? 0),
                (string) ($row['rank'] ?? ''),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Analytics Report';
    }

    private function formatNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return number_format((float) $value, 2);
    }

    private function formatCurrency(mixed $value): string
    {
        return number_format((float) $value, 2);
    }
}

