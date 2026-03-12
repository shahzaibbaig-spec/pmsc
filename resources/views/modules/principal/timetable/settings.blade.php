<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Timetable Settings
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Period Configuration</h3>
                    <p class="text-sm text-gray-600 mt-1">Generate weekly time slots for Mon-Sat with configurable period count and timings.</p>

                    <div id="slotActionMessage" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <form id="regenerateSlotsForm" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <x-input-label for="periods_per_day" value="Periods / Day" />
                            <x-text-input id="periods_per_day" type="number" min="1" max="12" class="mt-1 block w-full" value="{{ $configPeriodsPerDay }}" />
                        </div>
                        <div>
                            <x-input-label for="start_time" value="Start Time" />
                            <x-text-input id="start_time" type="time" class="mt-1 block w-full" value="{{ $configStartTime }}" />
                        </div>
                        <div>
                            <x-input-label for="period_minutes" value="Period Minutes" />
                            <x-text-input id="period_minutes" type="number" min="20" max="120" class="mt-1 block w-full" value="{{ $configPeriodMinutes }}" />
                        </div>
                        <div>
                            <x-input-label for="break_minutes" value="Break Minutes" />
                            <x-text-input id="break_minutes" type="number" min="0" max="30" class="mt-1 block w-full" value="{{ $configBreakMinutes }}" />
                        </div>
                        <div class="flex items-end">
                            <button id="regenerateSlotsBtn" type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Regenerate Slots
                            </button>
                        </div>
                        <div class="md:col-span-5">
                            <p class="text-xs font-medium text-gray-700 mb-2">Days</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach($days as $day)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" class="day-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" value="{{ $day }}" checked>
                                        <span>{{ strtoupper($day) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Session Constraints</h3>
                    <p class="text-sm text-gray-600 mt-1">Set weekly load constraints used by timetable generation rules.</p>

                    <div id="constraintActionMessage" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <form id="constraintsForm" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="max_periods_per_day_teacher" value="Teacher / Day" />
                            <x-text-input id="max_periods_per_day_teacher" type="number" min="1" max="12" class="mt-1 block w-full" value="6" />
                        </div>
                        <div>
                            <x-input-label for="max_periods_per_week_teacher" value="Teacher / Week" />
                            <x-text-input id="max_periods_per_week_teacher" type="number" min="1" max="60" class="mt-1 block w-full" value="28" />
                        </div>
                        <div>
                            <x-input-label for="max_periods_per_day_class" value="Class / Day" />
                            <x-text-input id="max_periods_per_day_class" type="number" min="1" max="12" class="mt-1 block w-full" value="7" />
                        </div>
                        <div class="flex items-end">
                            <button id="saveConstraintsBtn" type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Save Constraints
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-medium text-gray-900">Time Slots</h3>
                            <div class="flex gap-2">
                                <input id="slotSearch" type="text" placeholder="Search day/slot/time"
                                       class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <select id="slotDayFilter" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Days</option>
                                    @foreach($days as $day)
                                        <option value="{{ $day }}">{{ strtoupper($day) }}</option>
                                    @endforeach
                                </select>
                                <select id="slotPerPage" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Start</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">End</th>
                                    </tr>
                                </thead>
                                <tbody id="timeSlotsBody" class="divide-y divide-gray-200 bg-white">
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Loading time slots...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <p id="timeSlotsPaginationInfo" class="text-sm text-gray-600">-</p>
                            <div class="flex gap-2">
                                <button id="timeSlotsPrev" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
                                <button id="timeSlotsNext" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-medium text-gray-900">Constraints List</h3>
                            <div class="flex gap-2">
                                <input id="constraintsSearch" type="text" placeholder="Search session"
                                       class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <select id="constraintsPerPage" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Session</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Teacher / Day</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Teacher / Week</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class / Day</th>
                                    </tr>
                                </thead>
                                <tbody id="constraintsBody" class="divide-y divide-gray-200 bg-white">
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Loading constraints...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <p id="constraintsPaginationInfo" class="text-sm text-gray-600">-</p>
                            <div class="flex gap-2">
                                <button id="constraintsPrev" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
                                <button id="constraintsNext" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const slotActionMessage = document.getElementById('slotActionMessage');
        const constraintActionMessage = document.getElementById('constraintActionMessage');

        const timeSlotsBody = document.getElementById('timeSlotsBody');
        const timeSlotsPaginationInfo = document.getElementById('timeSlotsPaginationInfo');
        const slotSearch = document.getElementById('slotSearch');
        const slotDayFilter = document.getElementById('slotDayFilter');
        const slotPerPage = document.getElementById('slotPerPage');
        const timeSlotsPrev = document.getElementById('timeSlotsPrev');
        const timeSlotsNext = document.getElementById('timeSlotsNext');

        const constraintsBody = document.getElementById('constraintsBody');
        const constraintsPaginationInfo = document.getElementById('constraintsPaginationInfo');
        const constraintsSearch = document.getElementById('constraintsSearch');
        const constraintsPerPage = document.getElementById('constraintsPerPage');
        const constraintsPrev = document.getElementById('constraintsPrev');
        const constraintsNext = document.getElementById('constraintsNext');

        const slotState = { page: 1, per_page: 10, search: '', day_of_week: '' };
        const constraintState = { page: 1, per_page: 10, search: '' };

        function showMessage(el, message, type = 'success') {
            el.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            el.textContent = message;
            if (type === 'error') {
                el.classList.add('bg-red-50', 'text-red-700');
            } else {
                el.classList.add('bg-green-50', 'text-green-700');
            }
        }

        async function loadTimeSlots() {
            const params = new URLSearchParams({
                page: slotState.page,
                per_page: slotState.per_page,
                search: slotState.search,
                day_of_week: slotState.day_of_week
            });

            const response = await fetch(`{{ route('principal.timetable.settings.time-slots.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                timeSlotsBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">Failed to load time slots.</td></tr>';
                return;
            }

            const result = await response.json();
            const rows = result.data || [];

            if (!rows.length) {
                timeSlotsBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No time slots found.</td></tr>';
            } else {
                timeSlotsBody.innerHTML = rows.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.day_label)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${row.slot_index}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.start_time)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.end_time)}</td>
                    </tr>
                `).join('');
            }

            timeSlotsPaginationInfo.textContent = `Page ${result.meta.current_page} of ${result.meta.last_page} | Total: ${result.meta.total}`;
            timeSlotsPrev.disabled = result.meta.current_page <= 1;
            timeSlotsNext.disabled = result.meta.current_page >= result.meta.last_page;
        }

        async function loadConstraints() {
            const params = new URLSearchParams({
                page: constraintState.page,
                per_page: constraintState.per_page,
                search: constraintState.search
            });

            const response = await fetch(`{{ route('principal.timetable.settings.constraints.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                constraintsBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">Failed to load constraints.</td></tr>';
                return;
            }

            const result = await response.json();
            const rows = result.data || [];

            if (!rows.length) {
                constraintsBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No constraints found.</td></tr>';
            } else {
                constraintsBody.innerHTML = rows.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.session)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${row.max_periods_per_day_teacher}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${row.max_periods_per_week_teacher}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${row.max_periods_per_day_class}</td>
                    </tr>
                `).join('');
            }

            constraintsPaginationInfo.textContent = `Page ${result.meta.current_page} of ${result.meta.last_page} | Total: ${result.meta.total}`;
            constraintsPrev.disabled = result.meta.current_page <= 1;
            constraintsNext.disabled = result.meta.current_page >= result.meta.last_page;
        }

        document.getElementById('regenerateSlotsForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const days = Array.from(document.querySelectorAll('.day-checkbox:checked')).map(el => el.value);
            const payload = {
                periods_per_day: Number(document.getElementById('periods_per_day').value),
                start_time: document.getElementById('start_time').value,
                period_minutes: Number(document.getElementById('period_minutes').value),
                break_minutes: Number(document.getElementById('break_minutes').value),
                days
            };

            const button = document.getElementById('regenerateSlotsBtn');
            button.disabled = true;
            button.textContent = 'Regenerating...';

            try {
                const response = await fetch(`{{ route('principal.timetable.settings.time-slots.regenerate') }}`, {
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
                    const message = result.message || Object.values(result.errors || {}).flat().join(' ') || 'Failed to regenerate slots.';
                    showMessage(slotActionMessage, message, 'error');
                    return;
                }

                showMessage(slotActionMessage, `Time slots regenerated (${result.generated_count} rows).`);
                slotState.page = 1;
                await loadTimeSlots();
            } catch (error) {
                showMessage(slotActionMessage, 'Unexpected error while regenerating slots.', 'error');
            } finally {
                button.disabled = false;
                button.textContent = 'Regenerate Slots';
            }
        });

        document.getElementById('constraintsForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = {
                session: document.getElementById('session').value,
                max_periods_per_day_teacher: Number(document.getElementById('max_periods_per_day_teacher').value),
                max_periods_per_week_teacher: Number(document.getElementById('max_periods_per_week_teacher').value),
                max_periods_per_day_class: Number(document.getElementById('max_periods_per_day_class').value)
            };

            const button = document.getElementById('saveConstraintsBtn');
            button.disabled = true;
            button.textContent = 'Saving...';

            try {
                const response = await fetch(`{{ route('principal.timetable.settings.constraints.store') }}`, {
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
                    const message = result.message || Object.values(result.errors || {}).flat().join(' ') || 'Failed to save constraints.';
                    showMessage(constraintActionMessage, message, 'error');
                    return;
                }

                showMessage(constraintActionMessage, result.message || 'Constraints saved successfully.');
                constraintState.page = 1;
                await loadConstraints();
            } catch (error) {
                showMessage(constraintActionMessage, 'Unexpected error while saving constraints.', 'error');
            } finally {
                button.disabled = false;
                button.textContent = 'Save Constraints';
            }
        });

        const onSlotSearch = window.NSMS.debounce(async () => {
            slotState.search = slotSearch.value.trim();
            slotState.page = 1;
            await loadTimeSlots();
        }, 300);
        slotSearch.addEventListener('input', onSlotSearch);

        slotDayFilter.addEventListener('change', async () => {
            slotState.day_of_week = slotDayFilter.value;
            slotState.page = 1;
            await loadTimeSlots();
        });

        slotPerPage.addEventListener('change', async () => {
            slotState.per_page = Number(slotPerPage.value || 10);
            slotState.page = 1;
            await loadTimeSlots();
        });

        timeSlotsPrev.addEventListener('click', async () => {
            if (slotState.page > 1) {
                slotState.page -= 1;
                await loadTimeSlots();
            }
        });

        timeSlotsNext.addEventListener('click', async () => {
            slotState.page += 1;
            await loadTimeSlots();
        });

        const onConstraintsSearch = window.NSMS.debounce(async () => {
            constraintState.search = constraintsSearch.value.trim();
            constraintState.page = 1;
            await loadConstraints();
        }, 300);
        constraintsSearch.addEventListener('input', onConstraintsSearch);

        constraintsPerPage.addEventListener('change', async () => {
            constraintState.per_page = Number(constraintsPerPage.value || 10);
            constraintState.page = 1;
            await loadConstraints();
        });

        constraintsPrev.addEventListener('click', async () => {
            if (constraintState.page > 1) {
                constraintState.page -= 1;
                await loadConstraints();
            }
        });

        constraintsNext.addEventListener('click', async () => {
            constraintState.page += 1;
            await loadConstraints();
        });

        window.NSMS.lazyInit(timeSlotsBody, loadTimeSlots);
        window.NSMS.lazyInit(constraintsBody, loadConstraints);
    </script>
</x-app-layout>

