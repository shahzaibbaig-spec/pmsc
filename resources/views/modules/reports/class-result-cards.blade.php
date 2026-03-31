<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Result Cards</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 18px; color: #111827; }
        .card { page-break-after: always; }
        .card:last-child { page-break-after: auto; }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 14px; }
        .logo { width: 64px; height: 64px; object-fit: contain; }
        .school { font-size: 21px; font-weight: 700; margin: 0; }
        .subtitle { margin: 3px 0 0 0; font-size: 12px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { border: 1px solid #d1d5db; padding: 6px 8px; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 7px; font-size: 11px; }
        .table th { background: #f3f4f6; text-align: left; }
        .summary { margin-top: 12px; font-size: 12px; }
        .summary p { margin: 4px 0; }
        .signatures { margin-top: 26px; width: 100%; }
        .signatures td { width: 50%; vertical-align: bottom; text-align: center; font-size: 12px; }
        .line { border-top: 1px solid #111827; width: 75%; margin: 0 auto 6px auto; }
        .card-index { margin-top: 12px; text-align: right; font-size: 11px; color: #4b5563; }
    </style>
</head>
<body>
    @foreach($report['cards'] as $index => $result)
        <div class="card">
            <div class="header">
                <div>
                    <p class="school">{{ $report['school']['name'] }}</p>
                    <p class="subtitle">
                        {{ $report['uses_grade_system'] ? 'Early Years Grade Report' : 'Result Card' }}
                        | {{ $report['class']['name'] }} | {{ $report['exam']['exam_type_label'] }} ({{ $report['exam']['session'] }})
                    </p>
                </div>
                <div>
                    @if(!empty($report['school']['logo_absolute_path']))
                        <img class="logo" src="{{ $report['school']['logo_absolute_path'] }}" alt="School Logo">
                    @elseif(!empty($report['school']['logo_path']))
                        <img class="logo" src="{{ public_path('storage/'.$report['school']['logo_path']) }}" alt="School Logo">
                    @endif
                </div>
            </div>

            <table class="meta">
                <tr>
                    <td><strong>Student Name:</strong> {{ $result['student']['name'] }}</td>
                    <td><strong>Student ID:</strong> {{ $result['student']['student_id'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Class:</strong> {{ $result['student']['class'] ?: '-' }}</td>
                    <td><strong>Age:</strong> {{ $result['student']['age'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Exam Type:</strong> {{ $result['exam']['exam_type_label'] }}</td>
                    <td><strong>Date Generated:</strong> {{ $result['exam']['generated_at'] }}</td>
                </tr>
            </table>

            <table class="table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        @if ($report['uses_grade_system'])
                            <th>Grade</th>
                            <th>Description</th>
                        @else
                            <th>Total Marks</th>
                            <th>Obtained</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($result['subjects'] as $row)
                        <tr>
                            <td>{{ $row['subject'] }}</td>
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
                    @endforeach
                </tbody>
            </table>

            <div class="summary">
                @if ($report['uses_grade_system'])
                    <p><strong>Overall Grade:</strong> {{ $result['summary']['grade'] ?? '-' }}</p>
                    <p><strong>Descriptor:</strong> {{ $result['summary']['grade_label'] ?? '-' }}</p>
                    <p><strong>Overall Performance:</strong> {{ $result['summary']['overall_performance'] ?? '-' }}</p>
                @else
                    <p><strong>Total Marks:</strong> {{ $result['summary']['total_marks'] }}</p>
                    <p><strong>Obtained Marks:</strong> {{ $result['summary']['obtained_marks'] }}</p>
                    <p><strong>Overall Percentage:</strong> {{ $result['summary']['percentage'] }}%</p>
                    <p><strong>Overall Grade:</strong> {{ $result['summary']['grade'] }}</p>
                @endif
            </div>

            <table class="signatures">
                <tr>
                    <td>
                        <div class="line"></div>
                        {{ $result['signatures']['class_teacher'] }}<br>
                        Class Teacher Signature
                    </td>
                    <td>
                        <div class="line"></div>
                        {{ $result['signatures']['principal'] }}<br>
                        Principal Signature
                    </td>
                </tr>
            </table>

            <p class="card-index">Card {{ $index + 1 }} of {{ count($report['cards']) }}</p>
        </div>
    @endforeach
</body>
</html>
