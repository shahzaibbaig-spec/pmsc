<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Principal Live Exam Attendance Monitor</h2>
            <p class="mt-1 text-sm text-slate-500">Track room-wise attendance, absentees, and unmarked seats in real time.</p>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="liveExamAttendanceMonitor({
            defaultExamSessionId: @js($selectedExamSessionId),
            dataUrl: @js(route('principal.exams.live-attendance-monitor.data')),
            initialPayload: @js($payload),
        })"
        x-init="init()"
    >
        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <div
            x-show="status.message !== ''"
            x-cloak
            class="rounded-xl border px-4 py-3 text-sm"
            :class="status.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
            x-text="status.message"
        ></div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="monitor_exam_session_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</label>
                    <select
                        id="monitor_exam_session_id"
                        x-model.number="examSessionId"
                        @change="refreshData(true)"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Select exam session</option>
                        @foreach($examSessions as $examSession)
                            <option value="{{ $examSession->id }}">
                                {{ $examSession->name }} ({{ $examSession->session }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2 md:col-span-3">
                    <button
                        type="button"
                        @click="refreshData(true)"
                        :disabled="loading || !examSessionId"
                        class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-text="loading ? 'Refreshing...' : 'Refresh Now'"></span>
                    </button>
                    <button
                        type="button"
                        @click="toggleAutoRefresh()"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <span x-text="autoRefresh ? 'Auto Refresh: ON' : 'Auto Refresh: OFF'"></span>
                    </button>
                    <span class="inline-flex min-h-11 items-center text-xs text-slate-500" x-text="`Last update: ${lastUpdated}`"></span>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-7">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Rooms</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="summary.total_rooms"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Seats</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="summary.total_seats"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marked</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-700" x-text="summary.marked"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Present</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700" x-text="summary.present"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Absent</p>
                <p class="mt-2 text-2xl font-semibold text-rose-700" x-text="summary.absent"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Late</p>
                <p class="mt-2 text-2xl font-semibold text-amber-700" x-text="summary.late"></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unmarked</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="summary.unmarked"></p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Invigilators</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Seats</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marked</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Present</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Absent</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Late</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Unmarked</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="loading">
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">Refreshing monitor data...</td>
                            </tr>
                        </template>
                        <template x-if="!loading && rooms.length === 0">
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">No room attendance data found for selected session.</td>
                            </tr>
                        </template>
                        <template x-for="room in rooms" :key="`monitor-room-${room.room_id}`">
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900" x-text="room.room_name"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="room.invigilators.length ? room.invigilators.join(', ') : 'Not Assigned'"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="room.total_seats"></td>
                                <td class="px-4 py-3 text-sm text-indigo-700 font-semibold" x-text="room.marked"></td>
                                <td class="px-4 py-3 text-sm text-emerald-700 font-semibold" x-text="room.present"></td>
                                <td class="px-4 py-3 text-sm text-rose-700 font-semibold" x-text="room.absent"></td>
                                <td class="px-4 py-3 text-sm text-amber-700 font-semibold" x-text="room.late"></td>
                                <td class="px-4 py-3 text-sm text-slate-700 font-semibold" x-text="room.unmarked"></td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="w-40 rounded-full bg-slate-100">
                                        <div
                                            class="h-2 rounded-full bg-indigo-600"
                                            :style="`width: ${Math.min(100, Number(room.progress || 0))}%`"
                                        ></div>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500" x-text="`${room.progress}%`"></div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        function liveExamAttendanceMonitor(config) {
            return {
                examSessionId: config.defaultExamSessionId ? Number(config.defaultExamSessionId) : null,
                summary: (config.initialPayload && config.initialPayload.summary) ? config.initialPayload.summary : {
                    total_rooms: 0,
                    total_seats: 0,
                    marked: 0,
                    present: 0,
                    absent: 0,
                    late: 0,
                    unmarked: 0,
                },
                rooms: (config.initialPayload && Array.isArray(config.initialPayload.rooms)) ? config.initialPayload.rooms : [],
                loading: false,
                autoRefresh: true,
                timerId: null,
                lastUpdated: new Date().toLocaleTimeString(),
                status: {
                    message: '',
                    type: 'success',
                },

                init() {
                    this.startTimer();
                },

                setStatus(message, type = 'success') {
                    this.status.message = message;
                    this.status.type = type;
                },

                clearStatus() {
                    this.status.message = '';
                    this.status.type = 'success';
                },

                startTimer() {
                    this.stopTimer();
                    this.timerId = setInterval(() => {
                        if (this.autoRefresh && this.examSessionId) {
                            this.refreshData(false);
                        }
                    }, 30000);
                },

                stopTimer() {
                    if (this.timerId) {
                        clearInterval(this.timerId);
                        this.timerId = null;
                    }
                },

                toggleAutoRefresh() {
                    this.autoRefresh = !this.autoRefresh;
                    this.setStatus(this.autoRefresh ? 'Auto refresh enabled.' : 'Auto refresh paused.');
                },

                async parseJson(response) {
                    const raw = await response.text();
                    if (!raw) {
                        return {};
                    }

                    try {
                        return JSON.parse(raw);
                    } catch (error) {
                        return { raw };
                    }
                },

                async refreshData(showStatus) {
                    if (!this.examSessionId) {
                        this.summary = {
                            total_rooms: 0,
                            total_seats: 0,
                            marked: 0,
                            present: 0,
                            absent: 0,
                            late: 0,
                            unmarked: 0,
                        };
                        this.rooms = [];
                        return;
                    }

                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            exam_session_id: String(this.examSessionId),
                        });
                        const response = await fetch(`${config.dataUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await this.parseJson(response);

                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to refresh monitor data.', 'error');
                            return;
                        }

                        this.summary = result.summary || this.summary;
                        this.rooms = Array.isArray(result.rooms) ? result.rooms : [];
                        this.lastUpdated = new Date().toLocaleTimeString();

                        if (showStatus) {
                            this.setStatus('Monitor refreshed successfully.');
                        } else {
                            this.clearStatus();
                        }
                    } catch (error) {
                        this.setStatus('Unexpected error while refreshing monitor.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
