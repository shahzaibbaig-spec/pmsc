<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher ACR - {{ $payload['teacher']['name'] }}</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --panel: #f8fafc;
            --accent: #0f766e;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f1f5f9;
            color: var(--ink);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.5;
        }
        .shell { max-width: 1100px; margin: 0 auto; padding: 16px; }
        .toolbar { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: white;
            color: var(--ink);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .button.primary {
            background: var(--ink);
            color: white;
            border-color: var(--ink);
        }
        .paper {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            padding: 32px;
        }
        .topline { text-transform: uppercase; letter-spacing: 0.28em; color: var(--muted); font-size: 11px; font-weight: 700; }
        h1 { margin: 10px 0 4px; font-size: 34px; line-height: 1.1; }
        h2 { margin: 0 0 12px; font-size: 18px; }
        .subtle { color: var(--muted); font-size: 14px; }
        .header { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 24px; border-bottom: 1px solid #e2e8f0; }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
        }
        .panel-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-top: 28px; }
        .panel {
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: var(--panel);
            padding: 20px;
        }
        .label { text-transform: uppercase; letter-spacing: 0.18em; color: var(--muted); font-size: 11px; font-weight: 700; margin-bottom: 14px; }
        .kv { display: flex; justify-content: space-between; gap: 16px; font-size: 14px; padding: 4px 0; }
        .kv strong { color: var(--ink); }
        .table-wrap { margin-top: 28px; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8fafc; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; font-size: 13px; }
        th { color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; font-size: 11px; }
        tbody tr:last-child td { border-bottom: none; }
        .three-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-top: 28px; }
        .note-box {
            margin-top: 28px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 18px;
            padding: 18px 20px;
        }
        .note-box .label { color: #92400e; margin-bottom: 10px; }
        .text-block { white-space: pre-line; color: var(--muted); font-size: 14px; min-height: 110px; }
        .signatures { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 40px; margin-top: 40px; padding-top: 28px; border-top: 1px solid #e2e8f0; }
        .signature-line { margin-top: 56px; border-bottom: 1px solid #64748b; }
        @media (max-width: 800px) {
            .header, .panel-grid, .three-grid, .signatures { grid-template-columns: 1fr; display: grid; }
        }
        @media print {
            body { background: white; }
            .shell { max-width: none; padding: 0; }
            .toolbar { display: none !important; }
            .paper { border: none; border-radius: 0; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    @php
        $acr = $payload['acr'];
        $teacher = $payload['teacher'];
        $scores = $payload['scores'];
        $metrics = $payload['metrics'];
        $narrative = $payload['narrative'];
    @endphp

    <div class="shell">
        <div class="toolbar">
            <a href="{{ route('principal.acr.show', $acr['id']) }}" class="button">Back to Review</a>
            <button type="button" onclick="window.print()" class="button primary">Print ACR</button>
        </div>

        <main class="paper">
            <header class="header">
                <div>
                    <div class="topline">Confidential</div>
                    <h1>{{ $payload['school']['name'] }}</h1>
                    <div class="subtle">Teacher Annual Confidential Report / Annual Performance Review</div>
                </div>
                <div>
                    <div class="badge">{{ $acr['status_label'] }}</div>
                    <div class="subtle" style="margin-top: 12px;">Session: {{ $acr['session'] }}</div>
                    <div class="subtle">Final Grade: {{ $acr['final_grade'] ?: 'Pending review' }}</div>
                    <div class="subtle">Total Score: {{ number_format((float) $acr['total_score'], 2) }}/100</div>
                </div>
            </header>

            <section class="panel-grid">
                <div class="panel">
                    <div class="label">Teacher Details</div>
                    <div class="kv"><strong>Name</strong><span>{{ $teacher['name'] }}</span></div>
                    <div class="kv"><strong>Teacher ID</strong><span>{{ $teacher['teacher_id'] }}</span></div>
                    <div class="kv"><strong>Employee Code</strong><span>{{ $teacher['employee_code'] ?: '-' }}</span></div>
                    <div class="kv"><strong>Designation</strong><span>{{ $teacher['designation'] ?: 'Teacher' }}</span></div>
                    <div class="kv"><strong>Classes</strong><span>{{ !empty($teacher['classes']) ? implode(', ', $teacher['classes']) : '-' }}</span></div>
                </div>

                <div class="panel">
                    <div class="label">Review Audit</div>
                    <div class="kv"><strong>Prepared By</strong><span>{{ $acr['prepared_by'] ?: 'System draft' }}</span></div>
                    <div class="kv"><strong>Reviewed By</strong><span>{{ $acr['reviewed_by'] ?: 'Pending' }}</span></div>
                    <div class="kv"><strong>Reviewed At</strong><span>{{ $acr['reviewed_at'] ? $acr['reviewed_at']->format('d M Y, h:i A') : '-' }}</span></div>
                    <div class="kv"><strong>Finalized At</strong><span>{{ $acr['finalized_at'] ? $acr['finalized_at']->format('d M Y, h:i A') : '-' }}</span></div>
                </div>
            </section>

            <section>
                <h2 style="margin-top: 28px;">Score Breakdown</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th>Metric</th>
                                <th>Score</th>
                                <th>Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scores as $row)
                                <tr>
                                    <td><strong>{{ $row['label'] }}</strong></td>
                                    <td>{{ $row['metric'] }}</td>
                                    <td><strong>{{ number_format((float) $row['score'], 2) }}</strong></td>
                                    <td>{{ number_format((float) $row['weight'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel-grid">
                <div class="panel">
                    <div class="label">Performance Metrics</div>
                    <div class="kv"><strong>Attendance Percentage</strong><span>{{ $metrics['attendance_percentage'] !== null ? number_format((float) $metrics['attendance_percentage'], 2).'%' : 'N/A' }}</span></div>
                    <div class="kv"><strong>Teacher CGPA</strong><span>{{ $metrics['teacher_cgpa'] !== null ? number_format((float) $metrics['teacher_cgpa'], 2) : 'N/A' }}</span></div>
                    <div class="kv"><strong>Pass Percentage</strong><span>{{ $metrics['pass_percentage'] !== null ? number_format((float) $metrics['pass_percentage'], 2).'%' : 'N/A' }}</span></div>
                    <div class="kv"><strong>Student Improvement</strong><span>{{ $metrics['student_improvement_percentage'] !== null ? number_format((float) $metrics['student_improvement_percentage'], 2).'%' : 'Neutral baseline applied' }}</span></div>
                    <div class="kv"><strong>Trainings Attended</strong><span>{{ $metrics['trainings_attended'] }}</span></div>
                </div>

                <div class="panel">
                    <div class="label">Confidential Remarks</div>
                    <div class="text-block">{{ $narrative['confidential_remarks'] ?: 'No confidential remarks were recorded.' }}</div>
                </div>
            </section>

            @if (!empty($metrics['notes']))
                <section class="note-box">
                    <div class="label">Automation Notes</div>
                    @foreach ($metrics['notes'] as $note)
                        <div class="subtle">{{ $note }}</div>
                    @endforeach
                </section>
            @endif

            <section class="three-grid">
                <div class="panel">
                    <div class="label">Strengths</div>
                    <div class="text-block">{{ $narrative['strengths'] ?: 'No strengths recorded.' }}</div>
                </div>
                <div class="panel">
                    <div class="label">Areas for Improvement</div>
                    <div class="text-block">{{ $narrative['areas_for_improvement'] ?: 'No improvement areas recorded.' }}</div>
                </div>
                <div class="panel">
                    <div class="label">Recommendations</div>
                    <div class="text-block">{{ $narrative['recommendations'] ?: 'No recommendations recorded.' }}</div>
                </div>
            </section>

            <section class="signatures">
                <div>
                    <div class="label" style="margin-bottom: 0;">Principal Signature</div>
                    <div class="signature-line"></div>
                </div>
                <div>
                    <div class="label" style="margin-bottom: 0;">Date</div>
                    <div class="signature-line"></div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
