<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Class Test Sheet</h2>
        <p class="mt-1 text-sm text-slate-500">All class tests across all subjects for one student or the whole class.</p>
    </x-slot>

    <div class="space-y-6">
        <form method="GET" action="{{ route('principal.results.class-tests') }}" class="sheet-filters grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-4">
            <div>
                <label for="sheet_class" class="block text-sm font-medium">Class</label>
                <select id="sheet_class" name="class_id" required onchange="this.form.student_id.value = ''; this.form.submit()" class="mt-1 w-full rounded-lg border-slate-300">
                    @foreach($classes as $classRoom)
                        <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>{{ trim($classRoom->name.' '.$classRoom->section) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sheet_session" class="block text-sm font-medium">Session</label>
                <select id="sheet_session" name="session" onchange="this.form.student_id.value = ''; this.form.submit()" class="mt-1 w-full rounded-lg border-slate-300">
                    @foreach($sessions as $session)
                        <option @selected($filters['session'] === $session)>{{ $session }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sheet_student" class="block text-sm font-medium">Student</label>
                <select id="sheet_student" name="student_id" class="mt-1 w-full rounded-lg border-slate-300">
                    <option value="">Whole class</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected((string) $filters['student_id'] === (string) $student->id)>{{ $student->name }} ({{ $student->student_id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">View Sheet</button>
                @if($report && $report['columns']->isNotEmpty())
                    <button type="button" onclick="window.print()" class="rounded-lg border px-4 py-2 text-sm font-semibold">Print / Save PDF</button>
                @endif
            </div>
        </form>

        <section class="class-test-report rounded-2xl border bg-white p-5 shadow-sm">
            @if($report)
                <h3 class="text-lg font-semibold">{{ trim($report['class']->name.' '.$report['class']->section) }} · {{ $report['session'] }} · Class Tests</h3>
                <p class="my-2 text-sm text-slate-500">{{ $report['rows']->count() }} student(s) · {{ $report['columns']->count() }} test(s). — means marks have not been entered. Totals include recorded numeric results only.</p>
                @if($report['columns']->isEmpty())
                    <p class="py-6 text-sm text-slate-500">No class tests found for this class and session.</p>
                @else
                    <div class="sheet-table overflow-x-auto">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="border p-3 text-left">Student</th>
                                    @foreach($report['columns']->groupBy('subject') as $subject => $tests)
                                        <th colspan="{{ $tests->count() }}" class="border p-3 text-center">{{ $subject }}</th>
                                    @endforeach
                                    <th rowspan="2" class="border p-3">Recorded tests</th>
                                    <th rowspan="2" class="border p-3">Numeric total</th>
                                    <th rowspan="2" class="border p-3">Percentage</th>
                                </tr>
                                <tr class="bg-slate-50">
                                    <th class="border p-3 text-left">Name / Student ID</th>
                                    @foreach($report['columns'] as $column)
                                        <th class="min-w-36 border p-3 text-center">
                                            {{ $column['label'] }}
                                            @if($column['topic'] && !str_contains($column['label'], $column['topic']))
                                                <span class="block text-xs font-normal">{{ $column['topic'] }}</span>
                                            @endif
                                            <span class="block text-xs font-normal">{{ $column['date'] ?? 'Date not set' }} · #{{ $column['id'] }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['rows'] as $row)
                                    <tr>
                                        <td class="whitespace-nowrap border p-3">{{ $row['student']->name }}<span class="block text-xs text-slate-500">{{ $row['student']->student_id }}</span></td>
                                        @foreach($row['cells'] as $cell)
                                            <td class="whitespace-nowrap border p-3 text-center">{{ $cell }}</td>
                                        @endforeach
                                        <td class="border p-3 text-center">{{ $row['recorded'] }} / {{ $report['columns']->count() }}</td>
                                        <td class="border p-3 text-center">{{ $row['total'] > 0 ? $row['obtained'].' / '.$row['total'] : '—' }}</td>
                                        <td class="border p-3 text-center">{{ $row['percentage'] !== null ? number_format($row['percentage'], 2).'%' : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $report['columns']->count() + 4 }}" class="p-6 text-slate-500">No students found for this class and session.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <p>No classes available.</p>
            @endif
        </section>
    </div>
    <style>
        @media print {
            @page { size: landscape; margin: 10mm; }
            body * { visibility: hidden; }
            .class-test-report, .class-test-report * { visibility: visible; }
            .class-test-report { position: absolute; left: 0; top: 0; width: 100%; border: 0; padding: 0; box-shadow: none; }
            .sheet-table { overflow: visible; }
            .class-test-report table { font-size: 9px; }
            .class-test-report th, .class-test-report td { min-width: 0; padding: 4px; }
        }
    </style>
</x-app-layout>
