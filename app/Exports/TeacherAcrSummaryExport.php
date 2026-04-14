<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeacherAcrSummaryExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(private readonly array $rows)
    {
    }

    public function collection(): Collection
    {
        return collect($this->rows)->map(function (array $row): array {
            return [
                (string) ($row['teacher_name'] ?? ''),
                (string) ($row['employee_code'] ?? ''),
                (string) ($row['designation'] ?? ''),
                (string) ($row['session'] ?? ''),
                $this->numericOrBlank($row['attendance_score'] ?? null),
                $this->numericOrBlank($row['academic_score'] ?? null),
                $this->numericOrBlank($row['improvement_score'] ?? null),
                $this->numericOrBlank($row['conduct_score'] ?? null),
                $this->numericOrBlank($row['pd_score'] ?? null),
                $this->numericOrBlank($row['principal_score'] ?? null),
                $this->numericOrBlank($row['total_score'] ?? null),
                (string) ($row['final_grade'] ?? ''),
                (string) ($row['status'] ?? ''),
                $this->numericOrBlank($row['teacher_cgpa'] ?? null),
                $this->numericOrBlank($row['pass_percentage'] ?? null),
                $this->numericOrBlank($row['student_improvement_percentage'] ?? null),
                $this->numericOrBlank($row['trainings_attended'] ?? null),
                (string) ($row['strengths'] ?? ''),
                (string) ($row['areas_for_improvement'] ?? ''),
                (string) ($row['recommendations'] ?? ''),
                (string) ($row['reviewed_at'] ?? ''),
                (string) ($row['finalized_at'] ?? ''),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Teacher Name',
            'Employee Code',
            'Designation',
            'Session',
            'Attendance Score',
            'Academic Score',
            'Improvement Score',
            'Conduct Score',
            'PD Score',
            'Principal Score',
            'Total Score',
            'Final Grade',
            'Status',
            'Teacher CGPA',
            'Pass Percentage',
            'Student Improvement Percentage',
            'Trainings Attended',
            'Strengths',
            'Areas for Improvement',
            'Recommendations',
            'Reviewed At',
            'Finalized At',
        ];
    }

    private function numericOrBlank(mixed $value): string|int|float
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return '';
    }
}

