<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Card</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 24px; color: #111827; }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 16px; }
        .logo { width: 72px; height: 72px; object-fit: contain; }
        .school { font-size: 24px; font-weight: 700; margin: 0; }
        .meta-grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta-grid td { padding: 6px 8px; font-size: 13px; border: 1px solid #d1d5db; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; }
        .table th { background: #f3f4f6; text-align: left; }
        .summary { margin-top: 14px; font-size: 13px; }
        .signatures { margin-top: 36px; width: 100%; }
        .signatures td { width: 50%; vertical-align: bottom; text-align: center; font-size: 13px; }
        .line { border-top: 1px solid #111827; width: 75%; margin: 0 auto 6px auto; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="school">{{ $result['school']['name'] }}</h1>
            <p style="margin: 4px 0 0 0; font-size: 13px;">Student Result Card</p>
        </div>
        <div>
            @if(!empty($result['school']['logo_absolute_path']))
                <img class="logo" src="{{ $result['school']['logo_absolute_path'] }}" alt="School Logo">
            @elseif(!empty($result['school']['logo_path']))
                <img class="logo" src="{{ public_path('storage/'.$result['school']['logo_path']) }}" alt="School Logo">
            @endif
        </div>
    </div>

    <table class="meta-grid">
        <tr>
            <td><strong>Student Name:</strong> {{ $result['student']['name'] }}</td>
            <td><strong>Class:</strong> {{ $result['student']['class'] ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Age:</strong> {{ $result['student']['age'] ?? '-' }}</td>
            <td><strong>Session:</strong> {{ $result['exam']['session'] }}</td>
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
                <th>Total Marks</th>
                <th>Obtained</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($result['subjects'] as $row)
                <tr>
                    <td>{{ $row['subject'] }}</td>
                    <td>{{ $row['total_marks'] }}</td>
                    <td>{{ $row['obtained_marks'] }}</td>
                    <td>{{ $row['grade'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total Marks:</strong> {{ $result['summary']['total_marks'] }}</p>
        <p><strong>Obtained Marks:</strong> {{ $result['summary']['obtained_marks'] }}</p>
        <p><strong>Overall Percentage:</strong> {{ $result['summary']['percentage'] }}%</p>
        <p><strong>Overall Grade:</strong> {{ $result['summary']['grade'] }}</p>
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
</body>
</html>
