<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Timetable Generation
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Generate Session Timetable</h3>
                    <p class="text-sm text-gray-600 mt-1">Select session and class sections, then generate conflict-aware timetable entries.</p>

                    <div id="actionMessage" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="sectionSearch" value="Search Class Section" />
                            <x-text-input id="sectionSearch" type="text" class="mt-1 block w-full" placeholder="Type class or section name" />
                        </div>
                        <div class="flex items-end gap-2">
                            <button id="selectAllBtn" type="button" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Select Visible
                            </button>
                            <button id="clearAllBtn" type="button" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 rounded-md border border-gray-200">
                        <div class="max-h-72 overflow-y-auto p-3">
                            <div id="sectionsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @forelse($classSections as $section)
                                    <label class="section-item inline-flex items-center gap-2 rounded border border-gray-200 px-3 py-2 text-sm text-gray-800 hover:bg-gray-50"
                                           data-search="{{ strtolower($section['display_name']) }}">
                                        <input type="checkbox"
                                               class="section-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $section['id'] }}">
                                        <span>{{ $section['display_name'] }}</span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-500">No class sections found.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <p id="selectionMeta" class="text-sm text-gray-600">Selected: 0</p>
                        <button id="generateBtn" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Generate
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Scheduled Entries</p>
                        <p id="scheduledCount" class="mt-2 text-2xl font-semibold text-emerald-700">0</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Conflicts</p>
                        <p id="conflictCount" class="mt-2 text-2xl font-semibold text-red-700">0</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Unresolved Subjects</p>
                        <p id="unresolvedCount" class="mt-2 text-2xl font-semibold text-amber-700">0</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Unresolved Subjects</h3>
                    <p class="text-sm text-gray-600 mt-1">Subjects that could not be fully scheduled due to conflicts.</p>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class Section</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Subject</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Required</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Scheduled</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Unresolved</th>
                                </tr>
                            </thead>
                            <tbody id="unresolvedBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Generate timetable to view unresolved subjects.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Conflicts</h3>
                    <p class="text-sm text-gray-600 mt-1">Hard-constraint issues found during generation.</p>

                    <div id="conflictsList" class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                        No conflicts yet.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const sessionInput = document.getElementById('session');
        const sectionSearchInput = document.getElementById('sectionSearch');
        const sectionsGrid = document.getElementById('sectionsGrid');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const clearAllBtn = document.getElementById('clearAllBtn');
        const generateBtn = document.getElementById('generateBtn');
        const selectionMeta = document.getElementById('selectionMeta');
        const actionMessage = document.getElementById('actionMessage');

        const scheduledCount = document.getElementById('scheduledCount');
        const conflictCount = document.getElementById('conflictCount');
        const unresolvedCount = document.getElementById('unresolvedCount');
        const unresolvedBody = document.getElementById('unresolvedBody');
        const conflictsList = document.getElementById('conflictsList');

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

        function getVisibleSectionItems() {
            return Array.from(document.querySelectorAll('.section-item'))
                .filter(item => !item.classList.contains('hidden'));
        }

        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.section-checkbox:checked'))
                .map(input => Number(input.value));
        }

        function updateSelectionMeta() {
            selectionMeta.textContent = `Selected: ${getSelectedIds().length}`;
        }

        function filterSections() {
            const query = (sectionSearchInput.value || '').trim().toLowerCase();
            const items = Array.from(document.querySelectorAll('.section-item'));

            let visible = 0;
            items.forEach(item => {
                const haystack = item.dataset.search || '';
                const matched = query === '' || haystack.includes(query);
                item.classList.toggle('hidden', !matched);
                if (matched) {
                    visible += 1;
                }
            });

            if (visible === 0) {
                if (!document.getElementById('noSectionMatch')) {
                    const empty = document.createElement('p');
                    empty.id = 'noSectionMatch';
                    empty.className = 'text-sm text-gray-500';
                    empty.textContent = 'No class sections match your search.';
                    sectionsGrid.appendChild(empty);
                }
            } else {
                const empty = document.getElementById('noSectionMatch');
                if (empty) {
                    empty.remove();
                }
            }
        }

        function renderSummary(payload) {
            const unresolved = payload.unresolved_subjects || [];
            const conflicts = payload.conflicts || [];

            scheduledCount.textContent = String(payload.scheduled_count ?? 0);
            conflictCount.textContent = String(conflicts.length);
            unresolvedCount.textContent = String(unresolved.length);

            if (!unresolved.length) {
                unresolvedBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">All required subjects were scheduled.</td></tr>';
            } else {
                unresolvedBody.innerHTML = unresolved.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.class_section)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(row.subject)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${row.required_periods}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${row.scheduled_periods}</td>
                        <td class="px-4 py-2 text-sm font-semibold text-amber-700">${row.unresolved_periods}</td>
                    </tr>
                `).join('');
            }

            if (!conflicts.length) {
                conflictsList.innerHTML = '<p class="text-sm text-emerald-700">No conflicts found.</p>';
            } else {
                conflictsList.innerHTML = `
                    <ul class="list-disc ps-5 space-y-1">
                        ${conflicts.map(conflict => `<li><span class="font-medium">[${window.NSMS.escapeHtml(conflict.code)}]</span> ${window.NSMS.escapeHtml(conflict.message)}</li>`).join('')}
                    </ul>
                `;
            }
        }

        async function generateTimetable() {
            clearMessage();

            const selectedIds = getSelectedIds();
            if (!selectedIds.length) {
                showMessage('Please select at least one class section.', 'error');
                return;
            }

            const payload = {
                session: sessionInput.value,
                class_section_ids: selectedIds
            };

            generateBtn.disabled = true;
            generateBtn.textContent = 'Generating...';

            try {
                const response = await fetch(`{{ route('principal.timetable.generate.run') }}`, {
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
                    const validationErrors = Object.values(result.errors || {}).flat();
                    const message = validationErrors.length
                        ? validationErrors.join(' ')
                        : (result.message || 'Failed to generate timetable.');
                    showMessage(message, 'error');
                    return;
                }

                renderSummary(result);
                showMessage(`Generation complete. Scheduled ${result.scheduled_count ?? 0} entries.`);
            } catch (error) {
                showMessage('Unexpected error while generating timetable.', 'error');
            } finally {
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate';
            }
        }

        const onSectionSearch = window.NSMS.debounce(filterSections, 250);
        sectionSearchInput.addEventListener('input', onSectionSearch);

        selectAllBtn.addEventListener('click', () => {
            getVisibleSectionItems().forEach(item => {
                const checkbox = item.querySelector('.section-checkbox');
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            updateSelectionMeta();
        });

        clearAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.section-checkbox').forEach(input => {
                input.checked = false;
            });
            updateSelectionMeta();
        });

        sectionsGrid.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }

            if (target.classList.contains('section-checkbox')) {
                updateSelectionMeta();
            }
        });

        generateBtn.addEventListener('click', generateTimetable);

        updateSelectionMeta();
    </script>
</x-app-layout>
