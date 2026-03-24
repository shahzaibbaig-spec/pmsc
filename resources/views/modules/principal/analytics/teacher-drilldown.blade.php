<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Teacher Analytics Drilldown</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $payload['teacher']['name'] ?? 'Teacher' }} | Session {{ $selectedSession }}</p>
        </div>
    </x-slot>

    @php
        $summary = $payload['summary'] ?? [];
        $subjectPerformance = $payload['subject_performance'] ?? [];
        $assignedClasses = $payload['assigned_classes'] ?? [];
        $charts = $payload['charts'] ?? [];
        $formatPercent = fn ($value) => $value !== null ? number_format((float) $value, 2).'%' : 'N/A';
    @endphp

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('principal.analytics.dashboard.teacher', ['teacher' => $payload['teacher']['id'] ?? 0]) }}" class="grid grid-cols-1 gap-4 md:grid-cols-6">
                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Classes</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((int) $selectedClassId === (int) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-4 flex flex-wrap items-end gap-3">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Apply
                    </button>
                    <a href="{{ route('principal.analytics.dashboard.teacher', ['teacher' => $payload['teacher']['id'] ?? 0, 'session' => $selectedSession]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                    <a href="{{ route('principal.analytics.dashboard.index', ['session' => $selectedSession, 'class_id' => $selectedClassId]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Back to Dashboard
                    </a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Average Score</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $formatPercent($summary['average_score'] ?? null) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pass %</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $formatPercent($summary['pass_percentage'] ?? null) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ !empty($summary['rank']) ? '#'.$summary['rank'] : 'N/A' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marks Entries</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) ($summary['entries'] ?? 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Classes Covered</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) ($summary['classes_count'] ?? 0)) }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Assigned Classes</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse($assignedClasses as $classRow)
                    <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $classRow['name'] }}</span>
                @empty
                    <span class="text-sm text-slate-500">No assigned classes found for this scope.</span>
                @endforelse
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Exam Comparison</h3>
                <div class="mt-4 h-72">
                    <canvas id="teacherExamComparisonChart"></canvas>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Class-Wise Average</h3>
                <div class="mt-4 h-72">
                    <canvas id="teacherClassBreakdownChart"></canvas>
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Subject Performance by Class</h3>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Avg %</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pass %</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Entries</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($subjectPerformance as $row)
                            <tr>
                                <td class="px-4 py-2 text-sm text-slate-700">{{ $row['class_name'] }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-slate-900">{{ $row['subject_name'] }}</td>
                                <td class="px-4 py-2 text-sm text-slate-700">{{ number_format((float) $row['average_percentage'], 2) }}%</td>
                                <td class="px-4 py-2 text-sm text-slate-700">{{ number_format((float) $row['pass_percentage'], 2) }}%</td>
                                <td class="px-4 py-2 text-sm text-slate-700">{{ number_format((int) $row['entries']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No teacher performance rows found for this scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const examComparison = @json($charts['exam_comparison'] ?? ['labels' => [], 'average_values' => [], 'pass_values' => []]);
        const classBreakdown = @json($charts['class_breakdown'] ?? ['labels' => [], 'values' => []]);

        const examCanvas = document.getElementById('teacherExamComparisonChart');
        if (examCanvas) {
            new Chart(examCanvas, {
                type: 'bar',
                data: {
                    labels: examComparison.labels || [],
                    datasets: [
                        {
                            label: 'Average %',
                            data: examComparison.average_values || [],
                            backgroundColor: 'rgba(5,150,105,0.75)',
                            borderRadius: 8
                        },
                        {
                            label: 'Pass %',
                            data: examComparison.pass_values || [],
                            backgroundColor: 'rgba(217,119,6,0.75)',
                            borderRadius: 8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, suggestedMax: 100 } }
                }
            });
        }

        const classCanvas = document.getElementById('teacherClassBreakdownChart');
        if (classCanvas) {
            new Chart(classCanvas, {
                type: 'line',
                data: {
                    labels: classBreakdown.labels || [],
                    datasets: [{
                        label: 'Average %',
                        data: classBreakdown.values || [],
                        borderColor: '#4338ca',
                        backgroundColor: 'rgba(67,56,202,0.16)',
                        borderWidth: 2,
                        tension: 0.25,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, suggestedMax: 100 } }
                }
            });
        }
    </script>
</x-app-layout>
