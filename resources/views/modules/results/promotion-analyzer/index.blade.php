<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Promotion &amp; Stream Recommendation</h2>
            <p class="mt-1 text-sm text-slate-500">Final term promotion status and stream recommendation for each student.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8" x-data="promotionAnalyzerPage()">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('results.promotion-analyzer') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <x-input-label for="session" value="Session" />
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
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

                    <div class="md:col-span-2 flex items-end">
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Analyze Promotion
                        </button>
                    </div>
                </form>
            </section>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Students</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($analysis['summary']['students'] ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Promote</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ (int) ($analysis['summary']['promote'] ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Conditional</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-800">{{ (int) ($analysis['summary']['conditional'] ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Repeat</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-800">{{ (int) ($analysis['summary']['repeat'] ?? 0) }}</p>
                </article>
            </section>

            @if(empty($analysis['has_final_term_results']))
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm">
                    Final term result data is not available for this class and session.
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <h3 class="text-base font-semibold text-slate-900">Promotion Analyzer Table</h3>
                    <p class="mt-1 text-xs text-slate-500">Recommendations are generated from final term marks and stream-scoring weights.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Average %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Failed Subjects</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Promotion Recommendation</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Stream Recommendation</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse(($analysis['rows'] ?? []) as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <div class="font-medium">{{ $row['student_name'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $row['student_ref'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ number_format((float) $row['average_percentage'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $row['failed_subjects_count'] }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $row['promotion_recommendation'] === 'Promote' ? 'bg-emerald-100 text-emerald-700' : ($row['promotion_recommendation'] === 'Conditional' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                            {{ $row['promotion_recommendation'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-indigo-700">{{ $row['stream_recommendation'] }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                            @click='openDetail(@js($row["detail"]))'
                                        >
                                            View Detail
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No students or final term records found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Stream Distribution</h3>
                <p class="mt-1 text-xs text-slate-500">Distribution of recommended streams for selected class and session.</p>
                <div class="mt-4 h-80">
                    <canvas id="streamDistributionChart"></canvas>
                </div>
            </section>
        </div>

        <div
            x-cloak
            x-show="detailOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-transition.opacity
        >
            <div class="absolute inset-0 bg-slate-900/50" @click="closeDetail()"></div>
            <div class="relative z-10 w-full max-w-3xl rounded-2xl border border-slate-200 bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Student Recommendation Detail</h3>
                        <p class="text-xs text-slate-500">
                            <span x-text="detail.student_name || '-'"></span>
                            <span class="mx-1">|</span>
                            <span x-text="detail.student_ref || '-'"></span>
                        </p>
                    </div>
                    <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100" @click="closeDetail()">✕</button>
                </div>

                <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Average %</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900" x-text="formatPercent(detail.average_percentage)"></p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Failed Subjects</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900" x-text="detail.failed_subjects_count ?? 0"></p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Promotion</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900" x-text="detail.promotion_recommendation || '-'"></p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-semibold text-slate-900">Promotion Reasoning</p>
                        <p class="mt-1 text-sm text-slate-600" x-text="detail.promotion_reason || '-'"></p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-semibold text-slate-900">Failed Subjects</p>
                        <template x-if="(detail.failed_subjects || []).length === 0">
                            <p class="mt-1 text-sm text-emerald-700">No failed subjects.</p>
                        </template>
                        <ul class="mt-1 space-y-1 text-sm text-slate-700" x-show="(detail.failed_subjects || []).length > 0">
                            <template x-for="item in (detail.failed_subjects || [])" :key="item">
                                <li x-text="item"></li>
                            </template>
                        </ul>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-semibold text-slate-900">Stream Scores</p>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <template x-for="[stream, score] in Object.entries(detail.stream_scores || {})" :key="stream">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                                    <p class="text-xs font-semibold text-slate-500" x-text="stream"></p>
                                    <p class="text-sm font-semibold text-slate-900" x-text="formatPercent(score)"></p>
                                </div>
                            </template>
                        </div>
                        <p class="mt-3 text-sm">
                            Recommended Stream:
                            <span class="font-semibold text-indigo-700" x-text="detail.stream_recommendation || '-'"></span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-semibold text-slate-900">Subject Percentages</p>
                        <div class="mt-2 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Subject</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">%</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <template x-for="item in (detail.subject_percentages || [])" :key="item.subject">
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-slate-800" x-text="item.subject"></td>
                                            <td class="px-3 py-2 text-sm text-slate-700" x-text="formatPercent(item.percentage)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        function promotionAnalyzerPage() {
            return {
                detailOpen: false,
                detail: {},
                openDetail(payload) {
                    this.detail = payload || {};
                    this.detailOpen = true;
                },
                closeDetail() {
                    this.detailOpen = false;
                    this.detail = {};
                },
                formatPercent(value) {
                    const num = Number(value || 0);
                    return `${num.toFixed(2)}%`;
                },
            };
        }

        (function initStreamChart() {
            const chartData = @json($analysis['chart'] ?? ['labels' => [], 'values' => []]);
            const canvas = document.getElementById('streamDistributionChart');
            if (!canvas) {
                return;
            }

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels || [],
                    datasets: [{
                        data: chartData.values || [],
                        backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#64748b'],
                        borderWidth: 1,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        })();
    </script>
</x-app-layout>

