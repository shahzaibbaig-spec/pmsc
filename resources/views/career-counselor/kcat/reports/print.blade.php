<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>KCAT Report</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 32px; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .card { border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
        .label { color: #1d4ed8; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .signature { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; margin-top: 60px; }
        .line { border-top: 1px solid #334155; padding-top: 8px; }
        @media print { button { display: none; } body { margin: 18mm; } }
    </style>
</head>
<body>
@php($attempt = $report['attempt'])
<button onclick="window.print()">Print</button>
<div class="header">
    <h1>KORT Cognitive Assessment Test Report</h1>
    <p>{{ $attempt->student?->name }} | {{ trim(($attempt->student?->classRoom?->name ?? '').' '.($attempt->student?->classRoom?->section ?? '')) }} | {{ $attempt->test?->title }}</p>
</div>
<div class="grid">
    <div class="card"><div class="label">Overall</div><h2>{{ $attempt->percentage ?? 0 }}%</h2></div>
    <div class="card"><div class="label">Band</div><h2>{{ str_replace('_', ' ', $attempt->band ?? '-') }}</h2></div>
    <div class="card"><div class="label">Stream</div><h2>{{ $attempt->recommended_stream ?? '-' }}</h2></div>
    <div class="card"><div class="label">Date</div><h2>{{ optional($attempt->submitted_at)->format('d M Y') ?? '-' }}</h2></div>
</div>
@foreach ($report['scores'] as $score)
    <div class="card"><strong>{{ $score->section?->name ?? str_replace('_', ' ', $score->section_code) }}</strong>: {{ $score->percentage }}% ({{ $score->raw_score }} / {{ $score->total_marks }})</div>
@endforeach
<div class="card"><div class="label">Recommendation Summary</div><p>{{ $attempt->recommendation_summary }}</p></div>
<div class="card"><div class="label">Counselor Notes</div><p>{{ $report['note']?->counselor_recommendation ?? '-' }}</p></div>
<div class="signature"><div class="line">Career Counselor Signature</div><div class="line">Principal Signature</div></div>
</body>
</html>
