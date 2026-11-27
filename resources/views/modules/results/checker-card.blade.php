<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $student?->name ?? 'Student' }} — Result Card</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f6; color: #172033; font: 14px/1.5 Arial, sans-serif; }
        main, .toolbar { max-width: 1050px; margin: 24px auto; padding: 0 20px; }
        article { padding: 32px; border: 1px solid #d5dce5; border-radius: 12px; background: white; }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #334a6b; padding-bottom: 18px; }
        h1 { font-size: 23px; margin: 0; } h2 { font-size: 18px; margin-top: 28px; } h3 { font-size: 15px; }
        .muted { color: #526079; } .logo { width: 72px; height: 72px; object-fit: contain; }
        .meta, .summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin: 20px 0; }
        .summary > div { background: #f2f5f9; padding: 12px; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #d5dce5; padding: 9px; text-align: left; vertical-align: top; }
        th { background: #edf2f7; } .scroll { overflow-x: auto; }
        .comment, .recommendation { border-left: 3px solid #51709c; background: #f7f9fc; padding: 12px 16px; margin: 12px 0; }
        .comment { white-space: pre-line; } button, .toolbar a { cursor: pointer; padding: 10px 16px; border: 1px solid #334a6b; border-radius: 6px; color: #243b5c; background: white; text-decoration: none; }
        @media (max-width: 640px) { article { padding: 16px; } .meta, .summary { grid-template-columns: 1fr; } }
        @media print { body { background: white; } .toolbar { display: none; } main { padding: 0; margin: 0; max-width: none; } article { border: none; padding: 0; } thead { display: table-header-group; } tr, .recommendation, .comment { break-inside: avoid; } .scroll { overflow: visible; } @page { margin: 14mm; } }
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('result-checker.results.index', ['student_id' => $student?->id, 'student' => $student?->name, 'source' => $published ? 'published' : 'marks']) }}">Back to results</a> <button type="button" onclick="window.print()">Print / Save PDF</button></div>
    <main><article>
        <header>
            <div><h1>{{ $school?->school_name ?? config('app.name') }}</h1><div>Student Result Card</div><div class="muted">{{ $published ? 'Published result snapshot' : 'Recorded assessment — review copy' }}</div></div>
            @if($school?->logo_path)
                <img class="logo" src="{{ Storage::disk('public')->url($school->logo_path) }}" alt="School Logo">
            @endif
        </header>
        <div class="meta">
            <div><strong>Student:</strong> {{ $student?->name ?? 'Unknown student' }}<br><strong>Student ID:</strong> {{ $student?->student_id ?? '—' }}</div>
            <div><strong>Class:</strong> {{ trim(($classRoom?->name ?? '').' '.($classRoom?->section ?? '')) ?: 'Not recorded' }}<br><strong>Session:</strong> {{ $session }}</div>
            <div><strong>Assessment:</strong> {{ $title }}<br><strong>Assessment date:</strong> {{ $date ?? 'Not recorded' }}</div>
            <div><strong>School pass threshold:</strong> {{ number_format($threshold, 2) }}%<br><strong>Prepared:</strong> {{ now()->timezone('Asia/Karachi')->format('d M Y') }}</div>
        </div>
        <div class="scroll"><table>
            <thead><tr><th>Subject</th><th>Teacher</th><th>Marks</th><th>Percentage</th><th>Grade</th><th>School outcome</th></tr></thead>
            <tbody>@foreach($rows as $row)
                <tr><td>{{ $row['subject'] }}</td><td>{{ $row['teacher'] }}</td><td>{{ $row['obtained'] !== null ? $row['obtained'].' / '.$row['total'] : '—' }}</td><td>{{ $row['percentage'] !== null ? number_format($row['percentage'], 2).'%' : '—' }}</td><td>{{ $row['grade'] }}</td><td>{{ $row['status'] }}</td></tr>
            @endforeach</tbody>
        </table></div>
        <div class="summary">
            <div><strong>Total recorded numeric marks</strong><br>{{ $numericCount ? $obtained.' / '.$total : 'Not available' }}</div>
            <div><strong>Weighted numeric percentage</strong><br>{{ $overall !== null ? number_format($overall, 2).'%' : 'Not available' }}</div>
        </div>
        <p class="muted">Summary includes {{ $numericCount }} of {{ $rows->count() }} rows. Grade-only and incomplete records are excluded from numeric calculations. Letter grades and the school pass threshold are separate. This review covers recorded results, not unentered subjects.</p>
        <h2>Student analysis &amp; areas for improvement</h2>
        <p class="muted">Automatic suggestions based on this assessment; these are not teacher-written comments.</p>
        @foreach($rows as $row)
            <div class="recommendation"><strong>{{ $row['subject'] }}</strong><br>{{ $row['suggestion'] }}</div>
        @endforeach
        <h2>{{ $comment ? $commentLabel : 'Teacher comments' }}</h2>
        @if($comment)
            @if($commentAuthor)<p><strong>Recorded by:</strong> {{ $commentAuthor }}</p>@endif
            <div class="comment">{{ $comment }}</div>
            @if($commentGenerated)
                <p class="muted">Generated automatically from the displayed assessment and available class outcomes. These comments are suggestions for teacher review, not teacher-authored or approved statements.</p>
            @else
                <p class="muted">Saved comment for this student, session, and exam type. The comment record is not linked to an individual test or topic.</p>
            @endif
        @else
            <p class="muted">No saved comment is available for this student and assessment type.</p>
        @endif
        <h2>Teacher performance context: class assessment outcomes</h2>
        <p class="muted">Based on available numeric records for the same assessment and source. Small samples, missing entries, and student circumstances affect these figures; they do not establish teaching effectiveness.</p>
        <div class="scroll"><table>
            <thead><tr><th>Subject / Teacher</th><th>Numeric records</th><th>Class average</th><th>Pass rate</th><th>Suggested teaching follow-up</th></tr></thead>
            <tbody>@forelse($teaching as $item)
                <tr><td>{{ $item['subject'] }}<br>{{ $item['teacher'] }}</td><td>{{ $item['count'] }}</td><td>{{ $item['average'] !== null ? number_format($item['average'], 2).'%' : 'Not available' }}</td><td>{{ $item['pass_rate'] !== null ? number_format($item['pass_rate'], 2).'%' : 'Not available' }}</td><td>{{ $item['follow_up'] }}</td></tr>
            @empty
                <tr><td colspan="5">No linked assessment is available for class outcome analysis.</td></tr>
            @endforelse</tbody>
        </table></div>
    </article></main>
</body>
</html>
