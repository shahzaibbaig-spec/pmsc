<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBC Report #{{ $report->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; color: #111; margin: 0; }
        .page { max-width: 900px; margin: 20px auto; padding: 24px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1f2937; padding-bottom: 12px; margin-bottom: 16px; }
        .logo { width: 72px; height: 72px; object-fit: contain; }
        .title { margin: 0; font-size: 24px; font-weight: 700; }
        .subtitle { margin: 4px 0 0; font-size: 13px; color: #4b5563; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { border: 1px solid #d1d5db; padding: 8px; font-size: 13px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 8px; font-size: 13px; text-align: left; }
        .table th { background: #f3f4f6; }
        .remarks { margin-top: 14px; font-size: 13px; }
        .signature { margin-top: 32px; display: flex; justify-content: flex-end; }
        .signature .box { width: 240px; text-align: center; }
        .signature .line { border-top: 1px solid #111; margin-bottom: 6px; }
        .print-bar { margin: 10px auto 0; max-width: 900px; text-align: right; }
        .print-btn { background: #2563eb; border: 0; color: #fff; padding: 8px 14px; border-radius: 6px; cursor: pointer; }

        @media print {
            .print-bar { display: none !important; }
            .page { margin: 0; max-width: none; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>
    <div class="page">
        <div class="header">
            <div>
                <p class="title">{{ $school['name'] ?? 'School Management System' }}</p>
                <p class="subtitle">Clinic CBC Blood Report</p>
            </div>
            <div>
                @if(!empty($school['logo_absolute_path']))
                    <img src="{{ $school['logo_absolute_path'] }}" alt="School Logo" class="logo">
                @endif
            </div>
        </div>

        <table class="meta">
            <tr>
                <td><strong>Student:</strong> {{ $report->student?->name ?? '-' }}</td>
                <td><strong>Admission #:</strong> {{ $report->student?->student_id ?? '-' }}</td>
                <td><strong>Class/Section:</strong> {{ trim(($report->student?->classRoom?->name ?? '').' '.($report->student?->classRoom?->section ?? '')) ?: '-' }}</td>
            </tr>
            <tr>
                <td><strong>Age:</strong> {{ $report->student?->age ?? '-' }}</td>
                <td><strong>Gender:</strong> {{ data_get($report, 'student.gender', '-') }}</td>
                <td><strong>Doctor:</strong> {{ $report->doctor?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Report Date:</strong> {{ optional($report->report_date)->format('Y-m-d') }}</td>
                <td><strong>Machine Report #:</strong> {{ $report->machine_report_no ?: '-' }}</td>
                <td><strong>Session:</strong> {{ $report->session }}</td>
            </tr>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    'Hemoglobin' => $report->hemoglobin,
                    'RBC Count' => $report->rbc_count,
                    'WBC Count' => $report->wbc_count,
                    'Platelet Count' => $report->platelet_count,
                    'Hematocrit (PCV)' => $report->hematocrit_pcv,
                    'MCV' => $report->mcv,
                    'MCH' => $report->mch,
                    'MCHC' => $report->mchc,
                    'Neutrophils' => $report->neutrophils,
                    'Lymphocytes' => $report->lymphocytes,
                    'Monocytes' => $report->monocytes,
                    'Eosinophils' => $report->eosinophils,
                    'Basophils' => $report->basophils,
                    'ESR' => $report->esr,
                ] as $label => $value)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $value !== null ? $value : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="remarks">
            <strong>Remarks:</strong>
            <div>{{ $report->remarks ?: '-' }}</div>
        </div>

        <div class="signature">
            <div class="box">
                <div class="line"></div>
                <div>Doctor Signature</div>
            </div>
        </div>
    </div>
</body>
</html>
