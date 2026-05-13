<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassWiseStudentListExport implements FromArray, ShouldAutoSize, WithTitle
{
    /**
     * @param array{rows:array<int, array<string, mixed>>,filters:array<string, mixed>,school:array<string, string|null>,generated_at:\Illuminate\Support\Carbon,total:int} $payload
     */
    public function __construct(private readonly array $payload)
    {
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['Class-wise Student List'];
        $rows[] = ['School', (string) ($this->payload['school']['name'] ?? '-')];
        $rows[] = ['Session', (string) ($this->payload['filters']['session'] ?? '-')];
        $rows[] = ['Class', (string) ($this->payload['filters']['class_name'] ?? 'All Classes')];
        $rows[] = ['Section', (string) ($this->payload['filters']['section'] ?? 'All Sections')];
        $rows[] = ['Status', ucfirst((string) ($this->payload['filters']['status'] ?? 'all'))];
        $rows[] = ['Generated At', (string) ($this->payload['generated_at']?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'))];
        $rows[] = ['Total Students', (string) ($this->payload['total'] ?? 0)];
        $rows[] = [];

        $rows[] = ['Sr #', 'Admission No', 'Student Name', 'Father Name', 'Class/Section', 'Contact', 'Age', 'Date of Birth', 'Status'];

        foreach ($this->payload['rows'] as $row) {
            $rows[] = [
                (string) ($row['sr_no'] ?? ''),
                (string) ($row['student_id'] ?? ''),
                (string) ($row['name'] ?? ''),
                (string) ($row['father_name'] ?? ''),
                (string) ($row['class_section'] ?? ''),
                (string) ($row['contact'] ?? ''),
                (string) ($row['age'] ?? ''),
                (string) ($row['date_of_birth'] ?? ''),
                ucfirst((string) ($row['status'] ?? '')),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Class-wise Students';
    }
}
