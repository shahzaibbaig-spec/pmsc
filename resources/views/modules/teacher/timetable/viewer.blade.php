<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Timetable
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Weekly Timetable</h3>
                    <p class="text-sm text-gray-600 mt-1">View your weekly timetable by session.</p>

                    <div id="actionMessage" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button id="loadBtn" type="button" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Load Timetable
                            </button>
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
                        <table class="min-w-[760px] divide-y divide-gray-200">
                            <thead id="gridHead" class="bg-gray-50">
                                <tr>
                                    <th class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Day</th>
                                </tr>
                            </thead>
                            <tbody id="gridBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td class="px-4 py-8 text-center text-sm text-gray-500">Load timetable to view schedule.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const sessionInput = document.getElementById('session');
        const loadBtn = document.getElementById('loadBtn');
        const actionMessage = document.getElementById('actionMessage');
        const gridTitle = document.getElementById('gridTitle');
        const gridMeta = document.getElementById('gridMeta');
        const gridHead = document.getElementById('gridHead');
        const gridBody = document.getElementById('gridBody');

        const teacherId = Number(@json($teacherId));
        let payload = null;

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

        function renderGrid() {
            if (!payload) {
                gridHead.innerHTML = `
                    <tr>
                        <th class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Day</th>
                    </tr>
                `;
                gridBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-gray-500">Load timetable to view schedule.</td></tr>';
                gridMeta.textContent = '-';
                gridTitle.textContent = 'Weekly Grid';
                return;
            }

            gridTitle.textContent = payload.teacher?.name ? `${payload.teacher.name} - Weekly Grid` : 'Weekly Grid';

            const slotHeaders = payload.slot_headers || [];
            const rows = payload.rows || [];

            gridHead.innerHTML = `
                <tr>
                    <th class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 min-w-28">Day</th>
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
                    <td class="sticky left-0 z-10 bg-white px-4 py-2 text-sm font-medium text-gray-800">${window.NSMS.escapeHtml(row.day_label)}</td>
                    ${row.cells.map(cell => {
                        if (!cell.entry) {
                            return '<td class="px-2 py-2"><div class="rounded border border-dashed border-gray-200 px-2 py-4 text-center text-xs text-gray-400">Free</div></td>';
                        }

                        return `
                            <td class="px-2 py-2 align-top">
                                <div class="rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-700">
                                    <div class="font-semibold text-gray-900">${window.NSMS.escapeHtml(cell.entry.subject_name)}</div>
                                    <div class="mt-1">${window.NSMS.escapeHtml(cell.entry.class_section)}</div>
                                    <div>${window.NSMS.escapeHtml(cell.entry.room_name)}</div>
                                </div>
                            </td>
                        `;
                    }).join('')}
                </tr>
            `).join('');

            gridMeta.textContent = `${rows.length} days x ${slotHeaders.length} slots`;
        }

        async function loadTimetable() {
            clearMessage();

            const session = sessionInput.value;
            if (!session) {
                showMessage('Session is required.', 'error');
                return;
            }

            loadBtn.disabled = true;
            loadBtn.textContent = 'Loading...';
            gridBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-gray-500">Loading timetable...</td></tr>';

            const params = new URLSearchParams({
                session,
                teacher_id: teacherId
            });

            try {
                const response = await fetch(`{{ route('api.timetable.teacher') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();
                if (!response.ok) {
                    showMessage(result.message || 'Failed to load timetable.', 'error');
                    payload = null;
                    renderGrid();
                    return;
                }

                payload = result;
                renderGrid();
            } catch (error) {
                showMessage('Unexpected error while loading timetable.', 'error');
                payload = null;
                renderGrid();
            } finally {
                loadBtn.disabled = false;
                loadBtn.textContent = 'Load Timetable';
            }
        }

        loadBtn.addEventListener('click', loadTimetable);
        loadTimetable();
    </script>
</x-app-layout>
