<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Class Discipline Report</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background: #f8fafc;
        }
        .shell {
            max-width: 1280px;
            margin: 0 auto;
            padding: 18px;
        }
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .sheet {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
        }
        .header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .title {
            margin: 0;
            font-size: 22px;
        }
        .subtitle {
            margin: 5px 0 0;
            color: #334155;
            font-size: 14px;
        }
        .meta {
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            font-size: 12px;
            color: #334155;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 7px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #e0ecff;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 10px;
        }
        .signature {
            margin-top: 34px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 40px;
        }
        .signature-line {
            margin-top: 44px;
            border-bottom: 1px solid #64748b;
        }
        @media print {
            body { background: #fff; }
            .shell { max-width: none; padding: 0; }
            .toolbar { display: none !important; }
            .sheet {
                border: none;
                border-radius: 0;
                padding: 0;
            }
            @page {
                size: A4 landscape;
                margin: 12mm;
            }
        }
    </style>
</head>
<body>
    @php
        $selectedDate = $filters['date'] ?? null;
        $selectedDateFrom = $filters['date_from'] ?? null;
        $selectedDateTo = $filters['date_to'] ?? null;
        $selectedSession = $filters['session'] ?? '-';
        $selectedClass = collect($classes ?? [])->firstWhere('id', (int) ($filters['class_id'] ?? 0));
        $selectedStudent = collect($students ?? [])->firstWhere('id', (int) ($filters['student_id'] ?? 0));
        $selectedTeacher = collect($teachers ?? [])->firstWhere('id', (int) ($filters['teacher_id'] ?? 0));
        $classLabel = $selectedClass['name'] ?? 'All Classes';
        $studentLabel = $selectedStudent['name'] ?? 'All Students';
        $teacherLabel = $selectedTeacher['name'] ?? 'All';
        $dateLabel = $selectedDate ? \Illuminate\Support\Carbon::parse($selectedDate)->format('d M Y') : 'All Dates';
        if ($selectedDateFrom || $selectedDateTo) {
            $from = $selectedDateFrom ? \Illuminate\Support\Carbon::parse($selectedDateFrom)->format('d M Y') : 'Start';
            $to = $selectedDateTo ? \Illuminate\Support\Carbon::parse($selectedDateTo)->format('d M Y') : 'Today';
            $dateLabel = $from.' to '.$to;
        }
    @endphp

    <div class="shell">
        <div class="toolbar">
            <button type="button" onclick="window.print()" class="btn">Print</button>
            <a href="{{ route('principal.discipline-reports.daily', request()->query()) }}" class="btn">Back</a>
        </div>

        <section class="sheet">
            <header class="header">
                <h1 class="title">Daily Class Discipline Report</h1>
                <p class="subtitle">Classroom discipline submissions by teachers for principal and warden follow-up.</p>
            </header>

            <div class="meta">
                <div><strong>Date:</strong> {{ $dateLabel }}</div>
                <div><strong>Session:</strong> {{ $selectedSession }}</div>
                <div><strong>Generated At:</strong> {{ $generated_at->format('d M Y h:i A') }}</div>
                <div><strong>Class/Section:</strong> {{ $classLabel }}</div>
                <div><strong>Student:</strong> {{ $studentLabel }}</div>
                <div><strong>Teacher:</strong> {{ $teacherLabel }}</div>
                <div><strong>Status:</strong> {{ ucfirst((string) ($filters['status'] ?? 'all')) }}</div>
                <div><strong>Total Reports:</strong> {{ number_format(is_countable($reports) ? count($reports) : 0) }}</div>
                <div><strong>Generated By:</strong> {{ $generated_by?->name ?? '-' }}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Sr #</th>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Admission No</th>
                        <th>Class/Section</th>
                        <th>Issue</th>
                        <th>Severity</th>
                        <th>Teacher</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $index => $report)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ optional($report->report_date)->format('d M Y') ?: '-' }}</td>
                            <td>{{ $report->student?->name ?? '-' }}</td>
                            <td>{{ $report->student?->student_id ?? '-' }}</td>
                            <td>{{ trim(($report->classRoom?->name ?? '').' '.($report->classRoom?->section ?? '')) ?: '-' }}</td>
                            <td>{{ $report->issue_label ?: '-' }}</td>
                            <td>{{ ucfirst((string) ($report->severity ?? 'normal')) }}</td>
                            <td>{{ $report->teacher?->name ?? '-' }}</td>
                            <td>{{ $report->auto_message ?: '-' }}</td>
                            <td>{{ ucfirst((string) $report->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center;padding:22px;color:#475569;">No discipline reports found for selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="signature">
                <div>
                    <div style="font-size:12px;color:#334155;font-weight:700;">Principal Signature</div>
                    <div class="signature-line"></div>
                </div>
                <div>
                    <div style="font-size:12px;color:#334155;font-weight:700;">Warden Signature</div>
                    <div class="signature-line"></div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
