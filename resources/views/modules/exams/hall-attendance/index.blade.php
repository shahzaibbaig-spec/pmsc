<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Exams Hall Attendance</h2>
            <p class="mt-1 text-sm text-slate-500">
                Mark room-wise exam attendance live from seating plan.
                @if(! $canManageAllRooms)
                    Only your assigned rooms are visible.
                @endif
            </p>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="examHallAttendance({
            defaultExamSessionId: @js($selectedExamSessionId),
            defaultRoomId: @js($selectedRoomId),
            initialRooms: @js($rooms->values()->all()),
            optionsUrl: @js(route('exams.hall-attendance.options')),
            sheetUrl: @js(route('exams.hall-attendance.sheet')),
            saveUrl: @js(route('exams.hall-attendance.save')),
            roomSheetPdfUrl: @js(route('exams.hall-attendance.room-sheet-pdf')),
            csrfToken: @js(csrf_token()),
            statusOptions: @js($statusOptions),
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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="exam_session_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</label>
                    <select
                        id="exam_session_id"
                        x-model.number="examSessionId"
                        @change="onExamSessionChanged()"
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

                <div>
                    <label for="room_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Room</label>
                    <select
                        id="room_id"
                        x-model.number="roomId"
                        @change="onRoomChanged()"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :disabled="loadingRooms || rooms.length === 0"
                    >
                        <option value="">Select room</option>
                        <template x-for="room in rooms" :key="`room-${room.id}`">
                            <option :value="room.id" x-text="`${room.name} (Seats: ${room.total_seats})`"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-slate-500" x-text="loadingRooms ? 'Loading rooms...' : `${rooms.length} room(s)`"></p>
                </div>

                <div class="md:col-span-2 flex items-end gap-2">
                    <button
                        type="button"
                        @click="loadSheet()"
                        :disabled="loadingSheet || !examSessionId || !roomId"
                        class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-text="loadingSheet ? 'Loading...' : 'Load Attendance Sheet'"></span>
                    </button>
                    <button
                        type="button"
                        @click="saveAttendance()"
                        :disabled="saving || rows.length === 0"
                        class="inline-flex min-h-11 items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-text="saving ? 'Saving...' : 'Save Attendance'"></span>
                    </button>
                    <button
                        type="button"
                        @click="openPrintableSheet()"
                        :disabled="rows.length === 0 || !examSessionId || !roomId"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Printable PDF
                    </button>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" x-show="meta.room_name !== ''" x-cloak>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Room</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900" x-text="meta.room_name"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900" x-text="meta.exam_session_name"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Invigilators</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900" x-text="meta.invigilators.length ? meta.invigilators.join(', ') : 'Not Assigned'"></p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Seat #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Remarks</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marked?</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="loadingSheet">
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Loading attendance sheet...</td>
                            </tr>
                        </template>
                        <template x-if="!loadingSheet && rows.length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Select exam session and room to load attendance.</td>
                            </tr>
                        </template>
                        <template x-for="(row, index) in rows" :key="`att-row-${row.seat_assignment_id}`">
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900" x-text="row.seat_number"></td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900" x-text="row.student_name"></div>
                                    <div class="text-xs text-slate-500" x-text="row.student_code"></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="row.class_name"></td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <select
                                        x-model="row.status"
                                        class="block min-h-10 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <template x-for="statusOption in statusOptions" :key="`status-${index}-${statusOption}`">
                                            <option :value="statusOption" x-text="labelForStatus(statusOption)"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <input
                                        type="text"
                                        x-model="row.remarks"
                                        maxlength="500"
                                        placeholder="Optional note"
                                        class="block min-h-10 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <span
                                        x-show="row.is_marked"
                                        class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                    >
                                        Marked
                                    </span>
                                    <span
                                        x-show="!row.is_marked"
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                                    >
                                        Unmarked
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        function examHallAttendance(config) {
            return {
                examSessionId: config.defaultExamSessionId ? Number(config.defaultExamSessionId) : null,
                roomId: config.defaultRoomId ? Number(config.defaultRoomId) : null,
                rooms: Array.isArray(config.initialRooms) ? config.initialRooms : [],
                rows: [],
                statusOptions: Array.isArray(config.statusOptions) ? config.statusOptions : ['present', 'absent', 'late'],
                loadingRooms: false,
                loadingSheet: false,
                saving: false,
                status: {
                    message: '',
                    type: 'success',
                },
                summary: {
                    total_seats: 0,
                    marked: 0,
                    present: 0,
                    absent: 0,
                    late: 0,
                    unmarked: 0,
                },
                meta: {
                    room_name: '',
                    exam_session_name: '',
                    invigilators: [],
                },

                init() {
                    if (this.examSessionId && (!this.roomId || !this.rooms.some((room) => Number(room.id) === Number(this.roomId)))) {
                        this.roomId = this.rooms.length ? Number(this.rooms[0].id) : null;
                    }

                    if (this.examSessionId && this.roomId) {
                        this.loadSheet();
                    }
                },

                labelForStatus(status) {
                    return String(status || '')
                        .replace('_', ' ')
                        .replace(/\b\w/g, (char) => char.toUpperCase());
                },

                clearStatus() {
                    this.status.message = '';
                    this.status.type = 'success';
                },

                setStatus(message, type = 'success') {
                    this.status.message = message;
                    this.status.type = type;
                },

                resetSheet() {
                    this.rows = [];
                    this.summary = {
                        total_seats: 0,
                        marked: 0,
                        present: 0,
                        absent: 0,
                        late: 0,
                        unmarked: 0,
                    };
                    this.meta = {
                        room_name: '',
                        exam_session_name: '',
                        invigilators: [],
                    };
                },

                async onExamSessionChanged() {
                    this.clearStatus();
                    this.resetSheet();
                    await this.loadRooms();
                    if (this.examSessionId && this.roomId) {
                        await this.loadSheet();
                    }
                },

                async onRoomChanged() {
                    this.clearStatus();
                    this.resetSheet();
                    if (this.examSessionId && this.roomId) {
                        await this.loadSheet();
                    }
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

                async loadRooms() {
                    if (!this.examSessionId) {
                        this.rooms = [];
                        this.roomId = null;
                        return;
                    }

                    this.loadingRooms = true;
                    try {
                        const params = new URLSearchParams({
                            exam_session_id: String(this.examSessionId),
                        });
                        const response = await fetch(`${config.optionsUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await this.parseJson(response);

                        if (!response.ok) {
                            this.rooms = [];
                            this.roomId = null;
                            this.setStatus(result.message || 'Failed to load rooms.', 'error');
                            return;
                        }

                        this.rooms = Array.isArray(result.rooms) ? result.rooms : [];
                        if (!this.rooms.some((room) => Number(room.id) === Number(this.roomId))) {
                            this.roomId = this.rooms.length ? Number(this.rooms[0].id) : null;
                        }
                    } catch (error) {
                        this.rooms = [];
                        this.roomId = null;
                        this.setStatus('Unexpected error while loading rooms.', 'error');
                    } finally {
                        this.loadingRooms = false;
                    }
                },

                async loadSheet(showStatus = false) {
                    if (!this.examSessionId || !this.roomId) {
                        this.setStatus('Exam session and room are required.', 'error');
                        return;
                    }

                    this.loadingSheet = true;
                    try {
                        const params = new URLSearchParams({
                            exam_session_id: String(this.examSessionId),
                            room_id: String(this.roomId),
                        });
                        const response = await fetch(`${config.sheetUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await this.parseJson(response);

                        if (!response.ok) {
                            this.resetSheet();
                            this.setStatus(result.message || 'Failed to load attendance sheet.', 'error');
                            return;
                        }

                        this.rows = Array.isArray(result.rows) ? result.rows : [];
                        this.summary = result.summary || this.summary;
                        this.meta = {
                            room_name: result.room?.name || '',
                            exam_session_name: `${result.exam_session?.name || ''} (${result.exam_session?.session || '-'})`,
                            invigilators: Array.isArray(result.invigilators) ? result.invigilators : [],
                        };

                        if (showStatus) {
                            this.setStatus('Attendance sheet loaded successfully.');
                        }
                    } catch (error) {
                        this.resetSheet();
                        this.setStatus('Unexpected error while loading attendance sheet.', 'error');
                    } finally {
                        this.loadingSheet = false;
                    }
                },

                async saveAttendance() {
                    if (!this.examSessionId || !this.roomId || this.rows.length === 0) {
                        this.setStatus('Load attendance sheet first.', 'error');
                        return;
                    }

                    this.saving = true;
                    try {
                        const payload = {
                            exam_session_id: Number(this.examSessionId),
                            room_id: Number(this.roomId),
                            records: this.rows.map((row) => ({
                                student_id: Number(row.student_id),
                                status: row.status,
                                remarks: row.remarks || '',
                            })),
                        };

                        const response = await fetch(config.saveUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });
                        const result = await this.parseJson(response);

                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to save attendance.', 'error');
                            return;
                        }

                        this.summary = result.summary || this.summary;
                        this.rows = this.rows.map((row) => ({ ...row, is_marked: true }));
                        this.setStatus(result.message || 'Attendance saved successfully.');
                    } catch (error) {
                        this.setStatus('Unexpected error while saving attendance.', 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                openPrintableSheet() {
                    if (!this.examSessionId || !this.roomId) {
                        return;
                    }

                    const params = new URLSearchParams({
                        exam_session_id: String(this.examSessionId),
                        room_id: String(this.roomId),
                    });
                    window.open(`${config.roomSheetPdfUrl}?${params.toString()}`, '_blank');
                },
            };
        }
    </script>
</x-app-layout>
