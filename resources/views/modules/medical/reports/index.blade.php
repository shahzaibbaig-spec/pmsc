<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Medical Reports
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Monthly / Yearly Report</h3>
                    <p class="text-sm text-gray-600 mt-1">Generate referral history reports by month or year.</p>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div>
                            <x-input-label for="report_type" value="Report Type" />
                            <select id="report_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div id="monthWrapper">
                            <x-input-label for="month" value="Month" />
                            <select id="month" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" @selected($i === now()->month)>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <x-input-label for="year" value="Year" />
                            <x-text-input id="year" type="number" class="mt-1 block w-full" value="{{ now()->year }}" />
                        </div>
                        <div>
                            <x-input-label for="student_id" value="Student ID (Optional)" />
                            <x-text-input id="student_id" type="number" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="per_page" value="Per Page" />
                            <select id="per_page" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <div class="flex gap-2">
                                <button id="generateBtn" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                    Generate Report
                                </button>
                                <a id="downloadPdfBtn" href="#" target="_blank" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 opacity-50 pointer-events-none">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4">
                <div class="bg-white p-4 shadow-sm sm:rounded-lg"><p class="text-xs text-gray-500">Total</p><p id="sumTotal" class="text-xl font-semibold">0</p></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg"><p class="text-xs text-amber-600">Pending</p><p id="sumPending" class="text-xl font-semibold">0</p></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg"><p class="text-xs text-green-600">Completed</p><p id="sumCompleted" class="text-xl font-semibold">0</p></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg"><p class="text-xs text-gray-500">Fever</p><p id="sumFever" class="text-xl font-semibold">0</p></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg"><p class="text-xs text-gray-500">Headache</p><p id="sumHeadache" class="text-xl font-semibold">0</p></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg"><p class="text-xs text-gray-500">Stomach Ache</p><p id="sumStomach" class="text-xl font-semibold">0</p></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg"><p class="text-xs text-gray-500">Other</p><p id="sumOther" class="text-xl font-semibold">0</p></div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Source</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Doctor</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Illness</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Diagnosis</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Generate a report to view records.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <p id="paginationInfo" class="text-sm text-gray-600">-</p>
                        <div class="flex gap-2">
                            <button id="prevPage" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button id="nextPage" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const reportTypeInput = document.getElementById('report_type');
        const monthWrapper = document.getElementById('monthWrapper');
        const monthInput = document.getElementById('month');
        const yearInput = document.getElementById('year');
        const studentIdInput = document.getElementById('student_id');
        const perPageInput = document.getElementById('per_page');
        const generateBtn = document.getElementById('generateBtn');
        const downloadPdfBtn = document.getElementById('downloadPdfBtn');
        const reportBody = document.getElementById('reportBody');
        const messageBox = document.getElementById('messageBox');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');

        const sumTotal = document.getElementById('sumTotal');
        const sumPending = document.getElementById('sumPending');
        const sumCompleted = document.getElementById('sumCompleted');
        const sumFever = document.getElementById('sumFever');
        const sumHeadache = document.getElementById('sumHeadache');
        const sumStomach = document.getElementById('sumStomach');
        const sumOther = document.getElementById('sumOther');

        const state = {
            page: 1,
            per_page: Number(perPageInput.value || 20)
        };

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showMessage(text, type = 'success') {
            messageBox.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            messageBox.textContent = text;
            if (type === 'error') {
                messageBox.classList.add('bg-red-50', 'text-red-700');
            } else {
                messageBox.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function updateSummary(summary) {
            sumTotal.textContent = summary.total ?? 0;
            sumPending.textContent = summary.pending ?? 0;
            sumCompleted.textContent = summary.completed ?? 0;
            sumFever.textContent = summary.fever ?? 0;
            sumHeadache.textContent = summary.headache ?? 0;
            sumStomach.textContent = summary.stomach_ache ?? 0;
            sumOther.textContent = summary.other ?? 0;
        }

        async function generateReport({ resetPage = false } = {}) {
            if (resetPage) {
                state.page = 1;
            }

            const payload = {
                report_type: reportTypeInput.value,
                month: reportTypeInput.value === 'monthly' ? monthInput.value : '',
                year: yearInput.value,
                student_id: studentIdInput.value || '',
                page: state.page,
                per_page: state.per_page,
            };

            generateBtn.disabled = true;
            generateBtn.textContent = 'Generating...';
            reportBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Generating report...</td></tr>';

            try {
                const params = new URLSearchParams();
                params.set('report_type', payload.report_type);
                params.set('year', payload.year);
                params.set('page', String(payload.page));
                params.set('per_page', String(payload.per_page));
                if (payload.report_type === 'monthly' && payload.month) {
                    params.set('month', payload.month);
                }
                if (payload.student_id) {
                    params.set('student_id', payload.student_id);
                }
                const response = await fetch(`{{ route('medical.reports.data') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();
                if (!response.ok) {
                    showMessage(result.message || 'Failed to generate report.', 'error');
                    reportBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-red-600">Failed to generate report.</td></tr>';
                    return;
                }

                const rows = result.data || [];
                updateSummary(result.summary || {});

                if (!rows.length) {
                    reportBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No records found for selected filters.</td></tr>';
                } else {
                    reportBody.innerHTML = rows.map(row => `
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.visit_date || row.created_at)}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.source_label || '-')}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.student_name)} (${escapeHtml(row.student_id)})</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.class_name)}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.doctor_name || '-')}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.illness_label)}${row.illness_other_text ? ' - ' + escapeHtml(row.illness_other_text) : ''}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.diagnosis ?? '-')}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.status)}</td>
                        </tr>
                    `).join('');
                }

                const meta = result.meta || { current_page: 1, last_page: 1, total: rows.length };
                paginationInfo.textContent = `Page ${meta.current_page} of ${meta.last_page} | Total: ${meta.total}`;
                prevPageBtn.disabled = meta.current_page <= 1;
                nextPageBtn.disabled = meta.current_page >= meta.last_page;

                showMessage('Report generated successfully.');

                const pdfParams = new URLSearchParams({
                    report_type: payload.report_type,
                    year: payload.year,
                });
                if (payload.report_type === 'monthly' && payload.month) {
                    pdfParams.set('month', payload.month);
                }
                if (payload.student_id) {
                    pdfParams.set('student_id', payload.student_id);
                }
                downloadPdfBtn.href = `{{ route('reports.pdf.medical-report') }}?${pdfParams.toString()}`;
                downloadPdfBtn.classList.remove('opacity-50', 'pointer-events-none');
            } catch (error) {
                showMessage('Unexpected error while generating report.', 'error');
                reportBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-red-600">Failed to generate report.</td></tr>';
            } finally {
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate Report';
            }
        }

        reportTypeInput.addEventListener('change', () => {
            if (reportTypeInput.value === 'yearly') {
                monthWrapper.classList.add('hidden');
            } else {
                monthWrapper.classList.remove('hidden');
            }
            generateReport({ resetPage: true });
        });

        generateBtn.addEventListener('click', () => generateReport({ resetPage: true }));
        monthInput.addEventListener('change', () => generateReport({ resetPage: true }));
        yearInput.addEventListener('change', () => generateReport({ resetPage: true }));
        studentIdInput.addEventListener('change', () => generateReport({ resetPage: true }));
        perPageInput.addEventListener('change', () => {
            state.per_page = Number(perPageInput.value || 20);
            generateReport({ resetPage: true });
        });

        prevPageBtn.addEventListener('click', () => {
            if (state.page > 1) {
                state.page -= 1;
                generateReport();
            }
        });

        nextPageBtn.addEventListener('click', () => {
            state.page += 1;
            generateReport();
        });

        window.NSMS.lazyInit(reportBody, () => generateReport({ resetPage: true }));
    </script>
</x-app-layout>
