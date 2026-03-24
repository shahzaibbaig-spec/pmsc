<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Class Analytics Drilldown</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $payload['class']['name'] ?? 'Class' }} | Session {{ $selectedSession }}</p>
        </div>
    </x-slot>

    @php
        $kpis = $payload['kpis'] ?? [];
        $topPerformers = $payload['top_performers'] ?? [];
        $weakStudents = $payload['weak_students'] ?? [];
        $subjectPerformance = $payload['subject_performance'] ?? [];
        $teacherPerformance = $payload['teacher_performance'] ?? [];
        $classRank = $payload['class_rank'] ?? ['position' => null, 'total_classes' => 0];
        $charts = $payload['charts'] ?? [];

        $formatPercent = fn ($value) => $value !== null ? number_format((float) $value, 2).'%' : 'N/A';
        $riskClasses = [
            'high' => 'bg-rose-100 text-rose-700',
            'medium' => 'bg-amber-100 text-amber-700',
            'low' => 'bg-emerald-100 text-emerald-700',
        ];
    @endphp

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('principal.analytics.dashboard.class', ['schoolClass' => $payload['class']['id'] ?? 0]) }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3 flex flex-wrap items-end gap-3">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Refresh
                    </button>
                    <a href="{{ route('principal.analytics.dashboard.index', ['session' => $selectedSession, 'class_id' => $payload['class']['id'] ?? null]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Back to Dashboard
                    </a>
                    <p class="text-xs text-slate-500">
                        Class Rank:
                        <span class="font-semibold text-slate-700">
                            {{ $classRank['position'] ? '#'.$classRank['position'].' / '.$classRank['total_classes'] : 'N/A' }}
                        </span>
                    </p>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Students</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) ($kpis['total_students'] ?? 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pass Rate</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $formatPercent($kpis['pass_rate'] ?? null) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Avg Attendance</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $formatPercent($kpis['average_attendance'] ?? null) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Avg Result %</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $formatPercent($kpis['average_result_percentage'] ?? null) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fee Defaulters</p>
                <p class="mt-2 text-2xl font-semibold text-rose-700">{{ number_format((int) ($kpis['fee_defaulters'] ?? 0)) }}</p>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Attendance Trend</h3>
                <div class="mt-4 h-72">
                    <canvas id="classAttendanceTrendChart"></canvas>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Exam Comparison</h3>
                <div class="mt-4 h-72">
                    <canvas id="classExamComparisonChart"></canvas>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Top Performers</h3>
                </header>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">%</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($topPerformers as $row)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-medium text-slate-900">{{ $row['student_name'] }}</td>
                                    <td class="px-4 py-2 text-sm text-slate-700">{{ number_format((float) $row['percentage'], 2) }}%</td>
                                    <td class="px-4 py-2 text-sm text-slate-700">#{{ $row['rank'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Weak / At-Risk Students</h3>
                </header>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Result %</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Attendance %</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Risk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($weakStudents as $row)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-slate-700">{{ $row['student_name'] }}</td>
                                    <td class="px-4 py-2 text-sm text-slate-700">{{ $row['result_percentage'] !== null ? number_format((float) $row['result_percentage'], 2).'%' : 'N/A' }}</td>
                                    <td class="px-4 py-2 text-sm text-slate-700">{{ $row['attendance_percentage'] !== null ? number_format((float) $row['attendance_percentage'], 2).'%' : 'N/A' }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $riskClasses[$row['risk_level']] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ ucfirst($row['risk_level']) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No at-risk students.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Subject Performance</h3>
                </header>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Avg %</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pass %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($subjectPerformance as $row)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-medium text-slate-900">{{ $row['subject_name'] }}</td>
                                    <td class="px-4 py-2 text-sm text-slate-700">{{ $row['average_percentage'] !== null ? number_format((float) $row['average_percentage'], 2).'%' : 'N/A' }}</td>
                                    <td class="px-4 py-2 text-sm text-slate-700">{{ $row['pass_percentage'] !== null ? number_format((float) $row['pass_percentage'], 2).'%' : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No subject metrics available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Teacher Performance</h3>
                </header>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Avg %</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pass %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($teacherPerformance as $row)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-slate-700">
                                        <a href="{{ route('principal.analytics.dashboard.teacher', ['teacher' => $row['teacher_id'], 'session' => $selectedSession, 'class_id' => $payload['class']['id'] ?? null]) }}" class="font-medium text-indigo-700 hover:text-indigo-900">
                                            {{ $row['teacher_name'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-700">{{ $row['average_score'] !== null ? number_format((float) $row['average_score'], 2).'%' : 'N/A' }}</td>
                                    <td class="px-4 py-2 text-sm text-slate-700">{{ $row['pass_percentage'] !== null ? number_format((float) $row['pass_percentage'], 2).'%' : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No teacher metrics available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const attendanceTrend = @json($charts['attendance_trend'] ?? ['labels' => [], 'values' => []]);
        const examComparison = @json($charts['exam_comparison'] ?? ['labels' => [], 'average_values' => [], 'pass_values' => []]);

        const attendanceCanvas = document.getElementById('classAttendanceTrendChart');
        if (attendanceCanvas) {
            new Chart(attendanceCanvas, {
                type: 'line',
                data: {
                    labels: attendanceTrend.labels || [],
                    datasets: [{
                        label: 'Attendance %',
                        data: attendanceTrend.values || [],
                        borderColor: '#0891b2',
                        backgroundColor: 'rgba(8,145,178,0.15)',
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

        const examCanvas = document.getElementById('classExamComparisonChart');
        if (examCanvas) {
            new Chart(examCanvas, {
                type: 'bar',
                data: {
                    labels: examComparison.labels || [],
                    datasets: [
                        {
                            label: 'Average %',
                            data: examComparison.average_values || [],
                            backgroundColor: 'rgba(30,64,175,0.75)',
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
    </script>
</x-app-layout>
