<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher ACR Bulk Print - {{ $payload['session'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; line-height: 1.4; }
        .page { page-break-after: always; padding: 10px 6px; }
        .page:last-child { page-break-after: auto; }
        .header { border-bottom: 1px solid #cbd5e1; margin-bottom: 12px; padding-bottom: 8px; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        .subtle { color: #475569; font-size: 11px; margin-top: 3px; }
        .section-title { font-size: 12px; font-weight: bold; margin: 12px 0 6px; text-transform: uppercase; color: #1e293b; }
        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 3px 0; vertical-align: top; }
        .kv td.label { width: 170px; color: #475569; }
        .score-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .score-table th, .score-table td { border: 1px solid #cbd5e1; padding: 6px; font-size: 11px; vertical-align: top; }
        .score-table th { background: #f8fafc; text-align: left; }
        .block { border: 1px solid #cbd5e1; padding: 8px; min-height: 56px; margin-top: 6px; white-space: pre-line; }
        .signature { margin-top: 22px; }
        .line { margin-top: 28px; border-top: 1px solid #475569; width: 220px; }
    </style>
</head>
<body>
@foreach (($payload['acrs'] ?? []) as $item)
    @php
        $acr = $item['acr'];
        $teacher = $item['teacher'];
        $scores = $item['scores'];
        $metrics = $item['metrics'];
        $narrative = $item['narrative'];
    @endphp
    <div class="page">
        <div class="header">
            <p class="title">{{ $payload['school']['name'] ?? config('app.name') }}</p>
            <p class="subtle">Teacher ACR (Confidential) | Session: {{ $payload['session'] }} | Generated: {{ $payload['generated_at']->format('d M Y, h:i A') }}</p>
            <p class="subtle">Status Filter: {{ $payload['status_label'] }}</p>
        </div>

        <p class="section-title">Teacher Details</p>
        <table class="kv">
            <tr><td class="label">Teacher Name</td><td>{{ $teacher['name'] }}</td></tr>
            <tr><td class="label">Teacher ID</td><td>{{ $teacher['teacher_id'] }}</td></tr>
            <tr><td class="label">Employee Code</td><td>{{ $teacher['employee_code'] ?: '-' }}</td></tr>
            <tr><td class="label">Designation</td><td>{{ $teacher['designation'] ?: 'Teacher' }}</td></tr>
            <tr><td class="label">ACR Status</td><td>{{ $acr['status_label'] }}</td></tr>
            <tr><td class="label">Final Grade</td><td>{{ $acr['final_grade'] ?: 'Pending review' }}</td></tr>
            <tr><td class="label">Total Score</td><td>{{ number_format((float) $acr['total_score'], 2) }}/100</td></tr>
        </table>

        <p class="section-title">Score Breakdown</p>
        <table class="score-table">
            <thead>
                <tr>
                    <th style="width: 28%;">Component</th>
                    <th style="width: 42%;">Metric</th>
                    <th style="width: 15%;">Score</th>
                    <th style="width: 15%;">Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($scores as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['metric'] }}</td>
                        <td>{{ number_format((float) $row['score'], 2) }}</td>
                        <td>{{ number_format((float) $row['weight'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="section-title">Performance Metrics</p>
        <table class="kv">
            <tr><td class="label">Attendance Percentage</td><td>{{ $metrics['attendance_percentage'] !== null ? number_format((float) $metrics['attendance_percentage'], 2).'%' : 'N/A' }}</td></tr>
            <tr><td class="label">Teacher CGPA</td><td>{{ $metrics['teacher_cgpa'] !== null ? number_format((float) $metrics['teacher_cgpa'], 2) : 'N/A' }}</td></tr>
            <tr><td class="label">Pass Percentage</td><td>{{ $metrics['pass_percentage'] !== null ? number_format((float) $metrics['pass_percentage'], 2).'%' : 'N/A' }}</td></tr>
            <tr><td class="label">Student Improvement</td><td>{{ $metrics['student_improvement_percentage'] !== null ? number_format((float) $metrics['student_improvement_percentage'], 2).'%' : 'N/A' }}</td></tr>
            <tr><td class="label">Trainings Attended</td><td>{{ $metrics['trainings_attended'] }}</td></tr>
        </table>

        <p class="section-title">Strengths</p>
        <div class="block">{{ $narrative['strengths'] ?: '-' }}</div>

        <p class="section-title">Areas for Improvement</p>
        <div class="block">{{ $narrative['areas_for_improvement'] ?: '-' }}</div>

        <p class="section-title">Recommendations</p>
        <div class="block">{{ $narrative['recommendations'] ?: '-' }}</div>

        <p class="section-title">Confidential Remarks</p>
        <div class="block">{{ $narrative['confidential_remarks'] ?: '-' }}</div>

        <div class="signature">
            <p>Principal Signature</p>
            <div class="line"></div>
        </div>
    </div>
@endforeach
</body>
</html>

