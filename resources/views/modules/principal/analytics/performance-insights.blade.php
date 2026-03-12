<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Performance Insights
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Student Performance Risk Table</h3>
                    <p class="text-sm text-gray-600 mt-1">Built from attendance, exam averages, progress trend, and latest assessment.</p>

                    <div id="actionMessage" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-7 gap-4">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Classes</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="target_exam" value="Target Exam" />
                            <select id="target_exam" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="first_term">1st Term</option>
                                <option value="final_term" selected>Final Term</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="search" value="Search Student" />
                            <x-text-input id="search" type="text" class="mt-1 block w-full" placeholder="Name, student ID, or father name" />
                        </div>
                        <div>
                            <x-input-label for="per_page" value="Per Page" />
                            <select id="per_page" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button id="generatePredictionsBtn" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Generate Predictions
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Prediction Run Summary</h3>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Predicted</p>
                            <p id="summaryPredictedCount" class="mt-1 text-xl font-semibold text-gray-900">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-red-600">High Risk</p>
                            <p id="summaryHighCount" class="mt-1 text-xl font-semibold text-red-700">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-amber-600">Medium Risk</p>
                            <p id="summaryMediumCount" class="mt-1 text-xl font-semibold text-amber-700">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-emerald-600">Low Risk</p>
                            <p id="summaryLowCount" class="mt-1 text-xl font-semibold text-emerald-700">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Avg Predicted %</p>
                            <p id="summaryAveragePredicted" class="mt-1 text-xl font-semibold text-gray-900">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Features Rebuilt</p>
                            <p id="summaryFeaturesUpserted" class="mt-1 text-xl font-semibold text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Attendance %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Avg Score %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Trend Slope</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Predicted %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Risk</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Confidence</th>
                                </tr>
                            </thead>
                            <tbody id="insightsBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Loading insights...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <p id="paginationInfo" class="text-sm text-gray-600">-</p>
                        <div class="flex gap-2">
                            <button id="prevPageBtn" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button id="nextPageBtn" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const sessionInput = document.getElementById('session');
        const classInput = document.getElementById('class_id');
        const targetExamInput = document.getElementById('target_exam');
        const searchInput = document.getElementById('search');
        const perPageInput = document.getElementById('per_page');
        const generatePredictionsBtn = document.getElementById('generatePredictionsBtn');
        const actionMessage = document.getElementById('actionMessage');

        const insightsBody = document.getElementById('insightsBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const summaryPredictedCount = document.getElementById('summaryPredictedCount');
        const summaryHighCount = document.getElementById('summaryHighCount');
        const summaryMediumCount = document.getElementById('summaryMediumCount');
        const summaryLowCount = document.getElementById('summaryLowCount');
        const summaryAveragePredicted = document.getElementById('summaryAveragePredicted');
        const summaryFeaturesUpserted = document.getElementById('summaryFeaturesUpserted');

        const state = {
            page: 1,
            per_page: 10,
            session: sessionInput.value,
            class_id: '',
            target_exam: 'final_term',
            search: ''
        };

        function showMessage(message, type = 'success') {
            actionMessage.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            actionMessage.textContent = message;
            if (type === 'error') {
                actionMessage.classList.add('bg-red-50', 'text-red-700');
            } else {
                actionMessage.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function clearMessage() {
            actionMessage.classList.add('hidden');
            actionMessage.textContent = '';
        }

        function renderPredictionSummary(summary = {}, featureMeta = {}) {
            summaryPredictedCount.textContent = summary.predicted_count ?? '-';
            summaryHighCount.textContent = summary.high_count ?? '-';
            summaryMediumCount.textContent = summary.medium_count ?? '-';
            summaryLowCount.textContent = summary.low_count ?? '-';
            summaryAveragePredicted.textContent = summary.average_predicted !== null && summary.average_predicted !== undefined
                ? `${formatNumber(summary.average_predicted)}%`
                : '-';
            summaryFeaturesUpserted.textContent = featureMeta.features_upserted ?? '-';
        }

        function riskBadge(level) {
            if (level === 'high') {
                return '<span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">High</span>';
            }
            if (level === 'medium') {
                return '<span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Medium</span>';
            }
            if (level === 'low') {
                return '<span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Low</span>';
            }

            return '<span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">N/A</span>';
        }

        function formatNumber(value, digits = 2) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return '-';
            }

            return Number(value).toFixed(digits);
        }

        async function loadInsights() {
            insightsBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Loading insights...</td></tr>';

            const params = new URLSearchParams({
                session: state.session,
                target_exam: state.target_exam,
                page: state.page,
                per_page: state.per_page,
                search: state.search,
            });
            if (state.class_id) {
                params.set('class_id', state.class_id);
            }

            const response = await fetch(`{{ route('principal.analytics.performance-insights.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                insightsBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-red-600">Failed to load insights.</td></tr>';
                return;
            }

            const result = await response.json();
            const rows = result.data || [];

            if (!rows.length) {
                insightsBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No features found. Run `php artisan analytics:build-features --session='+window.NSMS.escapeHtml(state.session)+'`.</td></tr>';
            } else {
                insightsBody.innerHTML = rows.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">
                            <div class="font-medium">${window.NSMS.escapeHtml(row.student_name)}</div>
                            <div class="text-xs text-gray-500">${window.NSMS.escapeHtml(row.student_id ?? '-')}</div>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.class_name || '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${formatNumber(row.attendance_rate)}%</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${formatNumber(row.avg_score)}%</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${formatNumber(row.trend_slope, 4)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${formatNumber(row.predicted_percentage)}%</td>
                        <td class="px-4 py-2 text-sm">${riskBadge(row.risk_level)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${row.confidence !== null ? formatNumber(row.confidence * 100, 1) + '%' : '-'}</td>
                    </tr>
                `).join('');
            }

            paginationInfo.textContent = `Page ${result.meta.current_page} of ${result.meta.last_page} | Total: ${result.meta.total}`;
            prevPageBtn.disabled = result.meta.current_page <= 1;
            nextPageBtn.disabled = result.meta.current_page >= result.meta.last_page;
        }

        async function generatePredictions() {
            clearMessage();

            const payload = {
                session: state.session,
                target_exam: state.target_exam
            };
            if (state.class_id) {
                payload.class_id = Number(state.class_id);
            }

            generatePredictionsBtn.disabled = true;
            generatePredictionsBtn.textContent = 'Generating...';

            try {
                const response = await fetch(`{{ route('principal.analytics.predict') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok) {
                    const errors = Object.values(result.errors || {}).flat();
                    showMessage(errors.length ? errors.join(' ') : (result.message || 'Prediction generation failed.'), 'error');
                    return;
                }

                renderPredictionSummary(result.summary || {}, result.features || {});
                showMessage(result.message || 'Predictions generated successfully.');
                await loadInsights();
            } catch (error) {
                showMessage('Unexpected error while generating predictions.', 'error');
            } finally {
                generatePredictionsBtn.disabled = false;
                generatePredictionsBtn.textContent = 'Generate Predictions';
            }
        }

        sessionInput.addEventListener('change', async () => {
            state.session = sessionInput.value;
            state.page = 1;
            await loadInsights();
        });

        classInput.addEventListener('change', async () => {
            state.class_id = classInput.value;
            state.page = 1;
            await loadInsights();
        });

        targetExamInput.addEventListener('change', async () => {
            state.target_exam = targetExamInput.value;
            state.page = 1;
            await loadInsights();
        });

        perPageInput.addEventListener('change', async () => {
            state.per_page = Number(perPageInput.value || 10);
            state.page = 1;
            await loadInsights();
        });

        const onSearchInput = window.NSMS.debounce(async () => {
            state.search = searchInput.value.trim();
            state.page = 1;
            await loadInsights();
        }, 300);
        searchInput.addEventListener('input', onSearchInput);

        prevPageBtn.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadInsights();
            }
        });

        nextPageBtn.addEventListener('click', async () => {
            state.page += 1;
            await loadInsights();
        });

        generatePredictionsBtn.addEventListener('click', generatePredictions);

        window.NSMS.lazyInit(insightsBody, loadInsights);
    </script>
</x-app-layout>
