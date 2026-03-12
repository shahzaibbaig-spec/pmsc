<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 22px; color: #111827; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .logo { width: 64px; height: 64px; object-fit: contain; }
        .title { font-size: 22px; font-weight: 700; margin: 0; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { border: 1px solid #d1d5db; padding: 6px; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px; font-size: 11px; }
        .table th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <p class="title">{{ $report['school']['name'] }}</p>
            <p style="font-size:13px; margin-top:3px;">Attendance Report</p>
        </div>
        <div>
            @if(!empty($report['school']['logo_absolute_path']))
                <img class="logo" src="{{ $report['school']['logo_absolute_path'] }}" alt="School Logo">
            @endif
        </div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Date:</strong> {{ $report['date'] }}</td>
            <td><strong>Class:</strong> {{ $report['class']['name'] ?? 'All Classes' }}</td>
            <td><strong>Total Students:</strong> {{ $report['summary']['total_students'] }}</td>
            <td><strong>Present:</strong> {{ $report['summary']['present'] }}</td>
            <td><strong>Absent:</strong> {{ $report['summary']['absent'] }}</td>
            <td><strong>Leave:</strong> {{ $report['summary']['leave'] }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Class</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>{{ $row['student_id'] }}</td>
                    <td>{{ $row['student_name'] }}</td>
                    <td>{{ $row['class_name'] }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;">No attendance records found for selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

