<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Class Test Sheet</h2>
        <p class="mt-1 text-sm text-slate-500">All class tests across all subjects for one student or the whole class.</p>
    </x-slot>

    <div class="space-y-6" x-data="{ showPercentages: @js($filters['display'] === 'percentages') }">
        <form method="GET" action="{{ route('principal.results.class-tests') }}" class="sheet-filters grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-5">
            <input type="hidden" name="display" value="{{ $filters['display'] }}" :value="showPercentages ? 'percentages' : 'marks'">
            <div>
                <label for="sheet_class" class="block text-sm font-medium">Class</label>
                <select id="sheet_class" name="class_id" required onchange="this.form.student_id.value = ''; this.form.month.value = ''; this.form.submit()" class="mt-1 w-full rounded-lg border-slate-300">
                    @foreach($classes as $classRoom)
                        <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>{{ trim($classRoom->name.' '.$classRoom->section) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sheet_session" class="block text-sm font-medium">Session</label>
                <select id="sheet_session" name="session" onchange="this.form.student_id.value = ''; this.form.month.value = ''; this.form.submit()" class="mt-1 w-full rounded-lg border-slate-300">
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
            <div>
                <label for="sheet_month" class="block text-sm font-medium">Month</label>
                <select id="sheet_month" name="month" class="mt-1 w-full rounded-lg border-slate-300">
                    <option value="">All tests</option>
                    @foreach($report['months'] ?? [] as $month)
                        <option value="{{ $month['value'] }}" @selected($filters['month'] === $month['value'])>{{ $month['label'] }}</option>
                    @endforeach
                    @if($filters['month'] && $report && !$report['months']->contains('value', $filters['month']))
                        <option value="{{ $filters['month'] }}" selected>{{ $report['periodLabel'] }}</option>
                    @endif
                </select>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">View Sheet</button>
                @if($report && $report['columns']->isNotEmpty())
                    <button type="button" onclick="window.print()" class="rounded-lg border px-4 py-2 text-sm font-semibold">Print / Save PDF</button>
                @endif
            </div>
        </form>

        <section class="class-test-report rounded-2xl border bg-white p-5 shadow-sm">
            @if($report)
                <p class="mb-2 font-semibold text-indigo-700">{{ $report['periodLabel'] }}</p>
                <h3 class="text-lg font-semibold">{{ trim($report['class']->name.' '.$report['class']->section) }} · {{ $report['session'] }} · Class Tests</h3>
                <p class="my-2 text-sm text-slate-500">{{ $report['rows']->count() }} student(s) · {{ $report['columns']->count() }} test(s). — means marks have not been entered. Totals include recorded numeric results only.</p>
                @if($report['columns']->isEmpty())
                    <p class="py-6 text-sm text-slate-500">No class tests found for the selected class, session and month.</p>
                @else
                    <div class="test-display-toggle mb-4 flex flex-wrap items-center gap-3">
                        <span class="text-sm font-medium" id="test-display-label">Individual tests: Marks / Percentages</span>
                        <button type="button" role="switch" aria-labelledby="test-display-label"
                            :aria-checked="showPercentages.toString()" @click="showPercentages = !showPercentages"
                            class="test-display-switch" :class="{ 'is-on': showPercentages }">
                            <span aria-hidden="true"></span>
                        </button>
                    </div>
                    <p class="mb-2 text-sm font-semibold" x-text="showPercentages ? 'Individual tests shown as percentages' : 'Individual tests shown as marks'">Individual tests shown as {{ $filters['display'] === 'percentages' ? 'percentages' : 'marks' }}</p>
                    <p class="mb-3 text-xs text-slate-500">Grade results stay as grades. N/A means the test total is zero, so a percentage cannot be calculated.</p>
                    <p class="mb-4 text-sm text-slate-500">
                        Progress compares {{ $filters['month'] ? 'the selected month with the previous calendar month' : 'each student’s latest two months with recorded numeric results' }}.
                        Each test is converted to a percentage, then averaged within its subject and month. Overall change is the average change across subjects present in both months, in percentage points (pp).
                        Undated tests and grade-only results are excluded from progress. Undated tests appear under All tests only.
                    </p>
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
                                    <th rowspan="2" class="border p-3">Progress</th>
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
                                        @foreach($row['cells'] as $index => $cell)
                                            <td class="whitespace-nowrap border p-3 text-center"
                                                x-text="showPercentages ? @js($row['percentage_cells'][$index]) : @js($cell)">{{ $filters['display'] === 'percentages' ? $row['percentage_cells'][$index] : $cell }}</td>
                                        @endforeach
                                        <td class="border p-3 text-center">{{ $row['recorded'] }} / {{ $report['columns']->count() }}</td>
                                        <td class="border p-3 text-center">{{ $row['total'] > 0 ? $row['obtained'].' / '.$row['total'] : '—' }}</td>
                                        <td class="border p-3 text-center">{{ $row['percentage'] !== null ? number_format($row['percentage'], 2).'%' : '—' }}</td>
                                        <td class="min-w-48 border p-3">
                                            @php($progress = $row['progress'])
                                            <span @class(['font-semibold', 'text-emerald-700' => $progress['change'] > 0, 'text-rose-700' => $progress['change'] < 0, 'text-slate-600' => $progress['change'] === null || $progress['change'] == 0])>{{ $progress['status'] }}</span>
                                            @if($progress['change'] !== null)
                                                <span class="block font-semibold">{{ $progress['change'] > 0 ? '+' : '' }}{{ number_format($progress['change'], 2) }} pp</span>
                                            @endif
                                            @if($progress['previous'] && $progress['current'])
                                                <span class="block text-xs text-slate-500">{{ $progress['previous'] }} → {{ $progress['current'] }}</span>
                                            @endif
                                            @if(count($progress['subjects']))
                                                <details class="mt-2 text-xs">
                                                    <summary class="cursor-pointer">{{ count($progress['subjects']) }} comparable subject(s)</summary>
                                                    @foreach($progress['subjects'] as $subject)
                                                        <div class="mt-2">{{ $subject['name'] }}: {{ number_format($subject['before'], 2) }}% → {{ number_format($subject['after'], 2) }}%
                                                            ({{ $subject['change'] > 0 ? '+' : '' }}{{ number_format($subject['change'], 2) }} pp)
                                                        </div>
                                                    @endforeach
                                                </details>
                                            @else
                                                <span class="block text-xs text-slate-500">Needs numeric results for the same subject in both months.</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $report['columns']->count() + 5 }}" class="p-6 text-slate-500">No students found for this class and session.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
                @if($report['analysis'])
                    <section class="student-analysis mt-6 border-t border-slate-200 pt-5" aria-labelledby="student-analysis-title">
                        <h3 id="student-analysis-title" class="text-lg font-semibold">Teacher comments and intervention plan</h3>
                        <p class="mt-1 text-sm font-semibold">{{ $report['rows']->first()['student']->name }} · {{ $report['periodLabel'] }}</p>
                        <p class="mt-2 text-sm text-slate-700">{{ $report['analysis']['summary'] }}</p>
                        <p class="mt-2 text-xs text-slate-500">Planning guide: below 50% suggests priority support; 50–69.99% suggests targeted practice. These are support prompts, not pass/fail rules. Subject averages give each test equal weight. Test scores highlight areas to investigate; confirm specific learning gaps from the marked work and classroom observation.</p>
                        @foreach($report['analysis']['subjects'] as $subject)
                            <article class="student-analysis-subject mt-4 rounded-lg border border-slate-200 p-4">
                                <h4 class="font-semibold">{{ $subject['name'] }} — {{ $subject['priority'] }}</h4>
                                <p class="mt-2 text-sm"><strong>Comment:</strong> {{ $subject['comment'] }}</p>
                                @if(count($subject['topics']))
                                    <p class="mt-2 text-sm"><strong>Tests / topics to review:</strong> {{ implode('; ', $subject['topics']) }}. These test-level scores do not identify which individual skills were missed.</p>
                                @endif
                                <p class="mt-2 text-sm"><strong>Area of intervention:</strong> {{ $subject['intervention'] }}</p>
                                <p class="mt-2 text-sm"><strong>Teacher strategy:</strong> {{ $subject['strategy'] }}</p>
                            </article>
                        @endforeach
                    </section>
                @endif
            @else
                <p>No classes available.</p>
            @endif
        </section>
    </div>
    <style>
        .test-display-switch { width: 44px; height: 24px; padding: 3px; border-radius: 999px; background: #64748b; transition: background .15s; }
        .test-display-switch span { display: block; width: 18px; height: 18px; border-radius: 50%; background: white; transition: transform .15s; }
        .test-display-switch.is-on { background: #4f46e5; }
        .test-display-switch.is-on span { transform: translateX(20px); }
        .test-display-switch:focus-visible { outline: 2px solid #4f46e5; outline-offset: 3px; }
        @media print {
            .test-display-toggle { display: none; }
            @page { size: landscape; margin: 10mm; }
            body * { visibility: hidden; }
            .class-test-report, .class-test-report * { visibility: visible; }
            .class-test-report { position: absolute; left: 0; top: 0; width: 100%; border: 0; padding: 0; box-shadow: none; }
            .sheet-table { overflow: visible; }
            .class-test-report table { font-size: 9px; }
            .class-test-report th, .class-test-report td { min-width: 0; padding: 4px; }
            .student-analysis-subject { break-inside: avoid; }
        }
    </style>
</x-app-layout>
