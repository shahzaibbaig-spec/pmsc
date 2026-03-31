<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Result Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 18px; color: #111827; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .logo { width: 64px; height: 64px; object-fit: contain; }
        .title { font-size: 22px; font-weight: 700; margin: 0; }
        .sub { font-size: 13px; margin-top: 3px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { border: 1px solid #d1d5db; padding: 6px; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px; font-size: 11px; }
        .table th { background: #f3f4f6; text-align: left; }
        .summary { margin-top: 10px; font-size: 12px; }
        .summary p { margin: 4px 0; }
        .sign { margin-top: 28px; width: 100%; }
        .sign td { width: 50%; text-align: center; font-size: 12px; vertical-align: bottom; }
        .line { border-top: 1px solid #111827; width: 75%; margin: 0 auto 6px auto; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <p class="title">{{ $report['school']['name'] }}</p>
            <p class="sub">{{ $report['uses_grade_system'] ? 'Early Years Grade Report' : 'Class Result Report' }}</p>
        </div>
        <div>
            @if(!empty($report['school']['logo_absolute_path']))
                <img class="logo" src="{{ $report['school']['logo_absolute_path'] }}" alt="School Logo">
            @endif
        </div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Class:</strong> {{ $report['class']['name'] }}</td>
            <td><strong>Session:</strong> {{ $report['exam']['session'] }}</td>
            <td><strong>Exam Type:</strong> {{ $report['exam']['exam_type_label'] }}</td>
            <td><strong>Date:</strong> {{ $report['exam']['generated_at'] }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Subjects</th>
                @if ($report['uses_grade_system'])
                    <th>Overall Grade</th>
                    <th>Description</th>
                @else
                    <th>Total Marks</th>
                    <th>Obtained</th>
                    <th>%</th>
                    <th>Grade</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($report['students'] as $row)
                <tr>
                    <td>{{ $row['student_id'] }}</td>
                    <td>{{ $row['student_name'] }}</td>
                    <td>{{ $row['subjects_count'] }}</td>
                    @if ($report['uses_grade_system'])
                        <td>{{ $row['grade'] ?? '-' }}</td>
                        <td>{{ $row['grade_label'] ?? '-' }}</td>
                    @else
                        <td>{{ $row['total_marks'] }}</td>
                        <td>{{ $row['obtained_marks'] }}</td>
                        <td>{{ $row['percentage'] }}%</td>
                        <td>{{ $row['grade'] }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $report['uses_grade_system'] ? 5 : 7 }}" style="text-align:center;">No result data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Students:</strong> {{ $report['summary']['students_count'] }}</p>
        @if ($report['uses_grade_system'])
            <p><strong>Assessment Mode:</strong> Grade-based reporting for early years.</p>
        @else
            <p><strong>Total Marks:</strong> {{ $report['summary']['total_marks'] }} | <strong>Obtained:</strong> {{ $report['summary']['obtained_marks'] }}</p>
            <p><strong>Overall %:</strong> {{ $report['summary']['overall_percentage'] }}% | <strong>Pass Rate:</strong> {{ $report['summary']['pass_rate'] }}%</p>
        @endif
    </div>

    <table class="sign">
        <tr>
            <td>
                <div class="line"></div>
                {{ $report['signatures']['class_teacher'] }}<br>
                Class Teacher Signature
            </td>
            <td>
                <div class="line"></div>
                {{ $report['signatures']['principal'] }}<br>
                Principal Signature
            </td>
        </tr>
    </table>
</body>
</html>
