<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Board Summary Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 24px;
        }

        h1, h2 {
            margin: 0 0 8px;
        }

        .header {
            margin-bottom: 18px;
            border-bottom: 2px solid #d1d5db;
            padding-bottom: 10px;
        }

        .meta {
            font-size: 10px;
            color: #4b5563;
            margin-top: 6px;
        }

        .section {
            margin-bottom: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 7px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .footer {
            margin-top: 24px;
            font-size: 10px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $summary = $report['summary'] ?? [];
        $topPerformers = $report['topPerformers'] ?? [];
        $weakStudents = $report['weakStudents'] ?? [];
        $teacherPerformance = $report['teacherPerformance'] ?? [];
        $feeDefaulterSummary = $report['feeDefaulterSummary'] ?? [];
    @endphp

    <div class="header">
        <h1>Board Summary Report</h1>
        <div class="meta">
            Session: {{ $filters['session'] ?? 'All' }} |
            Exam: {{ $filters['exam_label'] ?? 'All Exams' }} |
            Class: {{ $filters['class_name'] ?? 'All Classes' }} |
            Generated: {{ $generatedAt->format('Y-m-d H:i') }}
        </div>
    </div>

    <div class="section">
        <h2>KPI Summary</h2>
        <table>
            <tbody>
                @forelse($summary as $label => $value)
                    <tr>
                        <th style="width: 40%;">{{ \Illuminate\Support\Str::of((string) $label)->replace('_', ' ')->title() }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No summary data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Top 10 Performers</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 35%;">Student</th>
                    <th style="width: 25%;">Class</th>
                    <th style="width: 20%;">Percentage</th>
                    <th style="width: 20%;">Rank</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topPerformers as $item)
                    <tr>
                        <td>{{ data_get($item, 'student_name', data_get($item, 'name', '')) }}</td>
                        <td>{{ data_get($item, 'class_name', data_get($item, 'class', '')) }}</td>
                        <td>{{ data_get($item, 'percentage') }}</td>
                        <td>{{ data_get($item, 'rank') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No performer data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Weak Students</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Student</th>
                    <th style="width: 20%;">Class</th>
                    <th style="width: 15%;">Result %</th>
                    <th style="width: 15%;">Attendance %</th>
                    <th style="width: 20%;">Risk Level</th>
                </tr>
            </thead>
            <tbody>
                @forelse($weakStudents as $item)
                    <tr>
                        <td>{{ data_get($item, 'student_name', data_get($item, 'name', '')) }}</td>
                        <td>{{ data_get($item, 'class_name', data_get($item, 'class', '')) }}</td>
                        <td>{{ data_get($item, 'result_percentage', data_get($item, 'percentage', '')) }}</td>
                        <td>{{ data_get($item, 'attendance_percentage') }}</td>
                        <td>{{ data_get($item, 'risk_level', 'Needs Review') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No weak student data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Teacher Ranking</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 35%;">Teacher</th>
                    <th style="width: 25%;">Average Score</th>
                    <th style="width: 20%;">Pass %</th>
                    <th style="width: 20%;">Rank</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teacherPerformance as $item)
                    <tr>
                        <td>{{ data_get($item, 'teacher_name', data_get($item, 'teacher', '')) }}</td>
                        <td>{{ data_get($item, 'average_score') }}</td>
                        <td>{{ data_get($item, 'pass_percentage', data_get($item, 'pass_percent', '')) }}</td>
                        <td>{{ data_get($item, 'rank') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No teacher ranking data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Fee Defaulter Summary</h2>
        <table>
            <tbody>
                @forelse($feeDefaulterSummary as $label => $value)
                    <tr>
                        <th style="width: 40%;">{{ \Illuminate\Support\Str::of((string) $label)->replace('_', ' ')->title() }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No fee defaulter data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated by School Management System
    </div>
</body>
</html>
