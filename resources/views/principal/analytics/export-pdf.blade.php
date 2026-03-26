<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Principal Analytics Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        .header { margin-bottom: 12px; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        .meta { margin: 4px 0 0; font-size: 10px; color: #334155; }
        .section { margin-top: 14px; }
        .section h3 { margin: 0 0 6px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-weight: bold; }
        .grid { width: 100%; border: 1px solid #cbd5e1; border-collapse: collapse; }
        .grid td { border: 1px solid #cbd5e1; padding: 6px; }
    </style>
</head>
<body>
    @php
        $filters = $report['filters'] ?? [];
        $summary = $report['summary'] ?? [];
        $topPerformers = $report['top_performers'] ?? [];
        $weakStudents = $report['weak_students'] ?? [];
        $subjectPerformance = $report['subject_performance'] ?? [];
        $teacherPerformance = $report['teacher_performance'] ?? [];
        $attendanceTrend = $report['attendance_trend'] ?? ['labels' => [], 'values' => []];
        $feeSummary = $report['fee_defaulter_summary'] ?? [];
        $classComparison = $report['class_comparison'] ?? [];
        $formatPercent = fn ($value) => $value !== null ? number_format((float) $value, 2).'%' : 'N/A';
    @endphp

    <div class="header">
        <p class="title">Principal Analytics Report</p>
        <p class="meta">
            Generated: {{ $filters['generated_at'] ?? now()->toDateTimeString() }}
            | Session: {{ $filters['session'] ?? 'N/A' }}
            | Exam: {{ $filters['exam_label'] ?? 'All Exams' }}
            | Class: {{ $filters['class_name'] ?? 'All Classes' }}
        </p>
    </div>

    <div class="section">
        <h3>KPI Summary</h3>
        <table class="grid">
            <tr><td>Total Students</td><td>{{ $summary['total_students'] ?? 0 }}</td></tr>
            <tr><td>Pass Rate</td><td>{{ $formatPercent($summary['pass_rate'] ?? null) }}</td></tr>
            <tr><td>Average Attendance</td><td>{{ $formatPercent($summary['average_attendance'] ?? null) }}</td></tr>
            <tr><td>Fee Defaulters</td><td>{{ $summary['fee_defaulters'] ?? 0 }}</td></tr>
            <tr><td>Average Result %</td><td>{{ $formatPercent($summary['average_result_percentage'] ?? null) }}</td></tr>
            <tr><td>Active Teachers</td><td>{{ $summary['active_teachers'] ?? 0 }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Top Performers</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Percentage</th>
                    <th>Rank</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topPerformers as $row)
                    <tr>
                        <td>{{ $row['student_name'] ?? '' }}</td>
                        <td>{{ $row['class_name'] ?? '' }}</td>
                        <td>{{ $formatPercent($row['percentage'] ?? null) }}</td>
                        <td>{{ $row['rank'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Weak Students / At-Risk</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Result %</th>
                    <th>Attendance %</th>
                    <th>Risk</th>
                </tr>
            </thead>
            <tbody>
                @forelse($weakStudents as $row)
                    <tr>
                        <td>{{ $row['student_name'] ?? '' }}</td>
                        <td>{{ $row['class_name'] ?? '' }}</td>
                        <td>{{ $formatPercent($row['result_percentage'] ?? null) }}</td>
                        <td>{{ $formatPercent($row['attendance_percentage'] ?? null) }}</td>
                        <td>{{ ucfirst((string) ($row['risk_level'] ?? '')) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Subject Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Average %</th>
                    <th>Pass %</th>
                    <th>Difficulty</th>
                    <th>Entries</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjectPerformance as $row)
                    <tr>
                        <td>{{ $row['subject_name'] ?? '' }}</td>
                        <td>{{ $formatPercent($row['average_percentage'] ?? null) }}</td>
                        <td>{{ $formatPercent($row['pass_percentage'] ?? null) }}</td>
                        <td>{{ ucfirst((string) ($row['difficulty'] ?? '')) }}</td>
                        <td>{{ $row['entries'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Teacher Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Code</th>
                    <th>Average %</th>
                    <th>Pass %</th>
                    <th>Rank</th>
                    <th>Entries</th>
                    <th>Classes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teacherPerformance as $row)
                    <tr>
                        <td>{{ $row['teacher_name'] ?? '' }}</td>
                        <td>{{ $row['teacher_code'] ?? '' }}</td>
                        <td>{{ $formatPercent($row['average_score'] ?? null) }}</td>
                        <td>{{ $formatPercent($row['pass_percentage'] ?? null) }}</td>
                        <td>{{ $row['rank'] ?? '' }}</td>
                        <td>{{ $row['entries'] ?? 0 }}</td>
                        <td>{{ $row['classes_count'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Attendance Trend</h3>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $labels = $attendanceTrend['labels'] ?? [];
                    $values = $attendanceTrend['values'] ?? [];
                @endphp
                @forelse($labels as $index => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $formatPercent($values[$index] ?? null) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Fee Defaulter Summary</h3>
        <table class="grid">
            <tr><td>Active Defaulters</td><td>{{ $feeSummary['active_count'] ?? 0 }}</td></tr>
            <tr><td>Total Due</td><td>{{ number_format((float) ($feeSummary['total_due'] ?? 0), 2) }}</td></tr>
            <tr><td>Oldest Due Date</td><td>{{ $feeSummary['oldest_due_date'] ?? 'N/A' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Class Comparison</h3>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Average %</th>
                    <th>Pass %</th>
                    <th>Attendance %</th>
                    <th>Students</th>
                    <th>Rank</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classComparison as $row)
                    <tr>
                        <td>{{ $row['class_name'] ?? '' }}</td>
                        <td>{{ $formatPercent($row['average_percentage'] ?? null) }}</td>
                        <td>{{ $formatPercent($row['pass_percentage'] ?? null) }}</td>
                        <td>{{ $formatPercent($row['attendance_percentage'] ?? null) }}</td>
                        <td>{{ $row['students_count'] ?? 0 }}</td>
                        <td>{{ $row['rank'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>

