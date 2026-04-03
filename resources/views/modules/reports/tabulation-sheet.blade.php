<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabulation Sheet</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 14px; color: #0f172a; }
        .title { text-align: center; margin: 0; font-size: 20px; font-weight: 700; }
        .subtitle { text-align: center; margin: 4px 0 0; font-size: 11px; color: #334155; }
        .meta { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .meta td { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 10px; }
        .meta .label { width: 16%; background: #f8fafc; font-weight: 700; color: #334155; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 5px 6px; font-size: 9px; text-align: left; }
        .table th { background: #f8fafc; color: #334155; font-size: 8.8px; text-transform: uppercase; }
        .student { font-weight: 700; }
    </style>
</head>
<body>
    @php
        $usesGradeSystem = (bool) ($report['uses_grade_system'] ?? false);
    @endphp

    <h1 class="title">{{ $school['name'] ?? 'School Management System' }}</h1>
    <p class="subtitle">{{ $usesGradeSystem ? 'Grade-Based Tabulation Sheet' : 'Tabulation Sheet' }}</p>

    <table class="meta">
        <tr>
            <td class="label">Class</td>
            <td>{{ $report['class']['name'] }}</td>
            <td class="label">Session</td>
            <td>{{ $report['exam']['session'] }}</td>
            <td class="label">Exam Type</td>
            <td>{{ $report['exam']['exam_type_label'] }}</td>
        </tr>
        <tr>
            <td class="label">Students</td>
            <td>{{ $report['summary']['students_count'] }}</td>
            <td class="label">Subjects</td>
            <td>{{ $report['summary']['subjects_count'] }}</td>
            <td class="label">{{ $usesGradeSystem ? 'Mode' : 'Class Avg' }}</td>
            <td>{{ $usesGradeSystem ? 'Grade-based' : number_format((float) $report['summary']['class_average_percentage'], 2).'%' }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Student</th>
                @foreach($report['subjects'] as $subject)
                    <th>{{ $subject['name'] }}</th>
                @endforeach
                @if ($usesGradeSystem)
                    <th>Overall Grade</th>
                    <th>Description</th>
                @else
                    <th>Total</th>
                    <th>Obtained</th>
                    <th>%</th>
                    <th>Grade</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] as $row)
                <tr>
                    <td class="student">{{ $row['student_name'] }}<br>{{ $row['student_code'] }}</td>
                    @foreach($report['subjects'] as $subject)
                        @php
                            $subjectId = (int) $subject['id'];
                            $subjectMark = $row['subject_marks'][$subjectId] ?? ($usesGradeSystem
                                ? ['grade' => null, 'label' => null]
                                : ['obtained' => 0, 'total' => (int) $subject['total_marks']]);
                        @endphp
                        <td>
                            @if ($usesGradeSystem)
                                {{ $subjectMark['grade'] ?? '-' }}
                                @if (!empty($subjectMark['label']))
                                    <br><span style="font-size: 8px; color: #475569;">{{ $subjectMark['label'] }}</span>
                                @endif
                            @else
                                {{ (int) $subjectMark['obtained'] }}/{{ (int) $subjectMark['total'] }}
                            @endif
                        </td>
                    @endforeach
                    @if ($usesGradeSystem)
                        <td>{{ $row['grade'] ?? '-' }}</td>
                        <td>{{ $row['grade_label'] ?? '-' }}</td>
                    @else
                        <td>{{ (int) $row['total_marks'] }}</td>
                        <td>{{ (int) $row['obtained_marks'] }}</td>
                        <td>{{ number_format((float) $row['percentage'], 2) }}</td>
                        <td>{{ $row['grade'] }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
