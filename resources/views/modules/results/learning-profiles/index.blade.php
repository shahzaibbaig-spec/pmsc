<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Student Learning Profiles</h2>
            <p class="mt-1 text-sm text-slate-500">Generate academic learning profiles and teacher-editable report comments.</p>
        </div>
    </x-slot>

    <div
        class="py-6 sm:py-8"
        x-data="learningProfilesPage({
            rows: @js($rows),
            session: @js($selectedSession),
            examType: @js($selectedExamType),
            saveUrl: @js(route('results.learning-profiles.comment')),
            csrfToken: @js(csrf_token()),
        })"
    >
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('status'))
                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('status') }}
                </section>
            @endif

            <template x-if="notice.message">
                <section class="rounded-2xl border p-4 text-sm shadow-sm" :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
                    <span x-text="notice.message"></span>
                </section>
            </template>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('results.learning-profiles') }}" class="grid grid-cols-1 gap-4 xl:grid-cols-5">
                    @csrf
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

                    <div>
                        <x-input-label for="exam_type" value="Exam Type" />
                        <select id="exam_type" name="exam_type" class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($examTypes as $examType)
                                <option value="{{ $examType['value'] }}" @selected($selectedExamType === $examType['value'])>{{ $examType['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Load Profiles
                        </button>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            formaction="{{ route('results.learning-profiles.generate') }}"
                            formmethod="POST"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                            @disabled($selectedClassId === null)
                        >
                            Generate Profiles
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <h3 class="text-base font-semibold text-slate-900">Learning Profile Table</h3>
                    <p class="mt-1 text-xs text-slate-500">Review aptitude, learning pattern, and report comment status for each student.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Student Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Average</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Best Aptitude</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Learning Pattern</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Comment Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <template x-if="rows.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No records found. Select filters and click Generate Profiles.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="row in rows" :key="`lp-${row.student_id}`">
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <div class="font-medium" x-text="row.student_name"></div>
                                        <div class="text-xs text-slate-500" x-text="row.student_ref"></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900" x-text="`${Number(row.average || 0).toFixed(2)}%`"></td>
                                    <td class="px-4 py-3 text-sm text-indigo-700" x-text="row.best_aptitude"></td>
                                    <td class="px-4 py-3 text-sm text-slate-700 max-w-xs truncate" :title="row.learning_pattern" x-text="row.learning_pattern"></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(row.comment_status)" x-text="row.comment_status"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <button type="button" class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100" @click="openDrawer(row)">
                                            View
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div x-cloak x-show="drawerOpen" class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-slate-900/50" @click="closeDrawer()"></div>
            <div class="absolute inset-y-0 right-0 w-full max-w-2xl border-l border-slate-200 bg-white shadow-2xl">
                <div class="flex h-full flex-col">
                    <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Student Learning Profile</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                <span x-text="activeRow.student_name || '-'"></span>
                                <span class="mx-1">|</span>
                                <span x-text="activeRow.student_ref || '-'"></span>
                            </p>
                        </div>
                        <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100" @click="closeDrawer()">✕</button>
                    </div>

                    <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Average</p>
                                <p class="mt-1 text-xl font-semibold text-slate-900" x-text="`${Number(activeRow.average || 0).toFixed(2)}%`"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Best Aptitude</p>
                                <p class="mt-1 text-xl font-semibold text-slate-900" x-text="activeRow.best_aptitude || 'Undetermined'"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Strengths</p>
                                <p class="mt-1 text-sm text-slate-700" x-text="activeRow.strengths || 'Not available'"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Support Areas</p>
                                <p class="mt-1 text-sm text-slate-700" x-text="activeRow.support_areas || 'Not available'"></p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Learning Pattern</p>
                            <p class="mt-1 text-sm text-slate-700" x-text="activeRow.learning_pattern || 'Not available'"></p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Auto Comment</label>
                            <textarea rows="4" readonly class="mt-2 block w-full rounded-md border-slate-300 bg-slate-50 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="activeRow.auto_comment"></textarea>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Final Comment (Editable)</label>
                            <textarea rows="6" class="mt-2 block w-full rounded-md border-slate-300 text-sm text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model="activeRow.final_comment"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4">
                        <span class="text-xs text-slate-500">Comment Status: <span class="font-semibold" x-text="activeRow.comment_status || 'Draft'"></span></span>
                        <button type="button" class="inline-flex min-h-10 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50" @click="saveFinalComment()" :disabled="savingComment || !activeRow.student_id">
                            <span x-text="savingComment ? 'Saving...' : 'Save Final Comment'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function learningProfilesPage(config) {
            return {
                rows: Array.isArray(config.rows) ? config.rows : [],
                session: config.session || '',
                examType: config.examType || '',
                saveUrl: config.saveUrl || '',
                csrfToken: config.csrfToken || '',
                drawerOpen: false,
                savingComment: false,
                activeRow: {},
                notice: {
                    type: 'success',
                    message: '',
                },

                openDrawer(row) {
                    this.activeRow = JSON.parse(JSON.stringify(row || {}));
                    this.drawerOpen = true;
                },

                closeDrawer() {
                    this.drawerOpen = false;
                    this.activeRow = {};
                },

                statusClass(status) {
                    if (status === 'Edited') {
                        return 'bg-emerald-100 text-emerald-700';
                    }
                    if (status === 'Auto') {
                        return 'bg-blue-100 text-blue-700';
                    }
                    if (status === 'Draft') {
                        return 'bg-amber-100 text-amber-700';
                    }
                    return 'bg-slate-100 text-slate-700';
                },

                async saveFinalComment() {
                    if (!this.activeRow.student_id || this.saveUrl === '') {
                        return;
                    }

                    this.savingComment = true;
                    this.notice = { type: 'success', message: '' };

                    try {
                        const response = await fetch(this.saveUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                student_id: this.activeRow.student_id,
                                session: this.session,
                                exam_type: this.examType,
                                final_comment: this.activeRow.final_comment || '',
                            }),
                        });

                        const payload = await response.json();
                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to save final comment.');
                        }

                        const index = this.rows.findIndex((row) => Number(row.student_id) === Number(this.activeRow.student_id));
                        if (index !== -1) {
                            this.rows[index].final_comment = payload.final_comment || '';
                            this.rows[index].comment_status = payload.comment_status || 'Edited';
                            this.rows[index].is_edited = !!payload.is_edited;
                            this.activeRow.comment_status = this.rows[index].comment_status;
                        }

                        this.notice = { type: 'success', message: payload.message || 'Final comment saved.' };
                    } catch (error) {
                        this.notice = { type: 'error', message: error.message || 'Unable to save final comment.' };
                    } finally {
                        this.savingComment = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>

