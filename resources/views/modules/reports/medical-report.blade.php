<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 18px; color: #111827; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .logo { width: 64px; height: 64px; object-fit: contain; }
        .title { font-size: 22px; font-weight: 700; margin: 0; }
        .sub { font-size: 12px; margin-top: 3px; }
        .summary { margin-bottom: 10px; width: 100%; border-collapse: collapse; }
        .summary td { border: 1px solid #d1d5db; padding: 6px; font-size: 11px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px; font-size: 10px; }
        .table th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <p class="title">{{ $school['name'] }}</p>
            <p class="sub">Medical Report ({{ ucfirst($filters['report_type']) }})</p>
        </div>
        <div>
            @if(!empty($school['logo_absolute_path']))
                <img class="logo" src="{{ $school['logo_absolute_path'] }}" alt="School Logo">
            @endif
        </div>
    </div>

    <table class="summary">
        <tr>
            <td><strong>Year:</strong> {{ $filters['year'] }}</td>
            <td><strong>Month:</strong> {{ $filters['report_type'] === 'monthly' ? ($filters['month'] ?? '-') : '-' }}</td>
            <td><strong>Total:</strong> {{ $report['summary']['total'] }}</td>
            <td><strong>Pending:</strong> {{ $report['summary']['pending'] }}</td>
            <td><strong>Completed:</strong> {{ $report['summary']['completed'] }}</td>
            <td><strong>Fever/Headache/Stomach/Other:</strong>
                {{ $report['summary']['fever'] }}/{{ $report['summary']['headache'] }}/{{ $report['summary']['stomach_ache'] }}/{{ $report['summary']['other'] }}
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Class</th>
                <th>Illness</th>
                <th>Diagnosis</th>
                <th>Prescription</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['data'] as $row)
                <tr>
                    <td>{{ $row['created_at'] }}</td>
                    <td>{{ $row['student_name'] }} ({{ $row['student_id'] }})</td>
                    <td>{{ $row['class_name'] }}</td>
                    <td>{{ $row['illness_label'] }}{{ $row['illness_other_text'] ? ' - '.$row['illness_other_text'] : '' }}</td>
                    <td>{{ $row['diagnosis'] ?? '-' }}</td>
                    <td>{{ $row['prescription'] ?? '-' }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">No medical records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

