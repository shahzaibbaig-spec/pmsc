<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Timetable</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 16px; color: #111827; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .logo { width: 60px; height: 60px; object-fit: contain; }
        .title { font-size: 20px; font-weight: 700; margin: 0; }
        .sub { font-size: 12px; margin-top: 3px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { border: 1px solid #d1d5db; padding: 6px; font-size: 11px; }
        .table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 5px; font-size: 10px; vertical-align: top; }
        .table th { background: #f3f4f6; text-align: left; }
        .cell-title { font-weight: 700; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <p class="title">{{ $school['name'] ?? 'School Management System' }}</p>
            <p class="sub">Teacher Timetable</p>
        </div>
        <div>
            @if(!empty($school['logo_absolute_path']))
                <img class="logo" src="{{ $school['logo_absolute_path'] }}" alt="School Logo">
            @endif
        </div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Session:</strong> {{ $report['session'] }}</td>
            <td><strong>Teacher:</strong> {{ $report['teacher']['name'] ?? '-' }}</td>
            <td><strong>Date:</strong> {{ now()->toDateString() }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 80px;">Day</th>
                @foreach($report['slot_headers'] as $slot)
                    <th>
                        Slot {{ $slot['slot_index'] }}<br>
                        <span class="muted">{{ $slot['start_time'] }} - {{ $slot['end_time'] }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td><strong>{{ $row['day_label'] }}</strong></td>
                    @foreach($row['cells'] as $cell)
                        <td>
                            @if(!empty($cell['entry']))
                                <div class="cell-title">{{ $cell['entry']['subject_name'] }}</div>
                                <div>{{ $cell['entry']['class_section'] }}</div>
                                <div>{{ $cell['entry']['room_name'] }}</div>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['slot_headers']) + 1 }}" style="text-align:center;">No timetable entries found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
