<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Class Result Analyzer</h2>
            <p class="mt-1 text-sm text-slate-500">Advanced analytics for class outcomes, student risk, subject difficulty, and teacher effectiveness.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('results.analyzer') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <x-input-label for="session" value="Session" />
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="exam_type" value="Exam Type" />
                        <select id="exam_type" name="exam_type" class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($examTypes as $examType)
                                <option value="{{ $examType['value'] }}" @selected($selectedExamType === $examType['value'])>{{ $examType['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="class_id" value="Class" />
                        <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @if($classes->isEmpty())
                                <option value="">No class available</option>
                            @endif
                            @foreach($classes as $classRoom)
                                <option value="{{ $classRoom->id }}" @selected((int) $selectedClassId === (int) $classRoom->id)>
                                    {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Analyze Results
                        </button>
                    </div>
                </form>
            </section>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Students</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($analysis['summary']['students'] ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pass %</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($analysis['summary']['pass_percentage'] ?? 0), 2) }}%</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Class Average</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($analysis['summary']['class_average'] ?? 0), 2) }}%</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Highest</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format((float) ($analysis['summary']['highest'] ?? 0), 2) }}%</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lowest</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-700">{{ number_format((float) ($analysis['summary']['lowest'] ?? 0), 2) }}%</p>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Low Risk</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ (int) (($analysis['charts']['student_risk']['values'][0] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Medium Risk</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-800">{{ (int) (($analysis['charts']['student_risk']['values'][1] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">High Risk</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-800">{{ (int) (($analysis['charts']['student_risk']['values'][2] ?? 0)) }}</p>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Subject Difficulty Bar Chart</h3>
                    <p class="mt-1 text-xs text-slate-500">Average percentage by subject. Colors map to Easy, Moderate, and Hard categories.</p>
                    <div class="mt-4 h-80">
                        <canvas id="subjectDifficultyChart"></canvas>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Student Risk Pie Chart</h3>
                    <p class="mt-1 text-xs text-slate-500">Failure risk distribution using score, attendance, and failed subject count.</p>
                    <div class="mt-4 h-80">
                        <canvas id="studentRiskChart"></canvas>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Progress Tracking</h3>
                        <p class="mt-1 text-xs text-slate-500">Student improvement between exams.</p>
                    </div>
                    <div class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        Improvement:
                        <span class="ml-1 {{ (float) ($analysis['progress_tracking']['improvement'] ?? 0) < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                            {{ number_format((float) ($analysis['progress_tracking']['improvement'] ?? 0), 2) }}%
                        </span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach(($analysis['progress_tracking']['items'] ?? []) as $item)
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">{{ number_format((float) ($item['average_percentage'] ?? 0), 2) }}%</p>
                        </article>
                    @endforeach
                </div>

                <div class="mt-5 h-72">
                    <canvas id="studentPerformanceTrendChart"></canvas>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <h3 class="text-base font-semibold text-slate-900">Subject Difficulty Heatmap</h3>
                    <p class="mt-1 text-xs text-slate-500">Difficulty levels are based on average percentage: Easy >= 75, Moderate >= 60, Hard &lt; 60.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Average %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Pass %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Difficulty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Teacher</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse(($analysis['subject_difficulty_heatmap'] ?? []) as $row)
                                <tr class="{{ $row['difficulty'] === 'Hard' ? 'bg-rose-50' : ($row['difficulty'] === 'Moderate' ? 'bg-amber-50' : 'bg-emerald-50') }}">
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $row['subject'] }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['average_percentage'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['pass_percentage'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $row['difficulty'] === 'Hard' ? 'bg-rose-100 text-rose-700' : ($row['difficulty'] === 'Moderate' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                            {{ $row['difficulty'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $row['teacher'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No subject analytics found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <h3 class="text-base font-semibold text-slate-900">Student Failure Prediction</h3>
                    <p class="mt-1 text-xs text-slate-500">Risk score uses average score, attendance percentage, and failed subjects count.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Average %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Attendance %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Failed Subjects</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Risk Score</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Risk Level</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse(($analysis['student_risk_predictions'] ?? []) as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <div class="font-medium">{{ $row['student_name'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $row['student_ref'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['average_score'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['attendance_percentage'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $row['failed_subjects_count'] }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ number_format((float) $row['risk_score'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $row['risk_level'] === 'High' ? 'bg-rose-100 text-rose-700' : ($row['risk_level'] === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                            {{ $row['risk_level'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No student risk data available for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <h3 class="text-base font-semibold text-slate-900">Teacher Effectiveness Ranking</h3>
                    <p class="mt-1 text-xs text-slate-500">Score = (Avg Score * 0.5) + (Pass Rate * 0.3) + (Improvement * 0.2).</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Rank</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Subjects</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Avg Score</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Pass Rate</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Improvement</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse(($analysis['teacher_effectiveness_ranking'] ?? []) as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">#{{ (int) $row['rank'] }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $row['teacher_name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ !empty($row['subjects']) ? implode(', ', $row['subjects']) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['avg_score'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['pass_rate'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm {{ (float) $row['improvement'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                        {{ number_format((float) $row['improvement'], 2) }}%
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-indigo-700">{{ number_format((float) $row['effectiveness_score'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No teacher effectiveness data available for this class and session.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function initAnalyzerCharts() {
            const subjectDifficulty = @json($analysis['charts']['subject_difficulty'] ?? ['labels' => [], 'values' => [], 'colors' => []]);
            const studentRisk = @json($analysis['charts']['student_risk'] ?? ['labels' => ['Low', 'Medium', 'High'], 'values' => [0, 0, 0]]);
            const performanceTrend = @json($analysis['charts']['student_performance_trend'] ?? ['labels' => ['Class Test', 'Bimonthly', 'First Term', 'Final'], 'values' => [0, 0, 0, 0]]);

            const difficultyCtx = document.getElementById('subjectDifficultyChart');
            if (difficultyCtx) {
                new Chart(difficultyCtx, {
                    type: 'bar',
                    data: {
                        labels: subjectDifficulty.labels || [],
                        datasets: [{
                            label: 'Average Percentage',
                            data: subjectDifficulty.values || [],
                            backgroundColor: subjectDifficulty.colors || 'rgba(30, 64, 175, 0.75)',
                            borderColor: subjectDifficulty.colors || 'rgba(30, 64, 175, 1)',
                            borderWidth: 1,
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                            },
                        },
                    },
                });
            }

            const riskCtx = document.getElementById('studentRiskChart');
            if (riskCtx) {
                new Chart(riskCtx, {
                    type: 'pie',
                    data: {
                        labels: studentRisk.labels || [],
                        datasets: [{
                            data: studentRisk.values || [],
                            backgroundColor: ['#16a34a', '#d97706', '#dc2626'],
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                    },
                });
            }

            const trendCtx = document.getElementById('studentPerformanceTrendChart');
            if (trendCtx) {
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: performanceTrend.labels || [],
                        datasets: [{
                            label: 'Average Score %',
                            data: performanceTrend.values || [],
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.15)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                            },
                        },
                    },
                });
            }
        })();
    </script>
</x-app-layout>
