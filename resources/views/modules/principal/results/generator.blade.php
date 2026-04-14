<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Result Card Generator</h2>
            <p class="mt-1 text-sm text-slate-500">Generate, preview, print, publish, and download student result cards.</p>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="resultCardGenerator({
            defaultSession: @js($defaultSession),
            defaultExamType: @js($defaultExamType),
            defaultClassId: @js($defaultClassId),
            defaultStudentId: @js($defaultStudentId),
            studentsUrl: @js(route('principal.results.students')),
            previewUrl: @js(route('principal.results.preview')),
            cardPreviewUrl: @js(route('principal.results.card')),
            studentPdfUrl: @js(route('reports.pdf.student-result-card')),
            classCardsPdfUrl: @js(route('reports.pdf.class-result-cards')),
            publishUrl: @js(route('principal.results.publish')),
            markingModeContextUrl: @js(route('principal.results.marking-mode.context')),
            updateMarkingModeUrl: @js(route('principal.results.marking-mode.update')),
            initialMarkingModeContext: @js($markingModeContext),
            csrfToken: @js(csrf_token()),
        })"
        x-init="init()"
    >
        <div
            x-show="status.message !== ''"
            x-cloak
            class="rounded-xl border px-4 py-3 text-sm"
            :class="status.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
            x-text="status.message"
        ></div>

        @if (! $hasMarks)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                No marks are available yet. Ask teachers to submit marks before generating result cards.
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="xl:col-span-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:sticky xl:top-6">
                    <h3 class="text-sm font-semibold text-slate-900">Filters</h3>
                    <p class="mt-1 text-xs text-slate-500">Choose exam context and student.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="session_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                            <select
                                id="session_filter"
                                x-model="session"
                                @change="onExamContextChanged()"
                                class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}">{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="exam_type_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Type</label>
                            <select
                                id="exam_type_filter"
                                x-model="examType"
                                @change="onExamContextChanged()"
                                class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                @foreach($examTypes as $examType)
                                    <option value="{{ $examType['value'] }}">{{ $examType['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="class_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                            <select
                                id="class_filter"
                                x-model.number="classId"
                                @change="onClassChanged()"
                                class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select class</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="student_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Student</label>
                            <select
                                id="student_filter"
                                x-model.number="studentId"
                                @change="onStudentChanged()"
                                class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :disabled="loadingStudents || students.length === 0"
                            >
                                <option value="">Select student</option>
                                <option value="0">Whole Class</option>
                                <template x-for="student in students" :key="`student-opt-${student.id}`">
                                    <option :value="student.id" x-text="`${student.name} (${student.student_id})`"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-slate-500" x-text="loadingStudents ? 'Loading students...' : `${students.length} student(s)`"></p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Assessment Marking Mode</p>
                                <p class="mt-1 text-xs text-slate-600">
                                    Principal/Admin can control whether this class exam scope uses grades or numeric marks.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="loadMarkingModeContext()"
                                class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!classId || markingModeLoading"
                            >
                                <span x-text="markingModeLoading ? 'Loading...' : 'Refresh'"></span>
                            </button>
                        </div>

                        <div x-show="!classId" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Select a class first to configure mode.
                        </div>

                        <template x-if="classId">
                            <div class="mt-3 space-y-3">
                                <div x-show="!supportsGradeMode" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-800">
                                    This class is not Early Years, so numeric mode remains active.
                                </div>

                                <div x-show="supportsGradeMode" class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" value="numeric" x-model="markingMode">
                                        <span>Numeric Marks</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" value="grade" x-model="markingMode">
                                        <span>Grades (A* to U)</span>
                                    </label>
                                </div>

                                <p class="text-xs text-slate-600">
                                    Applies to selected session + exam type for all assigned subjects in this class.
                                </p>

                                <p class="text-xs text-slate-600" x-show="markingModeContext">
                                    Configured subjects:
                                    <span class="font-semibold text-slate-900" x-text="`${markingModeContext?.configured_subjects ?? 0}/${markingModeContext?.expected_subjects ?? 0}`"></span>
                                </p>

                                <button
                                    type="button"
                                    @click="saveMarkingMode()"
                                    class="inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="markingModeSaving || !classId || !session || !examType"
                                >
                                    <span x-text="markingModeSaving ? 'Saving...' : 'Save Marking Mode'"></span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-5 space-y-2">
                        <button
                            type="button"
                            @click="previewResult()"
                            :disabled="previewLoading || !canRunStudentAction()"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span x-text="previewLoading ? 'Loading...' : (isWholeClassSelected() ? 'Generate Whole Class Result' : 'Preview Result')"></span>
                        </button>
                        <button
                            type="button"
                            @click="downloadPdf()"
                            :disabled="!result && !isWholeClassSelected()"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Download PDF
                        </button>
                        <button
                            type="button"
                            @click="printPreview()"
                            :disabled="!result && !isWholeClassSelected()"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Print
                        </button>
                    </div>
                </section>
            </aside>

            <main class="space-y-6 xl:col-span-5">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Class Actions</h3>
                            <p class="mt-1 text-xs text-slate-500">Generate cards for class and publish results.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="generateAllClassCards()"
                                :disabled="generatingClassCards || !canRunClassAction()"
                                class="inline-flex min-h-10 items-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span x-text="generatingClassCards ? 'Generating...' : 'Generate All Class Result Cards'"></span>
                            </button>
                            <button
                                type="button"
                                @click="publishResults()"
                                :disabled="publishing || !canRunClassAction()"
                                class="inline-flex min-h-10 items-center rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span x-text="publishing ? 'Publishing...' : 'Publish Results'"></span>
                            </button>
                        </div>
                    </div>
                </section>

                <section x-show="!result?.uses_grade_system" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Marks</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="result?.summary?.total_marks ?? '-'"></p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obtained Marks</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="result?.summary?.obtained_marks ?? '-'"></p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Overall Percentage</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="result?.summary?.percentage !== null && result?.summary?.percentage !== undefined ? `${result.summary.percentage}%` : '-'"></p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Overall Grade</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="result?.summary?.grade ?? '-'"></p>
                    </article>
                </section>

                <section x-show="result?.uses_grade_system" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Overall Grade</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="result?.summary?.grade ?? '-'"></p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Descriptor</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="result?.summary?.grade_label ?? '-'"></p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Overall Performance</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900" x-text="result?.summary?.overall_performance ?? '-'"></p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Assessment Mode</p>
                        <p class="mt-2 text-lg font-semibold text-indigo-700">Grade-based</p>
                    </article>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Result Summary</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                <span x-text="result?.student?.name || 'No student selected'"></span>
                                <span x-show="result?.exam?.exam_type_label"> | </span>
                                <span x-text="result?.exam?.exam_type_label || ''"></span>
                                <span x-show="result?.exam?.session"> (</span>
                                <span x-text="result?.exam?.session || ''"></span>
                                <span x-show="result?.exam?.session">)</span>
                            </p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700" x-text="result?.student?.class || '-'"></div>
                    </div>

                    <div
                        x-show="result?.uses_grade_system"
                        class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800"
                    >
                        This exam scope is configured for grade-based reporting. Totals, percentages, and positions are not used.
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                                    <template x-if="result?.uses_grade_system">
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Grade</th>
                                    </template>
                                    <template x-if="result?.uses_grade_system">
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                                    </template>
                                    <template x-if="!result?.uses_grade_system">
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total Marks</th>
                                    </template>
                                    <template x-if="!result?.uses_grade_system">
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Obtained</th>
                                    </template>
                                    <template x-if="!result?.uses_grade_system">
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Percentage</th>
                                    </template>
                                    <template x-if="!result?.uses_grade_system">
                                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Grade</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="previewLoading">
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Loading result...</td>
                                    </tr>
                                </template>
                                <template x-if="!previewLoading && (!result || (result.subjects || []).length === 0)">
                                    <tr>
                                        <td :colspan="result?.uses_grade_system ? 3 : 5" class="px-4 py-8 text-center text-sm text-slate-500">Preview a student result to view subject-wise results.</td>
                                    </tr>
                                </template>
                                <template x-for="row in (result?.subjects || [])" :key="`subject-${row.subject}`">
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-slate-700" x-text="row.subject"></td>
                                        <template x-if="result?.uses_grade_system">
                                            <td class="px-4 py-2 text-sm font-semibold text-slate-900" x-text="row.grade || '-'"></td>
                                        </template>
                                        <template x-if="result?.uses_grade_system">
                                            <td class="px-4 py-2 text-sm text-slate-700" x-text="row.grade_label || '-'"></td>
                                        </template>
                                        <template x-if="!result?.uses_grade_system">
                                            <td class="px-4 py-2 text-sm text-slate-700" x-text="row.total_marks"></td>
                                        </template>
                                        <template x-if="!result?.uses_grade_system">
                                            <td class="px-4 py-2 text-sm text-slate-700" x-text="row.obtained_marks"></td>
                                        </template>
                                        <template x-if="!result?.uses_grade_system">
                                            <td class="px-4 py-2 text-sm text-slate-700" x-text="row.percentage !== null && row.percentage !== undefined ? `${row.percentage}%` : '-'"></td>
                                        </template>
                                        <template x-if="!result?.uses_grade_system">
                                            <td class="px-4 py-2 text-sm font-semibold text-slate-900" x-text="row.grade"></td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <aside class="xl:col-span-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:sticky xl:top-6">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Result Card Preview</h3>
                            <p class="mt-1 text-xs text-slate-500">Live card preview for selected student.</p>
                        </div>
                        <button
                            type="button"
                            @click="openPreviewInNewTab()"
                            :disabled="!previewCardUrl"
                            class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Open
                        </button>
                    </div>

                    <div class="h-[640px] overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <template x-if="!previewCardUrl">
                            <div class="flex h-full items-center justify-center px-6 text-center text-sm text-slate-500">
                                <span x-show="!isWholeClassSelected()">Select filters and click <span class="mx-1 font-semibold text-slate-700">Preview Result</span> to load result card preview.</span>
                                <span x-show="isWholeClassSelected()">Whole class selected. Use <span class="mx-1 font-semibold text-slate-700">Generate Whole Class Result</span> to download class result cards.</span>
                            </div>
                        </template>
                        <iframe
                            x-show="previewCardUrl"
                            x-ref="previewFrame"
                            :src="previewCardUrl"
                            class="h-full w-full bg-white"
                            title="Result Card Preview"
                        ></iframe>
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <script>
        function resultCardGenerator(config) {
            return {
                session: config.defaultSession || '',
                examType: config.defaultExamType || '',
                classId: config.defaultClassId ? Number(config.defaultClassId) : null,
                studentId: config.defaultStudentId ? Number(config.defaultStudentId) : null,
                students: [],
                loadingStudents: false,
                previewLoading: false,
                generatingClassCards: false,
                publishing: false,
                result: null,
                previewCardUrl: '',
                studentPdfUrl: '',
                status: {
                    message: '',
                    type: 'success',
                },
                markingModeContext: config.initialMarkingModeContext || null,
                markingMode: (config.initialMarkingModeContext && (config.initialMarkingModeContext.marking_mode === 'grade' || config.initialMarkingModeContext.marking_mode === 'numeric'))
                    ? config.initialMarkingModeContext.marking_mode
                    : 'numeric',
                supportsGradeMode: Boolean(config.initialMarkingModeContext?.supports_grade_mode),
                markingModeLoading: false,
                markingModeSaving: false,

                async init() {
                    if (this.classId) {
                        await this.loadMarkingModeContext();
                        await this.loadStudents();
                        if (this.studentId) {
                            await this.previewResult();
                        }
                    }
                },

                clearStatus() {
                    this.status.message = '';
                    this.status.type = 'success';
                },

                setStatus(message, type = 'success') {
                    this.status.message = message;
                    this.status.type = type;
                },

                resetMarkingModeContext() {
                    this.markingModeContext = null;
                    this.supportsGradeMode = false;
                    this.markingMode = 'numeric';
                },

                applyMarkingModeContext(context) {
                    this.markingModeContext = context || null;
                    this.supportsGradeMode = Boolean(context?.supports_grade_mode);

                    if (!this.supportsGradeMode) {
                        this.markingMode = 'numeric';
                        return;
                    }

                    if (context?.marking_mode === 'grade' || context?.marking_mode === 'numeric') {
                        this.markingMode = context.marking_mode;
                        return;
                    }

                    this.markingMode = 'numeric';
                },

                canRunClassAction() {
                    return Boolean(this.classId && this.session && this.examType);
                },

                canRunStudentAction() {
                    const selectedStudent = this.studentId;
                    const wholeClassSelected = String(selectedStudent) === '0';
                    const singleStudentSelected = Number(selectedStudent) > 0;
                    const hasSelection = wholeClassSelected || singleStudentSelected;
                    return Boolean(this.classId && this.session && this.examType && hasSelection);
                },

                isWholeClassSelected() {
                    return String(this.studentId) === '0';
                },

                onClassChanged() {
                    this.studentId = null;
                    this.result = null;
                    this.previewCardUrl = '';
                    this.studentPdfUrl = '';
                    if (this.classId) {
                        this.loadMarkingModeContext();
                        this.loadStudents();
                    } else {
                        this.students = [];
                        this.resetMarkingModeContext();
                    }
                },

                onExamContextChanged() {
                    this.result = null;
                    this.previewCardUrl = '';
                    this.studentPdfUrl = '';

                    if (this.classId) {
                        this.loadMarkingModeContext();
                    } else {
                        this.resetMarkingModeContext();
                    }
                },

                onStudentChanged() {
                    this.result = null;
                    this.previewCardUrl = '';
                    this.studentPdfUrl = '';
                },

                async loadMarkingModeContext() {
                    if (!this.classId || !this.session || !this.examType) {
                        this.resetMarkingModeContext();
                        return;
                    }

                    this.markingModeLoading = true;
                    try {
                        const params = new URLSearchParams({
                            class_id: String(this.classId),
                            session: String(this.session),
                            exam_type: String(this.examType),
                        });

                        const response = await fetch(`${config.markingModeContextUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            this.resetMarkingModeContext();
                            this.setStatus(result.message || 'Failed to load marking mode context.', 'error');
                            return;
                        }

                        this.applyMarkingModeContext(result);
                    } catch (error) {
                        this.resetMarkingModeContext();
                        this.setStatus('Unexpected error while loading marking mode context.', 'error');
                    } finally {
                        this.markingModeLoading = false;
                    }
                },

                async saveMarkingMode() {
                    this.clearStatus();
                    if (!this.classId || !this.session || !this.examType) {
                        this.setStatus('Class, session, and exam type are required to configure marking mode.', 'error');
                        return;
                    }

                    if (!this.supportsGradeMode) {
                        this.markingMode = 'numeric';
                    }

                    this.markingModeSaving = true;
                    try {
                        const response = await fetch(config.updateMarkingModeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                class_id: Number(this.classId),
                                session: String(this.session),
                                exam_type: String(this.examType),
                                marking_mode: this.supportsGradeMode ? this.markingMode : 'numeric',
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to update marking mode.', 'error');
                            return;
                        }

                        await this.loadMarkingModeContext();
                        this.setStatus(result.message || 'Assessment marking mode saved successfully.');
                    } catch (error) {
                        this.setStatus('Unexpected error while saving marking mode.', 'error');
                    } finally {
                        this.markingModeSaving = false;
                    }
                },

                async loadStudents() {
                    this.clearStatus();
                    if (!this.classId) {
                        this.students = [];
                        return;
                    }

                    this.loadingStudents = true;
                    try {
                        const params = new URLSearchParams({
                            class_id: String(this.classId),
                        });
                        const response = await fetch(`${config.studentsUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            this.students = [];
                            this.setStatus(result.message || 'Failed to load students.', 'error');
                            return;
                        }

                        this.students = (result.students || []).map((student) => ({
                            id: Number(student.id),
                            student_id: student.student_id || '',
                            name: student.name || '',
                        }));

                        if (this.students.length === 0) {
                            this.studentId = null;
                            return;
                        }

                        const wholeClassSelected = String(this.studentId) === '0';
                        if (wholeClassSelected) {
                            return;
                        }

                        const hasSelected = this.studentId && this.students.some((student) => student.id === Number(this.studentId));
                        if (!hasSelected) {
                            this.studentId = this.students[0].id;
                        }
                    } catch (error) {
                        this.students = [];
                        this.setStatus('Unexpected error while loading students.', 'error');
                    } finally {
                        this.loadingStudents = false;
                    }
                },

                queryParamsForStudent() {
                    return new URLSearchParams({
                        student_id: String(this.studentId),
                        session: String(this.session),
                        exam_type: String(this.examType),
                    });
                },

                queryParamsForClass() {
                    return new URLSearchParams({
                        class_id: String(this.classId),
                        session: String(this.session),
                        exam_type: String(this.examType),
                    });
                },

                async previewResult() {
                    this.clearStatus();
                    if (!this.canRunStudentAction()) {
                        this.setStatus('Session, exam type, class, and student are required.', 'error');
                        return;
                    }

                    if (this.isWholeClassSelected()) {
                        this.result = null;
                        this.previewCardUrl = '';
                        this.studentPdfUrl = '';
                        this.generateAllClassCards();
                        return;
                    }

                    this.previewLoading = true;
                    try {
                        const params = this.queryParamsForStudent();
                        const response = await fetch(`${config.previewUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            this.result = null;
                            this.previewCardUrl = '';
                            this.studentPdfUrl = '';
                            this.setStatus(result.message || 'Failed to preview result.', 'error');
                            return;
                        }

                        this.result = result;
                        this.previewCardUrl = `${config.cardPreviewUrl}?${params.toString()}`;
                        this.studentPdfUrl = `${config.studentPdfUrl}?${params.toString()}`;
                        this.setStatus('Result preview loaded.');
                    } catch (error) {
                        this.result = null;
                        this.previewCardUrl = '';
                        this.studentPdfUrl = '';
                        this.setStatus('Unexpected error while loading result preview.', 'error');
                    } finally {
                        this.previewLoading = false;
                    }
                },

                openPreviewInNewTab() {
                    if (!this.previewCardUrl) {
                        return;
                    }
                    window.open(this.previewCardUrl, '_blank');
                },

                downloadPdf() {
                    if (this.isWholeClassSelected() && this.canRunClassAction()) {
                        window.open(`${config.classCardsPdfUrl}?${this.queryParamsForClass().toString()}`, '_blank');
                        return;
                    }

                    if (!this.studentPdfUrl) {
                        return;
                    }
                    window.open(this.studentPdfUrl, '_blank');
                },

                printPreview() {
                    if (this.isWholeClassSelected() && this.canRunClassAction()) {
                        window.open(`${config.classCardsPdfUrl}?${this.queryParamsForClass().toString()}`, '_blank');
                        return;
                    }

                    if (!this.previewCardUrl) {
                        return;
                    }

                    const frame = this.$refs.previewFrame;
                    if (frame && frame.contentWindow) {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                        return;
                    }

                    window.open(this.previewCardUrl, '_blank');
                },

                generateAllClassCards() {
                    this.clearStatus();
                    if (!this.canRunClassAction()) {
                        this.setStatus('Class, session, and exam type are required.', 'error');
                        return;
                    }

                    this.generatingClassCards = true;
                    try {
                        const params = this.queryParamsForClass();
                        window.open(`${config.classCardsPdfUrl}?${params.toString()}`, '_blank');
                        this.setStatus('Generating class result cards PDF.');
                    } finally {
                        this.generatingClassCards = false;
                    }
                },

                async publishResults() {
                    this.clearStatus();
                    if (!this.canRunClassAction()) {
                        this.setStatus('Class, session, and exam type are required.', 'error');
                        return;
                    }

                    this.publishing = true;
                    try {
                        const response = await fetch(config.publishUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                class_id: Number(this.classId),
                                session: this.session,
                                exam_type: this.examType,
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to publish results.', 'error');
                            return;
                        }

                        const notified = Number(result.summary?.notified_users || 0);
                        this.setStatus(`Results published successfully. Notifications sent to ${notified} user(s).`);
                    } catch (error) {
                        this.setStatus('Unexpected error while publishing results.', 'error');
                    } finally {
                        this.publishing = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
