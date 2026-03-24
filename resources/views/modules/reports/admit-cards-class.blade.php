<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Admit Cards</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 20px; color: #0f172a; }
        .meta { margin-bottom: 12px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; background: #f8fafc; }
        .meta p { margin: 2px 0; font-size: 11px; }
        .card { border: 2px solid #0f172a; border-radius: 10px; padding: 16px; margin-top: 8px; }
        .top { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .top td { vertical-align: top; }
        .logo { width: 72px; height: 72px; object-fit: contain; }
        .title { text-align: center; }
        .school { margin: 0; font-size: 20px; font-weight: 700; }
        .subtitle { margin: 4px 0 0 0; font-size: 12px; }
        .badge { display: inline-block; margin-top: 6px; border: 1px solid #0f172a; border-radius: 999px; padding: 3px 10px; font-size: 10px; font-weight: 700; }
        .student { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .student td { border: 1px solid #cbd5e1; padding: 7px 9px; font-size: 12px; }
        .label { color: #334155; font-weight: 700; width: 24%; background: #f8fafc; }
        .photo-box { width: 90px; height: 108px; border: 1px solid #94a3b8; text-align: center; vertical-align: middle; font-size: 10px; color: #64748b; }
        .photo { width: 88px; height: 106px; object-fit: cover; }
        .instructions { margin-top: 12px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; background: #f8fafc; }
        .instructions h3 { margin: 0 0 6px 0; font-size: 12px; }
        .instructions ul { margin: 0; padding-left: 16px; }
        .instructions li { margin-bottom: 3px; font-size: 11px; }
        .foot { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .foot td { width: 50%; text-align: center; font-size: 12px; vertical-align: bottom; }
        .line { border-top: 1px solid #0f172a; width: 72%; margin: 0 auto 6px auto; }
        .issue { margin-top: 10px; text-align: right; font-size: 11px; color: #334155; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="meta">
        <p><strong>Class:</strong> {{ $meta['class_name'] }}</p>
        <p><strong>Exam Session:</strong> {{ $meta['exam_session_name'] }} ({{ $meta['exam_session_range'] }})</p>
        <p><strong>Total Students:</strong> {{ $meta['total_students'] }} | <strong>Cards Generated:</strong> {{ $meta['cards_generated'] }} | <strong>Blocked:</strong> {{ $meta['blocked_count'] }}</p>
    </div>

    @foreach($cards as $card)
        <div class="card">
            <table class="top">
                <tr>
                    <td style="width: 90px;">
                        @if(!empty($card['school']['logo_absolute_path']))
                            <img class="logo" src="{{ $card['school']['logo_absolute_path'] }}" alt="School Logo">
                        @endif
                    </td>
                    <td class="title">
                        <h1 class="school">{{ $card['school']['name'] }}</h1>
                        <p class="subtitle">Official Examination Admit Card</p>
                        <span class="badge">{{ $card['exam_session']['name'] }}</span>
                    </td>
                    <td style="width: 90px;"></td>
                </tr>
            </table>

            <table class="student">
                <tr>
                    <td class="label">Student Name</td>
                    <td>{{ $card['student']['name'] }}</td>
                    <td class="photo-box" rowspan="4">
                        @if(!empty($card['student']['photo_absolute_path']))
                            <img class="photo" src="{{ $card['student']['photo_absolute_path'] }}" alt="Student Photo">
                        @else
                            Student Photo
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Student ID</td>
                    <td>{{ $card['student']['student_code'] }}</td>
                </tr>
                <tr>
                    <td class="label">Father Name</td>
                    <td>{{ $card['student']['father_name'] }}</td>
                </tr>
                <tr>
                    <td class="label">Class</td>
                    <td>{{ $card['student']['class_name'] }}</td>
                </tr>
                <tr>
                    <td class="label">Academic Session</td>
                    <td colspan="2">{{ $card['exam_session']['session'] }}</td>
                </tr>
                <tr>
                    <td class="label">Exam Duration</td>
                    <td colspan="2">{{ $card['exam_session']['start_date'] }} to {{ $card['exam_session']['end_date'] }}</td>
                </tr>
            </table>

            <div class="instructions">
                <h3>Instructions</h3>
                <ul>
                    <li>Carry this admit card to every paper.</li>
                    <li>Arrive at least 30 minutes before the exam start time.</li>
                    <li>Bring your required stationery and school ID.</li>
                    <li>Mobile phones and unauthorized material are not allowed.</li>
                </ul>
            </div>

            <table class="foot">
                <tr>
                    <td>
                        <div class="line"></div>
                        {{ $card['signatures']['controller'] }}<br>
                        Exam Controller
                    </td>
                    <td>
                        <div class="line"></div>
                        {{ $card['signatures']['principal'] }}<br>
                        Principal
                    </td>
                </tr>
            </table>

            <div class="issue">Issued on: {{ $card['issued_at'] }}</div>
        </div>

        @if(! $loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
