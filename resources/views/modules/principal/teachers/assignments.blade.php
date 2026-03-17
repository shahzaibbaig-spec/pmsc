<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Teacher Assignments
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Assign Teachers</h3>
                    <p class="text-sm text-gray-600 mt-1">Principal can assign class teachers and subject teachers by session.</p>
                    <p class="text-xs text-gray-500 mt-1">Tip: saving the same class + subject + session updates the existing assignment.</p>

                    <div id="assignmentErrors" class="mt-4 hidden rounded-md bg-red-50 p-3 text-sm text-red-700"></div>
                    <div id="assignmentSuccess" class="mt-4 hidden rounded-md bg-emerald-50 p-3 text-sm text-emerald-700"></div>

                    <form id="assignmentForm" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="teacher_id" value="Teacher" />
                            <select id="teacher_id" name="teacher_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></select>
                        </div>

                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" name="class_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></select>
                        </div>

                        <div>
                            <x-input-label for="subject_id" value="Subject" />
                            <select id="subject_id" name="subject_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></select>
                        </div>

                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="inline-flex items-center">
                                <input id="is_class_teacher" type="checkbox" name="is_class_teacher" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ms-2 text-sm text-gray-700">Assign as Class Teacher (subject not required)</span>
                            </label>
                        </div>

                        <div class="md:col-span-2">
                            <x-primary-button id="saveAssignmentBtn">Save Assignment</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Assignment List</h3>
                        <div class="flex items-center gap-2">
                            <input id="searchInput" type="text" placeholder="Search teacher/class/subject/session"
                                   class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <select id="sessionFilter" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">All Sessions</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                            <select id="perPage" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
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
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Teacher</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Code</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Designation</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Subject</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class Teacher</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Session</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Action</th>
                                </tr>
                            </thead>
                            <tbody id="assignmentBody" class="divide-y divide-gray-200 bg-white">
                                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Loading assignments...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <p id="paginationInfo" class="text-sm text-gray-600">-</p>
                        <div class="flex gap-2">
                            <button id="prevPage" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button id="nextPage" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const assignmentForm = document.getElementById('assignmentForm');
        const teacherSelect = document.getElementById('teacher_id');
        const classSelect = document.getElementById('class_id');
        const subjectSelect = document.getElementById('subject_id');
        const classTeacherCheckbox = document.getElementById('is_class_teacher');
        const assignmentErrors = document.getElementById('assignmentErrors');
        const assignmentSuccess = document.getElementById('assignmentSuccess');

        const assignmentBody = document.getElementById('assignmentBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageButton = document.getElementById('prevPage');
        const nextPageButton = document.getElementById('nextPage');
        const searchInput = document.getElementById('searchInput');
        const perPageInput = document.getElementById('perPage');
        const sessionFilterInput = document.getElementById('sessionFilter');

        let state = {
            page: 1,
            perPage: 10,
            search: '',
            session: sessionFilterInput.value.trim()
        };

        function showErrors(messages) {
            assignmentErrors.classList.remove('hidden');
            assignmentErrors.innerHTML = '<ul class="list-disc ps-5">' + messages.map(m => `<li>${m}</li>`).join('') + '</ul>';
        }

        function hideErrors() {
            assignmentErrors.classList.add('hidden');
            assignmentErrors.innerHTML = '';
        }

        function showSuccess(message) {
            assignmentSuccess.classList.remove('hidden');
            assignmentSuccess.textContent = message;
        }

        function hideSuccess() {
            assignmentSuccess.classList.add('hidden');
            assignmentSuccess.textContent = '';
        }

        function toggleSubjectState() {
            if (classTeacherCheckbox.checked) {
                subjectSelect.value = '';
                subjectSelect.disabled = true;
            } else {
                subjectSelect.disabled = false;
            }
        }

        async function loadOptions() {
            const response = await fetch('{{ route('principal.teacher-assignments.options') }}', {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Failed to load options');
            }

            const payload = await response.json();

            teacherSelect.innerHTML = '<option value="">Select teacher</option>' + payload.teachers.map(t =>
                `<option value="${t.id}">${t.name} (${t.teacher_id})${t.employee_code ? ' - ' + t.employee_code : ''}</option>`
            ).join('');

            classSelect.innerHTML = '<option value="">Select class</option>' + payload.classes.map(c =>
                `<option value="${c.id}">${c.name} ${c.section ?? ''}</option>`
            ).join('');

            subjectSelect.innerHTML = '<option value="">Select subject</option>' + payload.subjects.map(s =>
                `<option value="${s.id}">${s.name}</option>`
            ).join('');
        }

        async function loadAssignments() {
            assignmentBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Loading assignments...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.perPage,
                search: state.search,
                session: state.session
            });

            try {
                const response = await fetch(`{{ route('principal.teacher-assignments.data') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Failed to load assignments');
                }

                const payload = await response.json();

                if (payload.data.length === 0) {
                    assignmentBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No assignments found.</td></tr>';
                } else {
                    assignmentBody.innerHTML = payload.data.map(a => `
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800">${a.teacher_name ?? '-'}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${a.teacher_id_code ?? '-'}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${a.designation ?? '-'}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${a.class_name ?? '-'}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${a.subject_name ?? '-'}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${a.is_class_teacher ? 'Yes' : 'No'}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${a.session}</td>
                            <td class="px-4 py-2 text-sm">
                                <button type="button" class="delete-assignment rounded-md bg-red-600 px-3 py-1 text-white hover:bg-red-700" data-id="${a.id}">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                }

                paginationInfo.textContent = `Page ${payload.meta.current_page} of ${payload.meta.last_page} | Total: ${payload.meta.total}`;
                prevPageButton.disabled = payload.meta.current_page <= 1;
                nextPageButton.disabled = payload.meta.current_page >= payload.meta.last_page;
            } catch (error) {
                assignmentBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-red-600">Failed to load assignments.</td></tr>';
            }
        }

        assignmentForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideErrors();
            hideSuccess();

            const selectedSession = document.getElementById('session').value.trim();

            const payload = {
                teacher_id: teacherSelect.value,
                class_id: classSelect.value,
                subject_id: subjectSelect.value || null,
                is_class_teacher: classTeacherCheckbox.checked,
                session: selectedSession
            };

            if (!payload.session) {
                showErrors(['Session is required.']);
                return;
            }

            try {
                const response = await fetch('{{ route('principal.teacher-assignments.store') }}', {
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
                    if (result.errors) {
                        showErrors(Object.values(result.errors).flat());
                    } else {
                        showErrors([result.message || 'Assignment failed.']);
                    }
                    return;
                }

                sessionFilterInput.value = selectedSession;
                state.session = selectedSession;
                assignmentForm.reset();
                document.getElementById('session').value = selectedSession || "{{ $defaultSession }}";
                toggleSubjectState();
                state.page = 1;
                await loadAssignments();
                showSuccess(result.message || 'Assignment saved successfully.');
            } catch (error) {
                showErrors(['Unexpected error while saving assignment.']);
            }
        });

        assignmentBody.addEventListener('click', async (event) => {
            const target = event.target;
            if (!target.classList.contains('delete-assignment')) {
                return;
            }

            const id = target.dataset.id;
            if (!confirm('Delete this assignment?')) {
                return;
            }

            const response = await fetch(`/principal/teacher-assignments/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const result = await response.json();
            if (!response.ok) {
                alert(result.message || 'Delete failed.');
                return;
            }

            await loadAssignments();
        });

        classTeacherCheckbox.addEventListener('change', toggleSubjectState);

        prevPageButton.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadAssignments();
            }
        });

        nextPageButton.addEventListener('click', async () => {
            state.page += 1;
            await loadAssignments();
        });

        const onAssignmentsSearch = window.NSMS.debounce(async () => {
            state.search = searchInput.value.trim();
            state.page = 1;
            await loadAssignments();
        }, 300);
        searchInput.addEventListener('input', onAssignmentsSearch);

        perPageInput.addEventListener('change', async () => {
            state.perPage = Number(perPageInput.value);
            state.page = 1;
            await loadAssignments();
        });

        const onSessionFilterChange = async () => {
            state.session = sessionFilterInput.value.trim();
            state.page = 1;
            await loadAssignments();
        };
        sessionFilterInput.addEventListener('change', onSessionFilterChange);

        (async () => {
            await loadOptions();
            toggleSubjectState();
            window.NSMS.lazyInit(assignmentBody, loadAssignments);
        })();
    </script>
</x-app-layout>
