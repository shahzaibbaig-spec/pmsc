<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Subject Period Rules
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Create / Edit Rule</h3>
                    <p class="text-sm text-gray-600 mt-1">Define required weekly periods for each subject by class section and session.</p>

                    <div id="formErrors" class="mt-4 hidden rounded-md bg-red-50 p-3 text-sm text-red-700"></div>

                    <form id="ruleForm" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="hidden" id="ruleId" name="rule_id">

                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="class_section_id" value="Class Section" />
                            <select id="class_section_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select Class Section</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="subject_id" value="Subject" />
                            <select id="subject_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select Subject</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="periods_per_week" value="Periods / Week" />
                            <x-text-input id="periods_per_week" type="number" min="1" max="20" class="mt-1 block w-full" value="3" required />
                        </div>

                        <div class="flex items-end gap-3">
                            <x-primary-button id="saveRuleBtn">Save Rule</x-primary-button>
                            <button type="button" id="cancelEditBtn" class="hidden inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Rules List</h3>
                        <div class="flex flex-col md:flex-row gap-2">
                            <input id="searchInput" type="text" placeholder="Search session/class/subject"
                                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <select id="filterSession" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sessions</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}">{{ $session }}</option>
                                @endforeach
                            </select>
                            <select id="filterClassSection" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Class Sections</option>
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
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Session</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class Section</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Subject</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Periods / Week</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rulesBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Loading rules...</td>
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

        const form = document.getElementById('ruleForm');
        const ruleIdInput = document.getElementById('ruleId');
        const sessionInput = document.getElementById('session');
        const classSectionInput = document.getElementById('class_section_id');
        const subjectInput = document.getElementById('subject_id');
        const periodsInput = document.getElementById('periods_per_week');
        const saveRuleBtn = document.getElementById('saveRuleBtn');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const formErrors = document.getElementById('formErrors');

        const rulesBody = document.getElementById('rulesBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const searchInput = document.getElementById('searchInput');
        const filterSession = document.getElementById('filterSession');
        const filterClassSection = document.getElementById('filterClassSection');
        const perPageInput = document.getElementById('perPageInput');

        let options = {
            sections: [],
            subjects: []
        };

        let state = {
            page: 1,
            per_page: 10,
            search: '',
            session: '',
            class_section_id: ''
        };

        let rulesCache = [];

        function showErrors(messages) {
            formErrors.classList.remove('hidden');
            formErrors.innerHTML = '<ul class="list-disc ps-5">' + messages.map(message => `<li>${window.NSMS.escapeHtml(message)}</li>`).join('') + '</ul>';
        }

        function hideErrors() {
            formErrors.classList.add('hidden');
            formErrors.innerHTML = '';
        }

        function resetForm() {
            ruleIdInput.value = '';
            sessionInput.value = @json($defaultSession);
            classSectionInput.value = '';
            subjectInput.value = '';
            periodsInput.value = '3';
            saveRuleBtn.textContent = 'Save Rule';
            cancelEditBtn.classList.add('hidden');
            hideErrors();
        }

        function renderOptionLists() {
            const sectionOptions = ['<option value="">Select Class Section</option>'].concat(
                options.sections.map(section => `<option value="${section.id}">${window.NSMS.escapeHtml(section.display_name)}</option>`)
            );

            classSectionInput.innerHTML = sectionOptions.join('');

            const filterSectionOptions = ['<option value="">All Class Sections</option>'].concat(
                options.sections.map(section => `<option value="${section.id}">${window.NSMS.escapeHtml(section.display_name)}</option>`)
            );
            filterClassSection.innerHTML = filterSectionOptions.join('');

            const subjectOptions = ['<option value="">Select Subject</option>'].concat(
                options.subjects.map(subject => {
                    const label = subject.code ? `${subject.name} (${subject.code})` : subject.name;
                    return `<option value="${subject.id}">${window.NSMS.escapeHtml(label)}</option>`;
                })
            );
            subjectInput.innerHTML = subjectOptions.join('');
        }

        async function loadOptions() {
            const response = await fetch(`{{ route('principal.timetable.subject-rules.options') }}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Failed to load options');
            }

            const payload = await response.json();
            options.sections = payload.sections || [];
            options.subjects = payload.subjects || [];
            renderOptionLists();
        }

        async function loadRules() {
            rulesBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Loading rules...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.per_page,
                search: state.search,
                session: state.session,
                class_section_id: state.class_section_id
            });

            const response = await fetch(`{{ route('principal.timetable.subject-rules.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                rulesBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-red-600">Failed to load rules.</td></tr>';
                return;
            }

            const payload = await response.json();
            rulesCache = payload.data || [];

            if (!rulesCache.length) {
                rulesBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No rules found.</td></tr>';
            } else {
                rulesBody.innerHTML = rulesCache.map(rule => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(rule.session)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(rule.class_section_name)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(rule.subject_name)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${rule.periods_per_week}</td>
                        <td class="px-4 py-2 text-sm">
                            <div class="flex gap-2">
                                <button type="button" class="edit-btn rounded-md bg-amber-500 px-3 py-1 text-white hover:bg-amber-600" data-id="${rule.id}">Edit</button>
                                <button type="button" class="delete-btn rounded-md bg-red-600 px-3 py-1 text-white hover:bg-red-700" data-id="${rule.id}">Delete</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }

            paginationInfo.textContent = `Page ${payload.meta.current_page} of ${payload.meta.last_page} | Total: ${payload.meta.total}`;
            prevPageBtn.disabled = payload.meta.current_page <= 1;
            nextPageBtn.disabled = payload.meta.current_page >= payload.meta.last_page;
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideErrors();

            const ruleId = ruleIdInput.value;
            const isEdit = ruleId !== '';
            const endpoint = isEdit
                ? `/principal/timetable/subject-rules/${ruleId}`
                : `{{ route('principal.timetable.subject-rules.store') }}`;
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                session: sessionInput.value,
                class_section_id: Number(classSectionInput.value),
                subject_id: Number(subjectInput.value),
                periods_per_week: Number(periodsInput.value)
            };

            saveRuleBtn.disabled = true;

            try {
                const response = await fetch(endpoint, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok) {
                    if (result.errors) {
                        showErrors(Object.values(result.errors).flat());
                    } else {
                        showErrors([result.message || 'Failed to save rule.']);
                    }
                    return;
                }

                resetForm();
                state.page = 1;
                await loadRules();
            } catch (error) {
                showErrors(['Unexpected error while saving rule.']);
            } finally {
                saveRuleBtn.disabled = false;
            }
        });

        rulesBody.addEventListener('click', async (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (target.classList.contains('edit-btn')) {
                const rule = rulesCache.find(item => String(item.id) === String(target.dataset.id));
                if (!rule) {
                    return;
                }

                ruleIdInput.value = String(rule.id);
                sessionInput.value = rule.session;
                classSectionInput.value = String(rule.class_section_id);
                subjectInput.value = String(rule.subject_id);
                periodsInput.value = String(rule.periods_per_week);
                saveRuleBtn.textContent = 'Update Rule';
                cancelEditBtn.classList.remove('hidden');
                hideErrors();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            if (target.classList.contains('delete-btn')) {
                const id = target.dataset.id;
                if (!confirm('Delete this subject period rule?')) {
                    return;
                }

                const response = await fetch(`/principal/timetable/subject-rules/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!response.ok) {
                    const result = await response.json().catch(() => ({}));
                    showErrors([result.message || 'Failed to delete rule.']);
                    return;
                }

                await loadRules();
            }
        });

        cancelEditBtn.addEventListener('click', resetForm);

        prevPageBtn.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadRules();
            }
        });

        nextPageBtn.addEventListener('click', async () => {
            state.page += 1;
            await loadRules();
        });

        const onSearchInput = window.NSMS.debounce(async () => {
            state.search = searchInput.value.trim();
            state.page = 1;
            await loadRules();
        }, 300);
        searchInput.addEventListener('input', onSearchInput);

        filterSession.addEventListener('change', async () => {
            state.session = filterSession.value;
            state.page = 1;
            await loadRules();
        });

        filterClassSection.addEventListener('change', async () => {
            state.class_section_id = filterClassSection.value;
            state.page = 1;
            await loadRules();
        });

        perPageInput.addEventListener('change', async () => {
            state.per_page = Number(perPageInput.value || 10);
            state.page = 1;
            await loadRules();
        });

        async function boot() {
            try {
                await loadOptions();
                filterSession.value = '';
                state.session = '';
                resetForm();
                window.NSMS.lazyInit(rulesBody, loadRules);
            } catch (error) {
                rulesBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-red-600">Failed to initialize page.</td></tr>';
            }
        }

        boot();
    </script>
</x-app-layout>
