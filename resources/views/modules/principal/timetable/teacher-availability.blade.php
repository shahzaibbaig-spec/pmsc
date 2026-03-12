<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Teacher Availability
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Availability Matrix Editor</h3>
                    <p class="text-sm text-gray-600 mt-1">Set each teacher's available periods for Mon-Sat. Checked means available.</p>

                    <div id="actionMessage" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="teacher_id" value="Teacher" />
                            <select id="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Teacher</option>
                            </select>
                        </div>
                        <div class="md:col-span-3 flex items-end gap-3">
                            <button id="loadMatrixBtn" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Load Matrix
                            </button>
                            <button id="saveMatrixBtn" type="button" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                                Save Availability
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Matrix</h3>
                        <p id="matrixMeta" class="text-sm text-gray-600">Select a teacher and click Load Matrix.</p>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead id="matrixHead" class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Day</th>
                                </tr>
                            </thead>
                            <tbody id="matrixBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td class="px-4 py-8 text-center text-sm text-gray-500">No matrix loaded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Availability List</h3>
                        <div class="flex flex-col md:flex-row gap-2">
                            <input id="searchInput" type="text" placeholder="Search by day/slot/status"
                                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <select id="dayFilter" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Days</option>
                            </select>
                            <select id="perPageInput" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Day</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Slot</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                </tr>
                            </thead>
                            <tbody id="listBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Select a teacher to load availability.</td>
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

        const actionMessage = document.getElementById('actionMessage');
        const teacherInput = document.getElementById('teacher_id');
        const loadMatrixBtn = document.getElementById('loadMatrixBtn');
        const saveMatrixBtn = document.getElementById('saveMatrixBtn');
        const matrixMeta = document.getElementById('matrixMeta');
        const matrixHead = document.getElementById('matrixHead');
        const matrixBody = document.getElementById('matrixBody');

        const searchInput = document.getElementById('searchInput');
        const dayFilter = document.getElementById('dayFilter');
        const perPageInput = document.getElementById('perPageInput');
        const listBody = document.getElementById('listBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');

        let pageOptions = {
            teachers: [],
            slot_headers: [],
            days: []
        };

        let matrixRows = [];

        let listState = {
            teacher_id: '',
            day_of_week: '',
            search: '',
            page: 1,
            per_page: 10
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

        function renderOptions() {
            const teacherOptions = ['<option value="">Select Teacher</option>'].concat(
                pageOptions.teachers.map(teacher => {
                    const descriptor = teacher.employee_code || teacher.designation || teacher.email || '';
                    const display = descriptor ? `${teacher.name} (${descriptor})` : teacher.name;
                    return `<option value="${teacher.id}">${window.NSMS.escapeHtml(display)}</option>`;
                })
            );
            teacherInput.innerHTML = teacherOptions.join('');

            const dayOptions = ['<option value="">All Days</option>'].concat(
                pageOptions.days.map(day => `<option value="${day}">${window.NSMS.escapeHtml(day.toUpperCase())}</option>`)
            );
            dayFilter.innerHTML = dayOptions.join('');
        }

        async function loadPageOptions() {
            const response = await fetch(`{{ route('principal.timetable.teacher-availability.options') }}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Failed to load options');
            }

            const result = await response.json();
            pageOptions.teachers = result.teachers || [];
            pageOptions.slot_headers = result.slot_headers || [];
            pageOptions.days = result.days || [];
            renderOptions();
        }

        function renderMatrix(headers, rows) {
            if (!headers.length) {
                matrixHead.innerHTML = `
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Day</th>
                    </tr>
                `;
                matrixBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-gray-500">No time slots available. Regenerate slots in Timetable Settings.</td></tr>';
                matrixMeta.textContent = 'No slot headers found.';
                return;
            }

            matrixHead.innerHTML = `
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 min-w-28">Day</th>
                    ${headers.map(header => `
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-600 min-w-32">
                            <div>Slot ${header.slot_index}</div>
                            <div class="text-[10px] normal-case text-gray-500 mt-1">${window.NSMS.escapeHtml(header.start_time)} - ${window.NSMS.escapeHtml(header.end_time)}</div>
                        </th>
                    `).join('')}
                </tr>
            `;

            matrixBody.innerHTML = rows.map(row => `
                <tr>
                    <td class="px-4 py-2 text-sm font-medium text-gray-800">${window.NSMS.escapeHtml(row.day_label)}</td>
                    ${row.slots.map(slot => `
                        <td class="px-3 py-2 text-center">
                            <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                <input
                                    type="checkbox"
                                    class="availability-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    data-day="${row.day_of_week}"
                                    data-slot="${slot.slot_index}"
                                    ${slot.is_available ? 'checked' : ''}
                                >
                            </label>
                        </td>
                    `).join('')}
                </tr>
            `).join('');

            matrixMeta.textContent = `${rows.length} days x ${headers.length} slots`;
        }

        async function loadMatrix() {
            clearMessage();

            const teacherId = Number(teacherInput.value);
            if (!teacherId) {
                showMessage('Please select a teacher.', 'error');
                return;
            }

            loadMatrixBtn.disabled = true;
            loadMatrixBtn.textContent = 'Loading...';
            matrixBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-gray-500">Loading matrix...</td></tr>';

            try {
                const params = new URLSearchParams({ teacher_id: teacherId });
                const response = await fetch(`{{ route('principal.timetable.teacher-availability.matrix') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();
                if (!response.ok) {
                    showMessage(result.message || 'Failed to load matrix.', 'error');
                    matrixBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-red-600">Unable to load matrix.</td></tr>';
                    return;
                }

                matrixRows = result.rows || [];
                renderMatrix(result.slot_headers || [], matrixRows);

                listState.teacher_id = String(teacherId);
                listState.page = 1;
                await loadAvailabilityList();
            } catch (error) {
                showMessage('Unexpected error while loading matrix.', 'error');
                matrixBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-red-600">Unable to load matrix.</td></tr>';
            } finally {
                loadMatrixBtn.disabled = false;
                loadMatrixBtn.textContent = 'Load Matrix';
            }
        }

        async function saveMatrix() {
            clearMessage();

            const teacherId = Number(teacherInput.value);
            if (!teacherId) {
                showMessage('Please select a teacher before saving.', 'error');
                return;
            }

            const checkboxes = Array.from(document.querySelectorAll('.availability-checkbox'));
            if (!checkboxes.length) {
                showMessage('Load matrix first.', 'error');
                return;
            }

            const records = checkboxes.map(checkbox => ({
                day_of_week: checkbox.dataset.day,
                slot_index: Number(checkbox.dataset.slot),
                is_available: checkbox.checked
            }));

            saveMatrixBtn.disabled = true;
            saveMatrixBtn.textContent = 'Saving...';

            try {
                const response = await fetch(`{{ route('principal.timetable.teacher-availability.save') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        teacher_id: teacherId,
                        records
                    })
                });

                const result = await response.json();
                if (!response.ok) {
                    const errorText = result.message || Object.values(result.errors || {}).flat().join(' ') || 'Failed to save availability.';
                    showMessage(errorText, 'error');
                    return;
                }

                showMessage(result.message || 'Availability saved successfully.');
                listState.page = 1;
                await loadAvailabilityList();
            } catch (error) {
                showMessage('Unexpected error while saving availability.', 'error');
            } finally {
                saveMatrixBtn.disabled = false;
                saveMatrixBtn.textContent = 'Save Availability';
            }
        }

        async function loadAvailabilityList() {
            if (!listState.teacher_id) {
                listBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Select a teacher to load availability.</td></tr>';
                paginationInfo.textContent = '-';
                return;
            }

            listBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Loading availability...</td></tr>';

            const params = new URLSearchParams({
                teacher_id: listState.teacher_id,
                day_of_week: listState.day_of_week,
                search: listState.search,
                page: listState.page,
                per_page: listState.per_page
            });

            const response = await fetch(`{{ route('principal.timetable.teacher-availability.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                listBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">Failed to load availability list.</td></tr>';
                return;
            }

            const payload = await response.json();
            const rows = payload.data || [];

            if (!rows.length) {
                listBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No availability rows found.</td></tr>';
            } else {
                listBody.innerHTML = rows.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.day_label)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${row.slot_index}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.start_time ?? '-') } - ${window.NSMS.escapeHtml(row.end_time ?? '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">
                            ${row.is_available
                                ? '<span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Available</span>'
                                : '<span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">Unavailable</span>'
                            }
                        </td>
                    </tr>
                `).join('');
            }

            paginationInfo.textContent = `Page ${payload.meta.current_page} of ${payload.meta.last_page} | Total: ${payload.meta.total}`;
            prevPageBtn.disabled = payload.meta.current_page <= 1;
            nextPageBtn.disabled = payload.meta.current_page >= payload.meta.last_page;
        }

        loadMatrixBtn.addEventListener('click', loadMatrix);
        saveMatrixBtn.addEventListener('click', saveMatrix);

        teacherInput.addEventListener('change', async () => {
            listState.teacher_id = teacherInput.value;
            listState.page = 1;
            matrixMeta.textContent = 'Select a teacher and click Load Matrix.';
            matrixHead.innerHTML = '<tr><th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Day</th></tr>';
            matrixBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-gray-500">No matrix loaded.</td></tr>';
            listState.day_of_week = '';
            listState.search = '';
            dayFilter.value = '';
            searchInput.value = '';

            if (listState.teacher_id) {
                await loadAvailabilityList();
            } else {
                listBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Select a teacher to load availability.</td></tr>';
                paginationInfo.textContent = '-';
            }
        });

        const onSearchInput = window.NSMS.debounce(async () => {
            listState.search = searchInput.value.trim();
            listState.page = 1;
            await loadAvailabilityList();
        }, 300);
        searchInput.addEventListener('input', onSearchInput);

        dayFilter.addEventListener('change', async () => {
            listState.day_of_week = dayFilter.value;
            listState.page = 1;
            await loadAvailabilityList();
        });

        perPageInput.addEventListener('change', async () => {
            listState.per_page = Number(perPageInput.value || 10);
            listState.page = 1;
            await loadAvailabilityList();
        });

        prevPageBtn.addEventListener('click', async () => {
            if (listState.page > 1) {
                listState.page -= 1;
                await loadAvailabilityList();
            }
        });

        nextPageBtn.addEventListener('click', async () => {
            listState.page += 1;
            await loadAvailabilityList();
        });

        async function boot() {
            try {
                await loadPageOptions();
                if (pageOptions.teachers.length > 0) {
                    teacherInput.value = String(pageOptions.teachers[0].id);
                    listState.teacher_id = teacherInput.value;
                    await loadMatrix();
                }
            } catch (error) {
                showMessage('Failed to initialize teacher availability page.', 'error');
            }
        }

        boot();
    </script>
</x-app-layout>
