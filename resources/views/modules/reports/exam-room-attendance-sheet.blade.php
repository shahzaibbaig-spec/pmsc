<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Room Attendance Sheet</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 18px; color: #0f172a; }
        .header { margin-bottom: 12px; }
        .school { margin: 0; text-align: center; font-size: 22px; font-weight: 700; }
        .title { margin: 4px 0 0 0; text-align: center; font-size: 13px; color: #334155; }
        .meta { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .meta td { border: 1px solid #cbd5e1; padding: 7px 9px; font-size: 11px; }
        .meta .label { width: 20%; background: #f8fafc; font-weight: 700; color: #334155; }
        .summary { margin-top: 10px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; background: #f8fafc; font-size: 11px; }
        .summary span { margin-right: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .table th,
        .table td { border: 1px solid #cbd5e1; padding: 7px 8px; font-size: 10.5px; text-align: left; vertical-align: top; }
        .table th { background: #f8fafc; text-transform: uppercase; font-size: 10px; color: #334155; }
        .badge { display: inline-block; border-radius: 999px; padding: 2px 7px; font-size: 9px; font-weight: 700; }
        .present { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .absent { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .late { background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; }
        .unmarked { background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; }
        .footer { margin-top: 14px; font-size: 10px; color: #475569; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="school">{{ $school['name'] ?? 'School Management System' }}</h1>
        <p class="title">Exam Room Attendance Sheet</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Exam Session</td>
            <td>{{ $sheet['exam_session']['name'] }} ({{ $sheet['exam_session']['session'] }})</td>
            <td class="label">Room</td>
            <td>{{ $sheet['room']['name'] }}</td>
        </tr>
        <tr>
            <td class="label">Date Range</td>
            <td>{{ $sheet['exam_session']['start_date'] }} to {{ $sheet['exam_session']['end_date'] }}</td>
            <td class="label">Invigilators</td>
            <td>{{ count($sheet['invigilators']) > 0 ? implode(', ', $sheet['invigilators']) : 'Not Assigned' }}</td>
        </tr>
        <tr>
            <td class="label">Seating Plan ID</td>
            <td>#{{ $sheet['plan']['id'] }}</td>
            <td class="label">Generated At</td>
            <td>{{ $generatedAt }}</td>
        </tr>
    </table>

    <div class="summary">
        <span><strong>Total Seats:</strong> {{ $sheet['summary']['total_seats'] }}</span>
        <span><strong>Marked:</strong> {{ $sheet['summary']['marked'] }}</span>
        <span><strong>Present:</strong> {{ $sheet['summary']['present'] }}</span>
        <span><strong>Absent:</strong> {{ $sheet['summary']['absent'] }}</span>
        <span><strong>Late:</strong> {{ $sheet['summary']['late'] }}</span>
        <span><strong>Unmarked:</strong> {{ $sheet['summary']['unmarked'] }}</span>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Seat #</th>
                <th>Student</th>
                <th>Class</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sheet['rows'] as $row)
                @php
                    $status = strtolower((string) ($row['status'] ?? 'present'));
                    $badgeClass = $status === 'absent'
                        ? 'absent'
                        : ($status === 'late' ? 'late' : 'present');
                    $isMarked = (bool) ($row['is_marked'] ?? false);
                @endphp
                <tr>
                    <td>{{ $row['seat_number'] }}</td>
                    <td>
                        <strong>{{ $row['student_name'] }}</strong><br>
                        {{ $row['student_code'] }}
                    </td>
                    <td>{{ $row['class_name'] }}</td>
                    <td>
                        @if($isMarked)
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                        @else
                            <span class="badge unmarked">Unmarked</span>
                        @endif
                    </td>
                    <td>{{ $row['remarks'] ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Printed on {{ $generatedAt }}
    </div>
</body>
</html>
