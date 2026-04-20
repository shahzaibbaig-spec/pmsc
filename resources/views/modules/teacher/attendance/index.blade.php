<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mark Attendance
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Attendance Filters</h3>
                    <p class="mt-1 text-sm text-gray-600">Default date is today. One tap can mark all present, then adjust exceptions.</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <x-input-label for="attendanceDate" value="Date" />
                            <x-text-input id="attendanceDate" type="date" class="mt-1 block w-full min-h-11" value="{{ $defaultDate }}" />
                        </div>
                        <div>
                            <x-input-label for="attendanceSession" value="Session" />
                            <select id="attendanceSession" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @forelse($sessions as $session)
                                    <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                                @empty
                                    <option value="">No session found</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="classId" value="Class" />
                            <select id="classId" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @forelse($classes as $classRoom)
                                    <option value="{{ $classRoom['class_id'] }}">{{ $classRoom['class_name'] }} ({{ $classRoom['session'] }}, {{ $classRoom['active_students'] }} students)</option>
                                @empty
                                    <option value="">No class assignment found</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button id="loadSheetBtn" type="button" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Load Students
                            </button>
                        </div>
                    </div>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Student Attendance</h3>
                        <div class="flex flex-wrap gap-2">
                            <button id="presentAllBtn" type="button" class="inline-flex min-h-11 items-center justify-center rounded-md border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                                Present All
                            </button>
                            <button id="saveAttendanceBtn" type="button" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Save Attendance
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <label for="perPage" class="text-xs font-medium uppercase tracking-wider text-gray-500">Per Page</label>
                            <select id="perPage" class="mt-1 block min-h-10 w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <p id="paginationInfo" class="text-sm text-gray-600">-</p>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-[760px] divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="sticky left-0 z-20 bg-gray-50 px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Father Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                </tr>
                            </thead>
                            <tbody id="studentsBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Load a class to mark attendance.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button id="prevPageBtn" type="button" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            Previous
                        </button>
                        <button id="nextPageBtn" type="button" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const dateInput = document.getElementById('attendanceDate');
        const sessionInput = document.getElementById('attendanceSession');
        const classInput = document.getElementById('classId');
        const loadSheetBtn = document.getElementById('loadSheetBtn');
        const presentAllBtn = document.getElementById('presentAllBtn');
        const saveAttendanceBtn = document.getElementById('saveAttendanceBtn');
        const studentsBody = document.getElementById('studentsBody');
        const messageBox = document.getElementById('messageBox');
        const perPageInput = document.getElementById('perPage');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');

        const state = {
            students: [],
            page: 1,
            per_page: 10,
        };

        function showMessage(message, type = 'success') {
            messageBox.classList.remove('hidden');
            messageBox.classList.remove('bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            messageBox.textContent = message;

            if (type === 'error') {
                messageBox.classList.add('bg-red-50', 'text-red-700');
            } else {
                messageBox.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function clearMessage() {
            messageBox.classList.add('hidden');
            messageBox.textContent = '';
        }

        function renderSessionOptions(sessions, selectedSession = '') {
            if (!sessionInput || !Array.isArray(sessions) || sessions.length === 0) {
                return;
            }

            const desired = String(selectedSession || sessionInput.value || '').trim();
            sessionInput.innerHTML = sessions
                .map((session) => {
                    const value = String(session || '').trim();
                    const selected = desired !== '' ? value === desired : value === String(sessionInput.value || '').trim();
                    return `<option value="${window.NSMS.escapeHtml(value)}"${selected ? ' selected' : ''}>${window.NSMS.escapeHtml(value)}</option>`;
                })
                .join('');
        }

        async function reloadClassOptions() {
            const date = dateInput.value;
            if (!date) {
                return;
            }

            const params = new URLSearchParams({ date });
            const session = sessionInput ? String(sessionInput.value || '').trim() : '';
            if (session !== '') {
                params.set('session', session);
            }
            const response = await fetch(`{{ route('teacher.attendance.options') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                return;
            }

            const result = await response.json();
            const classes = result.classes || [];
            const sessions = Array.isArray(result.sessions) ? result.sessions : [];
            const selectedSession = String(result.selected_session || session || '').trim();

            renderSessionOptions(sessions, selectedSession);

            classInput.innerHTML = '';

            if (!classes.length) {
                classInput.innerHTML = '<option value="">No class assignment found</option>';
                return;
            }

            classInput.innerHTML = classes
                .map(row => `<option value="${row.class_id}">${window.NSMS.escapeHtml(row.class_name)} (${window.NSMS.escapeHtml(row.session || '')}, ${Number(row.active_students || 0)} students)</option>`)
                .join('');
        }

        function totalPages() {
            return Math.max(1, Math.ceil(state.students.length / state.per_page));
        }

        function pagedStudents() {
            const start = (state.page - 1) * state.per_page;
            const end = start + state.per_page;
            return state.students.slice(start, end);
        }

        function statusPill(studentId, status, currentStatus) {
            const active = currentStatus === status;

            let classes = 'status-btn inline-flex min-h-10 min-w-[88px] items-center justify-center rounded-full border px-3 py-1 text-xs font-semibold transition';
            if (!active) {
                classes += ' bg-white text-gray-700 border-gray-300 hover:bg-gray-100';
            } else if (status === 'present') {
                classes += ' bg-emerald-100 text-emerald-700 border-emerald-300';
            } else if (status === 'absent') {
                classes += ' bg-red-100 text-red-700 border-red-300';
            } else {
                classes += ' bg-amber-100 text-amber-700 border-amber-300';
            }

            return `<button type="button" class="${classes}" data-student-id="${studentId}" data-status="${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</button>`;
        }

        function renderRows() {
            if (!state.students.length) {
                studentsBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No students found in selected class.</td></tr>';
                paginationInfo.textContent = 'No records';
                prevPageBtn.disabled = true;
                nextPageBtn.disabled = true;
                return;
            }

            const rows = pagedStudents();
            studentsBody.innerHTML = rows.map((student) => `
                <tr>
                    <td class="sticky left-0 z-10 bg-white px-4 py-2 text-sm text-gray-800">
                        <div class="font-medium">${window.NSMS.escapeHtml(student.name)}</div>
                        <div class="text-xs text-gray-500">${window.NSMS.escapeHtml(student.student_id)}</div>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(student.father_name ?? '-')}</td>
                    <td class="px-4 py-2">
                        <div class="flex flex-wrap items-center gap-2">
                            ${statusPill(student.id, 'present', student.status)}
                            ${statusPill(student.id, 'absent', student.status)}
                            ${statusPill(student.id, 'leave', student.status)}
                        </div>
                    </td>
                </tr>
            `).join('');

            const pages = totalPages();
            paginationInfo.textContent = `Page ${state.page} of ${pages} | Total: ${state.students.length}`;
            prevPageBtn.disabled = state.page <= 1;
            nextPageBtn.disabled = state.page >= pages;
        }

        async function loadSheet() {
            clearMessage();

            const classId = Number(classInput.value);
            const date = dateInput.value;
            const session = sessionInput ? String(sessionInput.value || '').trim() : '';

            if (!classId || !date) {
                showMessage('Date and class are required.', 'error');
                return;
            }

            loadSheetBtn.disabled = true;
            loadSheetBtn.textContent = 'Loading...';
            studentsBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Loading students...</td></tr>';

            const params = new URLSearchParams({
                class_id: classId,
                date: date,
            });
            if (session !== '') {
                params.set('session', session);
            }

            try {
                const response = await fetch(`{{ route('teacher.attendance.sheet') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();
                if (!response.ok) {
                    showMessage(result.message || 'Failed to load attendance sheet.', 'error');
                    studentsBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
                    return;
                }

                state.students = result.students || [];
                state.page = 1;
                renderRows();
            } catch (error) {
                showMessage('Unexpected error while loading attendance sheet.', 'error');
                studentsBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
            } finally {
                loadSheetBtn.disabled = false;
                loadSheetBtn.textContent = 'Load Students';
            }
        }

        function markAllPresent() {
            clearMessage();
            if (!state.students.length) {
                showMessage('Load students first.', 'error');
                return;
            }

            state.students = state.students.map((student) => ({
                ...student,
                status: 'present',
            }));
            renderRows();
            showMessage('All students marked Present. Adjust exceptions if needed.');
        }

        async function saveAttendance() {
            clearMessage();

            const classId = Number(classInput.value);
            const date = dateInput.value;
            const session = sessionInput ? String(sessionInput.value || '').trim() : '';

            if (!classId || !date) {
                showMessage('Date and class are required.', 'error');
                return;
            }

            if (!state.students.length) {
                showMessage('No students to save.', 'error');
                return;
            }

            const shouldSave = window.confirm('Confirm save attendance for this class and date?');
            if (!shouldSave) {
                return;
            }

            saveAttendanceBtn.disabled = true;
            saveAttendanceBtn.textContent = 'Saving...';

            const payload = {
                class_id: classId,
                date: date,
                session: session,
                records: state.students.map((student) => ({
                    student_id: student.id,
                    status: student.status
                }))
            };

            try {
                const response = await fetch(`{{ route('teacher.attendance.mark') }}`, {
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
                    showMessage(result.message || 'Failed to save attendance.', 'error');
                    return;
                }

                showMessage('Attendance saved successfully.');
            } catch (error) {
                showMessage('Unexpected error while saving attendance.', 'error');
            } finally {
                saveAttendanceBtn.disabled = false;
                saveAttendanceBtn.textContent = 'Save Attendance';
            }
        }

        studentsBody.addEventListener('click', (event) => {
            const target = event.target;
            if (!target.classList.contains('status-btn')) {
                return;
            }

            const studentId = Number(target.dataset.studentId);
            const status = target.dataset.status;
            const student = state.students.find(row => Number(row.id) === studentId);
            if (!student) {
                return;
            }

            student.status = status;
            renderRows();
        });

        dateInput.addEventListener('change', async () => {
            await reloadClassOptions();
            state.students = [];
            state.page = 1;
            renderRows();
        });

        sessionInput?.addEventListener('change', async () => {
            await reloadClassOptions();
            state.students = [];
            state.page = 1;
            renderRows();
        });

        perPageInput.addEventListener('change', () => {
            state.per_page = Number(perPageInput.value || 10);
            state.page = 1;
            renderRows();
        });

        prevPageBtn.addEventListener('click', () => {
            if (state.page > 1) {
                state.page -= 1;
                renderRows();
            }
        });

        nextPageBtn.addEventListener('click', () => {
            const pages = totalPages();
            if (state.page < pages) {
                state.page += 1;
                renderRows();
            }
        });

        loadSheetBtn.addEventListener('click', loadSheet);
        presentAllBtn.addEventListener('click', markAllPresent);
        saveAttendanceBtn.addEventListener('click', saveAttendance);

        if (classInput.value) {
            loadSheet();
        } else {
            renderRows();
        }
    </script>
</x-app-layout>
