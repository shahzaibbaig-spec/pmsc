<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Teacher Performance Analytics</h2>
                <p class="mt-1 text-sm text-slate-500">Manage teacher-level attendance and results analytics.</p>
            </div>
            <a
                href="{{ route('principal.analytics.teacher-rankings.index', ['session' => $defaultSession]) }}"
                class="inline-flex min-h-11 items-center rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
            >
                Open Teacher Ranking
            </a>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="teacherAnalyticsPage({
            defaultSession: @js($defaultSession),
            dataUrl: @js(route('principal.analytics.teachers.data')),
            detailUrlTemplate: @js(route('principal.analytics.teachers.detail', ['teacher' => '__TEACHER__'])),
        })"
        x-init="init()"
    >
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div>
                    <label for="session_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session_filter" x-model="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($sessions as $session)
                            <option value="{{ $session }}">{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="teacher_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</label>
                    <select id="teacher_filter" x-model="teacherId" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Teachers</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher['id'] }}">{{ $teacher['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_filter" x-model="classId" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Classes</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom['id'] }}">{{ $classRoom['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subject_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                    <select id="subject_filter" x-model="subjectId" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject['id'] }}">{{ $subject['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="xl:col-span-2">
                    <label for="teacher_search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search Teacher</label>
                    <input
                        id="teacher_search"
                        type="text"
                        x-model="search"
                        @input="onSearchInput()"
                        placeholder="Name, teacher ID, employee code"
                        class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    @click="loadTable(true)"
                    :disabled="loading"
                    class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span x-text="loading ? 'Loading...' : 'Apply Filters'"></span>
                </button>
                <button
                    type="button"
                    @click="resetFilters()"
                    :disabled="loading"
                    class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Reset
                </button>
                <p class="text-xs text-slate-500" x-text="paginationLabel()"></p>
            </div>

            <div
                x-show="status.message !== ''"
                x-cloak
                class="mt-4 rounded-xl border px-4 py-3 text-sm"
                :class="status.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                x-text="status.message"
            ></div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Average Attendance</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="percent(kpis.average_attendance)"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Average Student Score</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="percent(kpis.average_student_score)"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pass Rate</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="percent(kpis.pass_rate)"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Improvement</p>
                <p class="mt-2 text-2xl font-semibold" :class="metricColor(kpis.improvement)" x-text="signedPercent(kpis.improvement)"></p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Attendance %</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Average Student Score</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pass %</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Improvement %</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Classes</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr x-show="loading">
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Loading analytics...</td>
                        </tr>

                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No teacher records found.</td>
                            </tr>
                        </template>

                        <template x-for="row in rows" :key="`teacher-${row.teacher_id}`">
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <p class="font-semibold text-slate-900" x-text="row.teacher_name"></p>
                                    <p class="text-xs text-slate-500" x-text="row.teacher_code ? `ID: ${row.teacher_code}` : '-'"></p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="percent(row.attendance_percentage)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="percent(row.average_student_score)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="percent(row.pass_percentage)"></td>
                                <td class="px-4 py-3 text-sm font-medium" :class="metricColor(row.improvement_percentage)" x-text="signedPercent(row.improvement_percentage)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <p x-text="row.classes_label || '-'"></p>
                                    <p class="text-xs text-slate-500" x-text="`${Number(row.classes_count || 0)} class(es)`"></p>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <button
                                        type="button"
                                        @click="openDetail(row.teacher_id)"
                                        class="inline-flex min-h-9 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3">
                <p class="text-xs text-slate-500" x-text="paginationLabel()"></p>
                <div class="inline-flex items-center gap-2">
                    <button
                        type="button"
                        @click="changePage(meta.current_page - 1)"
                        :disabled="meta.current_page <= 1 || loading"
                        class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <button
                        type="button"
                        @click="changePage(meta.current_page + 1)"
                        :disabled="meta.current_page >= meta.last_page || loading"
                        class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </section>
        <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/50" @click="closeDetail()"></div>
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-3xl flex-col border-l border-slate-200 bg-slate-50 shadow-xl">
                <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Teacher Detail</h3>
                        <p class="mt-1 text-xs text-slate-500" x-text="detail?.teacher?.name ? `${detail.teacher.name} (${detail.teacher.teacher_id || '-'})` : 'Loading...'"></p>
                    </div>
                    <button type="button" @click="closeDetail()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100">
                        <span class="sr-only">Close</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </header>

                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    <template x-if="drawerLoading">
                        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-500 shadow-sm">Loading teacher details...</div>
                    </template>

                    <template x-if="!drawerLoading && drawerError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="drawerError"></div>
                    </template>

                    <template x-if="!drawerLoading && detail">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Attendance</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900" x-text="percent(detail.summary?.attendance_percentage)"></p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Avg Score</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900" x-text="percent(detail.summary?.average_student_score)"></p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Pass Rate</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900" x-text="percent(detail.summary?.pass_percentage)"></p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Improvement</p>
                                    <p class="mt-1 text-lg font-semibold" :class="metricColor(detail.summary?.improvement_percentage)" x-text="signedPercent(detail.summary?.improvement_percentage)"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <h4 class="text-sm font-semibold text-slate-900">Assigned Classes</h4>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <template x-if="(detail.assigned_classes || []).length === 0">
                                            <span class="text-xs text-slate-500">No classes found.</span>
                                        </template>
                                        <template x-for="item in (detail.assigned_classes || [])" :key="`class-${item.id}`">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700" x-text="item.name"></span>
                                        </template>
                                    </div>
                                </article>

                                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <h4 class="text-sm font-semibold text-slate-900">Assigned Subjects</h4>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <template x-if="(detail.assigned_subjects || []).length === 0">
                                            <span class="text-xs text-slate-500">No subjects found.</span>
                                        </template>
                                        <template x-for="item in (detail.assigned_subjects || [])" :key="`subject-${item.id}`">
                                            <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700" x-text="item.name"></span>
                                        </template>
                                    </div>
                                </article>
                            </div>

                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                    <h4 class="text-sm font-semibold text-emerald-800">Best Class</h4>
                                    <p class="mt-2 text-sm font-medium text-emerald-900" x-text="detail.best_class?.class_name || '-'"></p>
                                </article>
                                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                                    <h4 class="text-sm font-semibold text-rose-800">Weakest Class</h4>
                                    <p class="mt-2 text-sm font-medium text-rose-900" x-text="detail.weakest_class?.class_name || '-'"></p>
                                </article>
                            </div>

                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <h4 class="text-sm font-semibold text-slate-900">Monthly Attendance</h4>
                                <div class="mt-3 h-64"><canvas id="teacherAttendanceChart"></canvas></div>
                            </article>

                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <h4 class="text-sm font-semibold text-slate-900">Exam Performance by Type</h4>
                                <div class="mt-3 h-64"><canvas id="teacherExamPerformanceChart"></canvas></div>
                            </article>

                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <h4 class="text-sm font-semibold text-slate-900">Monthly Exam Trend</h4>
                                <div class="mt-3 h-64"><canvas id="teacherMonthlyExamChart"></canvas></div>
                            </article>

                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <h4 class="text-sm font-semibold text-slate-900">Class Performance Breakdown</h4>
                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                                <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">Avg Score</th>
                                                <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">Pass %</th>
                                                <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">Entries</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <template x-if="(detail.class_performance || []).length === 0">
                                                <tr>
                                                    <td colspan="4" class="px-3 py-4 text-center text-xs text-slate-500">No class performance data available.</td>
                                                </tr>
                                            </template>
                                            <template x-for="item in (detail.class_performance || [])" :key="`class-perf-${item.class_id}`">
                                                <tr>
                                                    <td class="px-3 py-2 text-xs text-slate-700" x-text="item.class_name"></td>
                                                    <td class="px-3 py-2 text-xs text-slate-700" x-text="percent(item.avg_score)"></td>
                                                    <td class="px-3 py-2 text-xs text-slate-700" x-text="percent(item.pass_rate)"></td>
                                                    <td class="px-3 py-2 text-xs text-slate-700" x-text="Number(item.entries || 0)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                        </div>
                    </template>
                </div>
            </aside>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        function teacherAnalyticsPage(config) {
            return {
                session: config.defaultSession || '',
                teacherId: '',
                classId: '',
                subjectId: '',
                search: '',
                loading: false,
                rows: [],
                kpis: {
                    average_attendance: null,
                    average_student_score: null,
                    pass_rate: null,
                    improvement: null,
                },
                meta: {
                    current_page: 1,
                    last_page: 1,
                    total: 0,
                    per_page: 15,
                    from: null,
                    to: null,
                },
                status: {
                    message: '',
                    type: 'success',
                },
                searchTimer: null,
                drawerOpen: false,
                drawerLoading: false,
                drawerError: '',
                detail: null,
                charts: {
                    attendance: null,
                    examType: null,
                    monthlyExam: null,
                },

                init() {
                    this.loadTable(true);
                },

                clearStatus() {
                    this.status.message = '';
                    this.status.type = 'success';
                },

                setStatus(message, type = 'success') {
                    this.status.message = message;
                    this.status.type = type;
                },

                onSearchInput() {
                    if (this.searchTimer) {
                        window.clearTimeout(this.searchTimer);
                    }
                    this.searchTimer = window.setTimeout(() => this.loadTable(true), 350);
                },

                percent(value, digits = 2) {
                    if (value === null || value === undefined || Number.isNaN(Number(value))) {
                        return '-';
                    }
                    return `${Number(value).toFixed(digits)}%`;
                },

                signedPercent(value) {
                    if (value === null || value === undefined || Number.isNaN(Number(value))) {
                        return '-';
                    }
                    const numeric = Number(value);
                    const prefix = numeric > 0 ? '+' : '';
                    return `${prefix}${numeric.toFixed(2)}%`;
                },

                metricColor(value) {
                    if (value === null || value === undefined || Number.isNaN(Number(value))) {
                        return 'text-slate-700';
                    }
                    const numeric = Number(value);
                    if (numeric > 0) {
                        return 'text-emerald-700';
                    }
                    if (numeric < 0) {
                        return 'text-rose-700';
                    }
                    return 'text-slate-700';
                },

                paginationLabel() {
                    const total = Number(this.meta.total || 0);
                    if (total === 0) {
                        return 'No records available';
                    }
                    const from = this.meta.from ?? 1;
                    const to = this.meta.to ?? Math.min(Number(this.meta.per_page || 15), total);
                    return `Showing ${from} to ${to} of ${total} teachers`;
                },

                resetFilters() {
                    this.teacherId = '';
                    this.classId = '';
                    this.subjectId = '';
                    this.search = '';
                    this.loadTable(true);
                },

                buildQuery(page) {
                    const params = new URLSearchParams({
                        session: this.session,
                        page: String(Math.max(Number(page || 1), 1)),
                        per_page: String(Number(this.meta.per_page || 15)),
                    });

                    const term = this.search.trim();
                    if (this.teacherId !== '') {
                        params.set('teacher_id', String(this.teacherId));
                    }
                    if (this.classId !== '') {
                        params.set('class_id', String(this.classId));
                    }
                    if (this.subjectId !== '') {
                        params.set('subject_id', String(this.subjectId));
                    }
                    if (term !== '') {
                        params.set('search', term);
                    }
                    return params;
                },

                async loadTable(resetPage = true, targetPage = null) {
                    this.clearStatus();
                    if (!this.session) {
                        this.setStatus('Session is required.', 'error');
                        return;
                    }

                    const page = targetPage !== null
                        ? Number(targetPage)
                        : (resetPage ? 1 : Number(this.meta.current_page || 1));

                    this.loading = true;
                    try {
                        const params = this.buildQuery(page);
                        const response = await fetch(`${config.dataUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            this.rows = [];
                            this.kpis = {
                                average_attendance: null,
                                average_student_score: null,
                                pass_rate: null,
                                improvement: null,
                            };
                            this.meta = {
                                current_page: 1,
                                last_page: 1,
                                total: 0,
                                per_page: Number(this.meta.per_page || 15),
                                from: null,
                                to: null,
                            };
                            this.setStatus(result.message || 'Failed to load teacher analytics.', 'error');
                            return;
                        }

                        this.rows = Array.isArray(result.rows) ? result.rows : [];
                        this.kpis = {
                            average_attendance: result.kpis?.average_attendance ?? null,
                            average_student_score: result.kpis?.average_student_score ?? null,
                            pass_rate: result.kpis?.pass_rate ?? null,
                            improvement: result.kpis?.improvement ?? null,
                        };
                        this.meta = {
                            current_page: Number(result.meta?.current_page || 1),
                            last_page: Number(result.meta?.last_page || 1),
                            total: Number(result.meta?.total || 0),
                            per_page: Number(result.meta?.per_page || 15),
                            from: result.meta?.from ?? null,
                            to: result.meta?.to ?? null,
                        };
                    } catch (error) {
                        this.rows = [];
                        this.setStatus('Unexpected error while loading teacher analytics.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                changePage(nextPage) {
                    const page = Number(nextPage);
                    if (page < 1 || page > Number(this.meta.last_page || 1) || this.loading) {
                        return;
                    }
                    this.loadTable(false, page);
                },

                async openDetail(teacherId) {
                    if (!teacherId || !this.session) {
                        return;
                    }

                    this.drawerOpen = true;
                    this.drawerLoading = true;
                    this.drawerError = '';
                    this.detail = null;
                    this.destroyCharts();

                    const url = String(config.detailUrlTemplate).replace('__TEACHER__', String(teacherId));
                    const params = new URLSearchParams({ session: this.session });
                    if (this.classId !== '') {
                        params.set('class_id', String(this.classId));
                    }
                    if (this.subjectId !== '') {
                        params.set('subject_id', String(this.subjectId));
                    }

                    try {
                        const response = await fetch(`${url}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            this.drawerError = result.message || 'Failed to load teacher details.';
                            return;
                        }

                        this.detail = result;
                        await this.$nextTick();
                        this.renderCharts();
                    } catch (error) {
                        this.drawerError = 'Unexpected error while loading teacher details.';
                    } finally {
                        this.drawerLoading = false;
                    }
                },

                closeDetail() {
                    this.drawerOpen = false;
                    this.drawerLoading = false;
                    this.drawerError = '';
                    this.detail = null;
                    this.destroyCharts();
                },

                destroyCharts() {
                    Object.values(this.charts).forEach((chart) => {
                        if (chart && typeof chart.destroy === 'function') {
                            chart.destroy();
                        }
                    });
                    this.charts = {
                        attendance: null,
                        examType: null,
                        monthlyExam: null,
                    };
                },

                renderCharts() {
                    if (!this.detail || typeof Chart === 'undefined') {
                        return;
                    }

                    this.destroyCharts();

                    const attendanceCanvas = document.getElementById('teacherAttendanceChart');
                    if (attendanceCanvas) {
                        this.charts.attendance = new Chart(attendanceCanvas, {
                            type: 'line',
                            data: {
                                labels: this.detail.monthly_attendance?.labels || [],
                                datasets: [{
                                    label: 'Attendance %',
                                    data: this.detail.monthly_attendance?.values || [],
                                    borderColor: '#0f766e',
                                    backgroundColor: 'rgba(15, 118, 110, 0.16)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 2,
                                }],
                            },
                            options: {
                                maintainAspectRatio: false,
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: { callback: (value) => `${value}%` },
                                    },
                                },
                            },
                        });
                    }

                    const examCanvas = document.getElementById('teacherExamPerformanceChart');
                    if (examCanvas) {
                        this.charts.examType = new Chart(examCanvas, {
                            type: 'bar',
                            data: {
                                labels: this.detail.exam_performance?.labels || [],
                                datasets: [
                                    {
                                        label: 'Average Score %',
                                        data: this.detail.exam_performance?.average_scores || [],
                                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                                        borderColor: '#2563eb',
                                        borderWidth: 1,
                                    },
                                    {
                                        label: 'Pass %',
                                        data: this.detail.exam_performance?.pass_rates || [],
                                        backgroundColor: 'rgba(22, 163, 74, 0.55)',
                                        borderColor: '#15803d',
                                        borderWidth: 1,
                                    },
                                ],
                            },
                            options: {
                                maintainAspectRatio: false,
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: { callback: (value) => `${value}%` },
                                    },
                                },
                            },
                        });
                    }

                    const monthlyExamCanvas = document.getElementById('teacherMonthlyExamChart');
                    if (monthlyExamCanvas) {
                        this.charts.monthlyExam = new Chart(monthlyExamCanvas, {
                            type: 'line',
                            data: {
                                labels: this.detail.monthly_exam_performance?.labels || [],
                                datasets: [{
                                    label: 'Average Score %',
                                    data: this.detail.monthly_exam_performance?.values || [],
                                    borderColor: '#7c3aed',
                                    backgroundColor: 'rgba(124, 58, 237, 0.12)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 2,
                                }],
                            },
                            options: {
                                maintainAspectRatio: false,
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100,
                                        ticks: { callback: (value) => `${value}%` },
                                    },
                                },
                            },
                        });
                    }
                },
            };
        }
    </script>
</x-app-layout>
