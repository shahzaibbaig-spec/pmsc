<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Class-wise Student List</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background: #f8fafc;
        }
        .shell {
            max-width: 1200px;
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
            border-radius: 18px;
            padding: 22px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }
        .logo {
            max-height: 68px;
            max-width: 90px;
            object-fit: contain;
        }
        .school-name {
            margin: 0;
            font-size: 24px;
        }
        .title {
            margin: 6px 0 0;
            font-size: 15px;
            color: #334155;
        }
        .meta {
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            font-size: 13px;
            color: #334155;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
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
            font-size: 11px;
        }
        .signature {
            margin-top: 36px;
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
        $schoolName = (string) ($school['name'] ?? config('app.name', 'School Management System'));
        $logoPath = $school['logo_absolute_path'] ?? $school['logo_url'] ?? null;
        $statusLabel = ucfirst((string) ($filters['status'] ?? 'all'));
        $classLabel = (string) ($filters['class_name'] ?? 'All Classes');
        $sectionLabel = (string) ($filters['section'] ?? 'All Sections');
    @endphp

    <div class="shell">
        <div class="toolbar">
            <button type="button" onclick="window.print()" class="btn">Print</button>
            <a href="{{ route('principal.student-lists.index', request()->query()) }}" class="btn">Back</a>
        </div>

        <section class="sheet">
            <header class="header">
                <div class="flex" style="display:flex;align-items:center;gap:12px;">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" alt="School Logo" class="logo">
                    @endif
                    <div>
                        <h1 class="school-name">{{ $schoolName }}</h1>
                        <p class="title">Class-wise Student List</p>
                    </div>
                </div>
                <div style="font-size:12px;color:#334155;text-align:right;">
                    <div><strong>Generated:</strong> {{ $generated_at->format('d M Y h:i A') }}</div>
                    <div><strong>Total Students:</strong> {{ number_format($total) }}</div>
                </div>
            </header>

            <div class="meta">
                <div><strong>Session:</strong> {{ $filters['session'] ?? '-' }}</div>
                <div><strong>Status:</strong> {{ $statusLabel }}</div>
                <div><strong>Class:</strong> {{ $classLabel }}</div>
                <div><strong>Section:</strong> {{ $sectionLabel }}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Sr #</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Father Name</th>
                        <th>Class/Section</th>
                        <th>Contact</th>
                        <th>Age / DOB</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['sr_no'] }}</td>
                            <td>{{ $row['student_id'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['father_name'] ?: '-' }}</td>
                            <td>{{ $row['class_section'] ?: '-' }}</td>
                            <td>{{ $row['contact'] ?: '-' }}</td>
                            <td>
                                {{ $row['age'] !== null ? $row['age'] : '-' }}
                                @if (! empty($row['date_of_birth']))
                                    / {{ $row['date_of_birth'] }}
                                @endif
                            </td>
                            <td>{{ ucfirst((string) $row['status']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:22px;color:#475569;">No students found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="signature">
                <div>
                    <div style="font-size:12px;color:#334155;font-weight:700;">Prepared By</div>
                    <div class="signature-line"></div>
                </div>
                <div>
                    <div style="font-size:12px;color:#334155;font-weight:700;">Principal Signature</div>
                    <div class="signature-line"></div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
