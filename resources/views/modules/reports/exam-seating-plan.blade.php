<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Seating Plan</title>
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
        .container {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 14px 24px;
        }
        .sheet {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }
        .title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
        }
        .subtitle {
            margin: 6px 0 0;
            font-size: 14px;
            text-align: center;
            color: #334155;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .meta td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            font-size: 13px;
        }
        .meta .label {
            width: 22%;
            font-weight: 700;
            background: #f8fafc;
        }
        .classes {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .class-chip {
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #f8fafc;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }
        .room-block {
            margin-top: 16px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
        }
        .room-head {
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 10px 12px;
        }
        .room-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }
        .room-subtitle {
            margin: 4px 0 0;
            font-size: 12px;
            color: #475569;
        }
        .table-wrap {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 10px;
            font-size: 12px;
            text-align: left;
            vertical-align: top;
        }
        .table th {
            background: #f8fafc;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            font-size: 11px;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .student-name {
            font-weight: 700;
            color: #0f172a;
        }
        .student-code {
            margin-top: 2px;
            color: #64748b;
            font-size: 11px;
        }
        .page-break {
            page-break-after: always;
        }
        @media print {
            body {
                background: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .container {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .sheet,
            .room-block {
                border-color: #cbd5e1;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print</button>
        <a href="javascript:window.close()">Close</a>
    </div>

    <div class="container">
        <section class="sheet">
            <h1 class="title">{{ $school['name'] ?? 'School Management System' }}</h1>
            <p class="subtitle">Exam Seating Plan</p>

            <table class="meta">
                <tr>
                    <td class="label">Plan ID</td>
                    <td>#{{ (int) $plan->id }}</td>
                    <td class="label">Exam Session</td>
                    <td>{{ $plan->examSession?->name ?? 'Session' }} ({{ $plan->examSession?->session ?? '-' }})</td>
                </tr>
                <tr>
                    <td class="label">Date Range</td>
                    <td>
                        {{ optional($plan->examSession?->start_date)->format('d M Y') ?: '-' }}
                        to
                        {{ optional($plan->examSession?->end_date)->format('d M Y') ?: '-' }}
                    </td>
                    <td class="label">Generated</td>
                    <td>{{ optional($plan->generated_at)->format('d M Y h:i A') ?: '-' }} by {{ $plan->generator?->name ?: 'System' }}</td>
                </tr>
                <tr>
                    <td class="label">Students</td>
                    <td>{{ (int) $plan->total_students }}</td>
                    <td class="label">Rooms Used</td>
                    <td>{{ (int) $plan->total_rooms }}</td>
                </tr>
                <tr>
                    <td class="label">Mode</td>
                    <td>{{ $plan->is_randomized ? 'Randomized' : 'Roll Number Order' }}</td>
                    <td class="label">Printed</td>
                    <td>{{ now()->format('d M Y h:i A') }}</td>
                </tr>
            </table>

            <div class="classes">
                @forelse($classLabels as $label)
                    <span class="class-chip">{{ $label }}</span>
                @empty
                    <span class="class-chip">No classes listed</span>
                @endforelse
            </div>
        </section>

        @foreach($roomGroups as $group)
            <section class="room-block">
                <div class="room-head">
                    <h2 class="room-title">{{ $group['room']?->name ?? 'Room' }}</h2>
                    <p class="room-subtitle">
                        Seats Used: {{ (int) $group['used_seats'] }} / {{ (int) $group['capacity'] }}
                    </p>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Seat #</th>
                                <th>Student</th>
                                <th>Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['assignments'] as $assignment)
                                @php
                                    $student = $assignment->student;
                                    $className = trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? ''));
                                    $className = $className !== '' ? $className : '-';
                                @endphp
                                <tr>
                                    <td>{{ (int) $assignment->seat_number }}</td>
                                    <td>
                                        <div class="student-name">{{ $student?->name ?? 'Student' }}</div>
                                        <div class="student-code">{{ $student?->student_id ?: ($student?->id ?? '-') }}</div>
                                    </td>
                                    <td>{{ $className }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @if(! $loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    </div>
</body>
</html>
