<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Timetable Viewer
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Class Timetable</h3>
                    <p class="text-sm text-gray-600 mt-1">View and manually edit timetable entries with live hard-constraint validation.</p>

                    <div id="actionMessage" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="class_section_id" value="Class Section" />
                            <select id="class_section_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($classSections as $section)
                                    <option value="{{ $section['id'] }}">{{ $section['display_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button id="loadBtn" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Load Timetable
                            </button>
                        </div>
                        <div class="flex items-end gap-2">
                            <a id="exportPdfBtn" href="#" target="_blank"
                               class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 opacity-50 pointer-events-none">
                                Export PDF
                            </a>
                            <a id="exportCsvBtn" href="#"
                               class="inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 opacity-50 pointer-events-none">
                                Export CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 id="gridTitle" class="text-lg font-medium text-gray-900">Weekly Grid</h3>
                        <p id="gridMeta" class="text-sm text-gray-600">-</p>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead id="gridHead" class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Day</th>
                                </tr>
                            </thead>
                            <tbody id="gridBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td class="px-4 py-8 text-center text-sm text-gray-500">Select filters and load timetable.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden bg-black/40 px-4 py-8">
        <div class="mx-auto mt-8 w-full max-w-2xl rounded-lg bg-white shadow-xl">
            <div class="border-b px-6 py-4">
                <h4 class="text-lg font-semibold text-gray-900">Edit Timetable Entry</h4>
                <p id="editSlotMeta" class="text-sm text-gray-600 mt-1">-</p>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div id="editValidationMessage" class="hidden rounded-md p-3 text-sm"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="modal_subject_id" value="Subject" />
                        <select id="modal_subject_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                    </div>
                    <div>
                        <x-input-label for="modal_teacher_id" value="Teacher" />
                        <select id="modal_teacher_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                    </div>
                    <div>
                        <x-input-label for="modal_room_id" value="Room" />
                        <select id="modal_room_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                    </div>
                </div>
            </div>
            <div class="border-t px-6 py-4 flex justify-end gap-2">
                <button type="button" id="closeModalBtn" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" id="saveEntryBtn" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save</button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const sessionInput = document.getElementById('session');
        const classSectionInput = document.getElementById('class_section_id');
        const loadBtn = document.getElementById('loadBtn');
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        const exportCsvBtn = document.getElementById('exportCsvBtn');
        const actionMessage = document.getElementById('actionMessage');
        const gridTitle = document.getElementById('gridTitle');
        const gridMeta = document.getElementById('gridMeta');
        const gridHead = document.getElementById('gridHead');
        const gridBody = document.getElementById('gridBody');

        const editModal = document.getElementById('editModal');
        const editSlotMeta = document.getElementById('editSlotMeta');
        const editValidationMessage = document.getElementById('editValidationMessage');
        const modalSubjectInput = document.getElementById('modal_subject_id');
        const modalTeacherInput = document.getElementById('modal_teacher_id');
        const modalRoomInput = document.getElementById('modal_room_id');
        const saveEntryBtn = document.getElementById('saveEntryBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');

        let timetablePayload = null;
        let modalState = {
            day_of_week: null,
            slot_index: null,
            entry_id: null
        };

        function showActionMessage(message, type = 'success') {
            actionMessage.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            actionMessage.textContent = message;
            if (type === 'error') {
                actionMessage.classList.add('bg-red-50', 'text-red-700');
            } else {
                actionMessage.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function clearActionMessage() {
            actionMessage.classList.add('hidden');
            actionMessage.textContent = '';
        }

        function showEditValidation(message, type = 'success') {
            editValidationMessage.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            editValidationMessage.textContent = message;
            if (type === 'error') {
                editValidationMessage.classList.add('bg-red-50', 'text-red-700');
            } else {
                editValidationMessage.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function clearEditValidation() {
            editValidationMessage.classList.add('hidden');
            editValidationMessage.textContent = '';
        }

        function updateExportLinks() {
            const session = sessionInput.value;
            const classSectionId = classSectionInput.value;

            if (!session || !classSectionId) {
                exportPdfBtn.classList.add('opacity-50', 'pointer-events-none');
                exportCsvBtn.classList.add('opacity-50', 'pointer-events-none');
                exportPdfBtn.href = '#';
                exportCsvBtn.href = '#';
                return;
            }

            const pdfParams = new URLSearchParams({
                session,
                type: 'class',
                class_section_id: classSectionId
            });
            const csvParams = new URLSearchParams({
                session,
                class_section_id: classSectionId
            });

            exportPdfBtn.href = `{{ route('principal.timetable.export.pdf') }}?${pdfParams.toString()}`;
            exportCsvBtn.href = `{{ route('principal.timetable.export.csv') }}?${csvParams.toString()}`;
            exportPdfBtn.classList.remove('opacity-50', 'pointer-events-none');
            exportCsvBtn.classList.remove('opacity-50', 'pointer-events-none');
        }

        function renderGrid() {
            if (!timetablePayload) {
                gridHead.innerHTML = `
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Day</th>
                    </tr>
                `;
                gridBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-gray-500">Select filters and load timetable.</td></tr>';
                gridMeta.textContent = '-';
                gridTitle.textContent = 'Weekly Grid';
                return;
            }

            gridTitle.textContent = timetablePayload.class_section?.display_name || 'Weekly Grid';

            const slotHeaders = timetablePayload.slot_headers || [];
            const rows = timetablePayload.rows || [];

            gridHead.innerHTML = `
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 min-w-28">Day</th>
                    ${slotHeaders.map(slot => `
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-600 min-w-52">
                            <div>Slot ${slot.slot_index}</div>
                            <div class="mt-1 text-[10px] normal-case text-gray-500">${window.NSMS.escapeHtml(slot.start_time)} - ${window.NSMS.escapeHtml(slot.end_time)}</div>
                        </th>
                    `).join('')}
                </tr>
            `;

            gridBody.innerHTML = rows.map(row => `
                <tr>
                    <td class="px-4 py-2 text-sm font-medium text-gray-800">${window.NSMS.escapeHtml(row.day_label)}</td>
                    ${row.cells.map(cell => {
                        if (!cell.entry) {
                            return `
                                <td class="px-2 py-2 align-top">
                                    <button type="button"
                                            class="cell-edit-btn w-full rounded border border-dashed border-gray-300 px-2 py-4 text-xs text-gray-500 hover:bg-gray-50"
                                            data-day="${cell.day_of_week}"
                                            data-slot="${cell.slot_index}">
                                        + Set Entry
                                    </button>
                                </td>
                            `;
                        }

                        return `
                            <td class="px-2 py-2 align-top">
                                <div class="rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-700">
                                    <div class="font-semibold text-gray-900">${window.NSMS.escapeHtml(cell.entry.subject_name)}</div>
                                    <div class="mt-1">${window.NSMS.escapeHtml(cell.entry.teacher_name)}</div>
                                    <div>${window.NSMS.escapeHtml(cell.entry.room_name)}</div>
                                    <button type="button"
                                            class="cell-edit-btn mt-2 inline-flex rounded bg-indigo-600 px-2 py-1 text-[11px] text-white hover:bg-indigo-700"
                                            data-entry-id="${cell.entry.id}"
                                            data-day="${cell.day_of_week}"
                                            data-slot="${cell.slot_index}"
                                            data-subject-id="${cell.entry.subject_id}"
                                            data-teacher-id="${cell.entry.teacher_id}"
                                            data-room-id="${cell.entry.room_id}">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        `;
                    }).join('')}
                </tr>
            `).join('');

            gridMeta.textContent = `${rows.length} days x ${slotHeaders.length} slots`;
        }

        function renderModalOptions(selectedSubjectId = null, selectedTeacherId = null, selectedRoomId = null) {
            const subjects = timetablePayload?.options?.subjects || [];
            const rooms = timetablePayload?.options?.rooms || [];
            const teachersBySubject = timetablePayload?.options?.teachers_by_subject || {};

            modalSubjectInput.innerHTML = subjects.map(subject => {
                const label = subject.code ? `${subject.name} (${subject.code})` : subject.name;
                return `<option value="${subject.id}">${window.NSMS.escapeHtml(label)}</option>`;
            }).join('');

            if (selectedSubjectId) {
                modalSubjectInput.value = String(selectedSubjectId);
            }

            const roomText = rooms.map(room => `
                <option value="${room.id}">
                    ${window.NSMS.escapeHtml(room.name)} (${window.NSMS.escapeHtml(room.type)})
                </option>
            `).join('');
            modalRoomInput.innerHTML = roomText;

            if (selectedRoomId) {
                modalRoomInput.value = String(selectedRoomId);
            }

            const currentSubject = Number(modalSubjectInput.value || 0);
            const teacherOptions = teachersBySubject[currentSubject] || [];

            modalTeacherInput.innerHTML = teacherOptions.map(teacher => {
                const extra = teacher.employee_code || teacher.teacher_id || '';
                const text = extra ? `${teacher.name} (${extra})` : teacher.name;
                return `<option value="${teacher.id}">${window.NSMS.escapeHtml(text)}</option>`;
            }).join('');

            if (selectedTeacherId && teacherOptions.some(t => Number(t.id) === Number(selectedTeacherId))) {
                modalTeacherInput.value = String(selectedTeacherId);
            }
        }

        function openEditModal(button) {
            if (!timetablePayload) {
                return;
            }

            modalState = {
                day_of_week: button.dataset.day,
                slot_index: Number(button.dataset.slot),
                entry_id: button.dataset.entryId ? Number(button.dataset.entryId) : null
            };

            editSlotMeta.textContent = `${(button.dataset.day || '').toUpperCase()} | Slot ${button.dataset.slot}`;

            const subjectId = button.dataset.subjectId ? Number(button.dataset.subjectId) : null;
            const teacherId = button.dataset.teacherId ? Number(button.dataset.teacherId) : null;
            const roomId = button.dataset.roomId ? Number(button.dataset.roomId) : null;

            renderModalOptions(subjectId, teacherId, roomId);
            clearEditValidation();
            editModal.classList.remove('hidden');

            validateDraft();
        }

        function closeEditModal() {
            editModal.classList.add('hidden');
            modalState = { day_of_week: null, slot_index: null, entry_id: null };
            clearEditValidation();
        }

        function draftPayload(validateOnly = true) {
            return {
                entry_id: modalState.entry_id,
                session: sessionInput.value,
                class_section_id: Number(classSectionInput.value),
                day_of_week: modalState.day_of_week,
                slot_index: Number(modalState.slot_index),
                subject_id: Number(modalSubjectInput.value),
                teacher_id: Number(modalTeacherInput.value),
                room_id: Number(modalRoomInput.value),
                validate_only: validateOnly
            };
        }

        async function validateDraft() {
            if (!modalState.day_of_week || !modalState.slot_index) {
                return;
            }

            const payload = draftPayload(true);
            if (!payload.subject_id || !payload.teacher_id || !payload.room_id) {
                showEditValidation('Select subject, teacher, and room.', 'error');
                return;
            }

            const response = await fetch(`{{ route('api.timetable.entry.update') }}`, {
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
                const message = result.message || (result.conflicts?.[0]?.message ?? 'Invalid entry.');
                showEditValidation(message, 'error');
                return;
            }

            showEditValidation(result.message || 'Valid slot.', 'success');
        }

        const debouncedValidateDraft = window.NSMS.debounce(validateDraft, 250);

        async function loadTimetable() {
            clearActionMessage();
            updateExportLinks();

            const session = sessionInput.value;
            const classSectionId = Number(classSectionInput.value);

            if (!session || !classSectionId) {
                showActionMessage('Session and class section are required.', 'error');
                return;
            }

            loadBtn.disabled = true;
            loadBtn.textContent = 'Loading...';
            gridBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-gray-500">Loading timetable...</td></tr>';

            const params = new URLSearchParams({
                session,
                class_section_id: classSectionId
            });

            try {
                const response = await fetch(`{{ route('api.timetable.class') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();
                if (!response.ok) {
                    showActionMessage(result.message || 'Failed to load timetable.', 'error');
                    timetablePayload = null;
                    renderGrid();
                    return;
                }

                timetablePayload = result;
                renderGrid();
            } catch (error) {
                showActionMessage('Unexpected error while loading timetable.', 'error');
                timetablePayload = null;
                renderGrid();
            } finally {
                loadBtn.disabled = false;
                loadBtn.textContent = 'Load Timetable';
            }
        }

        async function saveEntry() {
            const payload = draftPayload(false);
            if (!payload.subject_id || !payload.teacher_id || !payload.room_id) {
                showEditValidation('Subject, teacher and room are required.', 'error');
                return;
            }

            saveEntryBtn.disabled = true;
            saveEntryBtn.textContent = 'Saving...';

            try {
                const response = await fetch(`{{ route('api.timetable.entry.update') }}`, {
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
                    const message = result.message || (result.conflicts?.[0]?.message ?? 'Failed to save entry.');
                    showEditValidation(message, 'error');
                    return;
                }

                closeEditModal();
                showActionMessage(result.message || 'Timetable entry saved.');
                await loadTimetable();
            } catch (error) {
                showEditValidation('Unexpected error while saving entry.', 'error');
            } finally {
                saveEntryBtn.disabled = false;
                saveEntryBtn.textContent = 'Save';
            }
        }

        loadBtn.addEventListener('click', loadTimetable);

        sessionInput.addEventListener('change', updateExportLinks);
        classSectionInput.addEventListener('change', updateExportLinks);

        gridBody.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (target.classList.contains('cell-edit-btn')) {
                openEditModal(target);
            }
        });

        modalSubjectInput.addEventListener('change', () => {
            const selectedSubjectId = Number(modalSubjectInput.value);
            renderModalOptions(selectedSubjectId, null, Number(modalRoomInput.value || 0));
            debouncedValidateDraft();
        });

        modalTeacherInput.addEventListener('change', debouncedValidateDraft);
        modalRoomInput.addEventListener('change', debouncedValidateDraft);

        closeModalBtn.addEventListener('click', closeEditModal);
        saveEntryBtn.addEventListener('click', saveEntry);

        editModal.addEventListener('click', (event) => {
            if (event.target === editModal) {
                closeEditModal();
            }
        });

        updateExportLinks();
        if (classSectionInput.value) {
            loadTimetable();
        }
    </script>
</x-app-layout>
