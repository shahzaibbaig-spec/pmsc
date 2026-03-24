<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Seat Slip</title>
    <style>
        :root {
            color-scheme: light;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background: #f8fafc;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .toolbar button {
            border: 1px solid #334155;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 14px;
            cursor: pointer;
        }
        .toolbar a {
            display: inline-flex;
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 14px;
        }
        .page {
            max-width: 900px;
            margin: 24px auto;
            padding: 0 14px;
        }
        .slip {
            border: 2px solid #0f172a;
            border-radius: 14px;
            background: #ffffff;
            padding: 18px;
        }
        .title {
            margin: 0;
            font-size: 22px;
            text-align: center;
            font-weight: 700;
        }
        .subtitle {
            margin: 6px 0 0;
            text-align: center;
            color: #475569;
            font-size: 13px;
        }
        .seat-pill {
            margin: 12px auto 0;
            width: fit-content;
            border: 1px solid #334155;
            border-radius: 999px;
            background: #f8fafc;
            padding: 6px 16px;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .table td {
            border: 1px solid #e2e8f0;
            padding: 9px 10px;
            font-size: 13px;
            vertical-align: top;
        }
        .table .label {
            width: 26%;
            background: #f8fafc;
            font-weight: 700;
            color: #334155;
        }
        .foot {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            gap: 14px;
            font-size: 12px;
            color: #475569;
        }
        .note {
            margin-top: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            padding: 10px;
            font-size: 12px;
            color: #334155;
        }
        @media print {
            body {
                background: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .page {
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .slip {
                border-color: #0f172a;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print</button>
        <a href="javascript:window.close()">Close</a>
    </div>

    @php
        $student = $assignment->student;
        $className = trim(($assignment->classRoom?->name ?? $student?->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? $student?->classRoom?->section ?? ''));
        $className = $className !== '' ? $className : '-';
    @endphp

    <div class="page">
        <section class="slip">
            <h1 class="title">{{ $school['name'] ?? 'School Management System' }}</h1>
            <p class="subtitle">Exam Seat Slip</p>
            <div class="seat-pill">Room {{ $assignment->room?->name ?? '-' }} | Seat {{ (int) $assignment->seat_number }}</div>

            <table class="table">
                <tr>
                    <td class="label">Student Name</td>
                    <td>{{ $student?->name ?? 'Student' }}</td>
                    <td class="label">Student ID</td>
                    <td>{{ $student?->student_id ?: ($student?->id ?? '-') }}</td>
                </tr>
                <tr>
                    <td class="label">Father Name</td>
                    <td>{{ $student?->father_name ?: '-' }}</td>
                    <td class="label">Class</td>
                    <td>{{ $className }}</td>
                </tr>
                <tr>
                    <td class="label">Exam Session</td>
                    <td>{{ $plan->examSession?->name ?? '-' }}</td>
                    <td class="label">Academic Session</td>
                    <td>{{ $plan->examSession?->session ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Exam Date Range</td>
                    <td colspan="3">
                        {{ optional($plan->examSession?->start_date)->format('d M Y') ?: '-' }}
                        to
                        {{ optional($plan->examSession?->end_date)->format('d M Y') ?: '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Seating Plan</td>
                    <td>#{{ (int) $plan->id }}</td>
                    <td class="label">Generated</td>
                    <td>{{ optional($plan->generated_at)->format('d M Y h:i A') ?: '-' }}</td>
                </tr>
            </table>

            <div class="note">
                Keep this slip with your admit card and present it to invigilation staff before taking your seat.
            </div>

            <div class="foot">
                <div>Issued: {{ now()->format('d M Y h:i A') }}</div>
                <div>Generated by: {{ $plan->generator?->name ?: 'System' }}</div>
            </div>
        </section>
    </div>
</body>
</html>
