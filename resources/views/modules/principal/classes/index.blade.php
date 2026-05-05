<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Classes
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Create / Edit Class</h3>
                    <p class="text-sm text-gray-600 mt-1">Principal can create classes and assign subjects to each class.</p>

                    <div id="formErrors" class="mt-4 hidden rounded-md bg-red-50 p-3 text-sm text-red-700"></div>

                    <form id="classForm" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="hidden" id="classId" name="class_id">

                        <div>
                            <x-input-label for="name" value="Class Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="Class 1 or Nursery" required />
                        </div>

                        <div>
                            <x-input-label for="section" value="Section (Optional)" />
                            <x-text-input id="section" name="section" type="text" class="mt-1 block w-full" placeholder="A" />
                        </div>

                        <div class="flex items-end gap-3">
                            <x-primary-button id="saveBtn">Save Class</x-primary-button>
                            <button type="button" id="cancelEdit" class="hidden inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Copy Subject Assignments Between Sections</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Copy subjects from one section to another section of the same class (for example, 8 A to 8 B).
                    </p>

                    <div id="copyStatus" class="mt-4 hidden rounded-md p-3 text-sm"></div>
                    <div id="copyErrors" class="mt-4 hidden rounded-md bg-red-50 p-3 text-sm text-red-700"></div>

                    <form id="copySubjectsForm" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <x-input-label for="copySourceClass" value="Source Section" />
                            <select id="copySourceClass" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select source section</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="copyTargetClass" value="Target Section" />
                            <select id="copyTargetClass" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select target section</option>
                            </select>
                        </div>

                        <div>
                            <x-input-label for="copyMode" value="Copy Mode" />
                            <select id="copyMode" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="copy_missing_only">Copy missing only</option>
                                <option value="replace_target_subjects">Replace target subjects</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button id="copySubjectsBtn" type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Copy Subjects
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Class List</h3>
                        <div class="flex items-center gap-2">
                            <input id="searchInput" type="text" placeholder="Search class or section"
                                   class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
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
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Section</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Assigned Subjects</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="classesBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Loading classes...</td>
                                </tr>
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

    <div id="assignModal" class="fixed inset-0 z-50 hidden bg-black/40 px-4 py-8">
        <div class="mx-auto mt-8 w-full max-w-3xl rounded-lg bg-white shadow-xl">
            <div class="border-b px-6 py-4">
                <h4 class="text-lg font-semibold text-gray-900">Assign Subjects</h4>
                <p id="assignClassTitle" class="text-sm text-gray-600 mt-1"></p>
            </div>
            <div class="px-6 py-4">
                <div id="assignErrors" class="mb-4 hidden rounded-md bg-red-50 p-3 text-sm text-red-700"></div>
                <div id="subjectCheckboxes" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto"></div>
            </div>
            <div class="border-t px-6 py-4 flex justify-end gap-2">
                <button type="button" id="closeAssignModal" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" id="saveAssignments" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Subjects</button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const classForm = document.getElementById('classForm');
        const formErrors = document.getElementById('formErrors');
        const classIdInput = document.getElementById('classId');
        const nameInput = document.getElementById('name');
        const sectionInput = document.getElementById('section');
        const saveBtn = document.getElementById('saveBtn');
        const cancelEditBtn = document.getElementById('cancelEdit');

        const classesBody = document.getElementById('classesBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageButton = document.getElementById('prevPage');
        const nextPageButton = document.getElementById('nextPage');
        const searchInput = document.getElementById('searchInput');
        const perPageInput = document.getElementById('perPage');

        const assignModal = document.getElementById('assignModal');
        const assignClassTitle = document.getElementById('assignClassTitle');
        const assignErrors = document.getElementById('assignErrors');
        const subjectCheckboxes = document.getElementById('subjectCheckboxes');
        const closeAssignModalButton = document.getElementById('closeAssignModal');
        const saveAssignmentsButton = document.getElementById('saveAssignments');
        const copySubjectsForm = document.getElementById('copySubjectsForm');
        const copySourceClass = document.getElementById('copySourceClass');
        const copyTargetClass = document.getElementById('copyTargetClass');
        const copyMode = document.getElementById('copyMode');
        const copyStatus = document.getElementById('copyStatus');
        const copyErrors = document.getElementById('copyErrors');
        const copySubjectsBtn = document.getElementById('copySubjectsBtn');

        let state = {
            page: 1,
            perPage: 10,
            search: ''
        };

        let classesCache = [];
        let subjectsCache = [];
        let classesOptions = [];
        let assignTargetClassId = null;

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showErrors(messages) {
            formErrors.classList.remove('hidden');
            formErrors.innerHTML = '<ul class="list-disc ps-5">' + messages.map(m => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>';
        }

        function hideErrors() {
            formErrors.classList.add('hidden');
            formErrors.innerHTML = '';
        }

        function showAssignErrors(messages) {
            assignErrors.classList.remove('hidden');
            assignErrors.innerHTML = '<ul class="list-disc ps-5">' + messages.map(m => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>';
        }

        function hideAssignErrors() {
            assignErrors.classList.add('hidden');
            assignErrors.innerHTML = '';
        }

        function showCopyStatus(message, type = 'success') {
            copyStatus.classList.remove('hidden');
            copyStatus.textContent = message;
            copyStatus.className = type === 'error'
                ? 'mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700'
                : 'mt-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700';
        }

        function hideCopyStatus() {
            copyStatus.classList.add('hidden');
            copyStatus.textContent = '';
        }

        function showCopyErrors(messages) {
            copyErrors.classList.remove('hidden');
            copyErrors.innerHTML = '<ul class="list-disc ps-5">' + messages.map(m => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>';
        }

        function hideCopyErrors() {
            copyErrors.classList.add('hidden');
            copyErrors.innerHTML = '';
        }

        function resetForm() {
            classForm.reset();
            classIdInput.value = '';
            saveBtn.textContent = 'Save Class';
            cancelEditBtn.classList.add('hidden');
            hideErrors();
        }

        function renderSubjectSummary(subjects) {
            if (!subjects || subjects.length === 0) {
                return '<span class="text-gray-500">No subjects assigned</span>';
            }

            return subjects.map(subject => `<span class="inline-flex me-1 mb-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">${escapeHtml(subject.name)}</span>`).join('');
        }

        function renderCopyClassOptions() {
            const defaultOption = '<option value="">Select section</option>';
            const optionsHtml = classesOptions.map((classRoom) => {
                const label = classRoom.status === 'active'
                    ? classRoom.display_name
                    : `${classRoom.display_name} (Inactive)`;

                return `<option value="${classRoom.id}">${escapeHtml(label)}</option>`;
            }).join('');

            copySourceClass.innerHTML = defaultOption + optionsHtml;
            copyTargetClass.innerHTML = defaultOption + optionsHtml;
        }

        async function loadSubjectsOptions() {
            const response = await fetch(`{{ route('principal.classes.options') }}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Failed to load subjects.');
            }

            const payload = await response.json();
            subjectsCache = payload.subjects || [];
            classesOptions = Array.isArray(payload.classes) ? payload.classes : [];
            renderCopyClassOptions();
        }

        async function loadClasses() {
            classesBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Loading classes...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.perPage,
                search: state.search
            });

            try {
                const response = await fetch(`{{ route('principal.classes.data') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Failed');
                }

                const payload = await response.json();
                classesCache = payload.data;

                if (payload.data.length === 0) {
                    classesBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No classes found.</td></tr>';
                } else {
                    classesBody.innerHTML = payload.data.map(classRoom => `
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(classRoom.name)}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(classRoom.section ?? '-')}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${renderSubjectSummary(classRoom.subjects)}</td>
                            <td class="px-4 py-2 text-sm">
                                <div class="flex gap-2">
                                    <button type="button" class="edit-btn rounded-md bg-amber-500 px-3 py-1 text-white hover:bg-amber-600" data-id="${classRoom.id}">Edit</button>
                                    <button type="button" class="assign-btn rounded-md bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700" data-id="${classRoom.id}">Assign Subjects</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }

                paginationInfo.textContent = `Page ${payload.meta.current_page} of ${payload.meta.last_page} | Total: ${payload.meta.total}`;
                prevPageButton.disabled = payload.meta.current_page <= 1;
                nextPageButton.disabled = payload.meta.current_page >= payload.meta.last_page;
            } catch (error) {
                classesBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">Failed to load classes.</td></tr>';
            }
        }

        function openAssignModal(classRoom) {
            assignTargetClassId = classRoom.id;
            assignClassTitle.textContent = `Class: ${classRoom.display_name}`;
            hideAssignErrors();

            const selectedIds = new Set((classRoom.subjects || []).map(subject => Number(subject.id)));

            subjectCheckboxes.innerHTML = subjectsCache.map(subject => `
                <label class="flex items-center gap-2 rounded border border-gray-200 p-2 hover:bg-gray-50">
                    <input type="checkbox" class="subject-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           value="${subject.id}" ${selectedIds.has(Number(subject.id)) ? 'checked' : ''}>
                    <span class="text-sm text-gray-800">${escapeHtml(subject.name)}</span>
                    ${subject.is_default ? '<span class="ms-auto inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-800">Default</span>' : ''}
                </label>
            `).join('');

            assignModal.classList.remove('hidden');
        }

        function closeAssignModal() {
            assignTargetClassId = null;
            assignModal.classList.add('hidden');
            hideAssignErrors();
        }

        classForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideErrors();

            const classId = classIdInput.value;
            const isEdit = classId !== '';
            const endpoint = isEdit ? `/principal/classes/${classId}` : `{{ route('principal.classes.store') }}`;
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                name: nameInput.value.trim(),
                section: sectionInput.value.trim() || null
            };

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
                        showErrors([result.message || 'Save failed.']);
                    }
                    return;
                }

                resetForm();
                state.page = 1;
                await loadClasses();
            } catch (error) {
                showErrors(['Unexpected error occurred.']);
            }
        });

        classesBody.addEventListener('click', (event) => {
            const target = event.target;

            if (target.classList.contains('edit-btn')) {
                const id = target.dataset.id;
                const classRoom = classesCache.find(item => String(item.id) === String(id));
                if (!classRoom) {
                    return;
                }

                classIdInput.value = classRoom.id;
                nameInput.value = classRoom.name;
                sectionInput.value = classRoom.section ?? '';
                saveBtn.textContent = 'Update Class';
                cancelEditBtn.classList.remove('hidden');
                hideErrors();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            if (target.classList.contains('assign-btn')) {
                const id = target.dataset.id;
                const classRoom = classesCache.find(item => String(item.id) === String(id));
                if (!classRoom) {
                    return;
                }

                openAssignModal(classRoom);
            }
        });

        saveAssignmentsButton.addEventListener('click', async () => {
            if (!assignTargetClassId) {
                return;
            }

            hideAssignErrors();

            const checked = Array.from(document.querySelectorAll('.subject-checkbox:checked')).map(input => Number(input.value));

            try {
                const response = await fetch(`/principal/classes/${assignTargetClassId}/assign-subjects`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ subject_ids: checked })
                });

                const result = await response.json();
                if (!response.ok) {
                    if (result.errors) {
                        showAssignErrors(Object.values(result.errors).flat());
                    } else {
                        showAssignErrors([result.message || 'Failed to update class subjects.']);
                    }
                    return;
                }

                closeAssignModal();
                await loadClasses();
            } catch (error) {
                showAssignErrors(['Unexpected error occurred while saving class subjects.']);
            }
        });

        copySubjectsForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideCopyErrors();
            hideCopyStatus();

            const sourceClassId = Number(copySourceClass.value);
            const targetClassId = Number(copyTargetClass.value);
            const mode = copyMode.value || 'copy_missing_only';

            if (!sourceClassId || !targetClassId) {
                showCopyErrors(['Please select both source and target sections.']);
                return;
            }

            if (mode === 'replace_target_subjects') {
                const proceed = window.confirm('Replace mode will remove existing target subjects before copying. Continue?');
                if (!proceed) {
                    return;
                }
            }

            copySubjectsBtn.disabled = true;
            copySubjectsBtn.textContent = 'Copying...';

            try {
                const response = await fetch(`{{ route('principal.classes.copy-subjects') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        source_class_id: sourceClassId,
                        target_class_id: targetClassId,
                        copy_mode: mode,
                    })
                });

                const result = await response.json();
                if (!response.ok) {
                    if (result.errors) {
                        showCopyErrors(Object.values(result.errors).flat());
                    } else {
                        showCopyErrors([result.message || 'Failed to copy subjects.']);
                    }
                    return;
                }

                showCopyStatus(result.message || 'Subjects copied successfully.');
                await loadClasses();
            } catch (error) {
                showCopyErrors(['Unexpected error occurred while copying subjects.']);
            } finally {
                copySubjectsBtn.disabled = false;
                copySubjectsBtn.textContent = 'Copy Subjects';
            }
        });

        closeAssignModalButton.addEventListener('click', closeAssignModal);

        assignModal.addEventListener('click', (event) => {
            if (event.target === assignModal) {
                closeAssignModal();
            }
        });

        cancelEditBtn.addEventListener('click', resetForm);

        prevPageButton.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadClasses();
            }
        });

        nextPageButton.addEventListener('click', async () => {
            state.page += 1;
            await loadClasses();
        });

        const onClassesSearch = window.NSMS.debounce(async () => {
            state.search = searchInput.value.trim();
            state.page = 1;
            await loadClasses();
        }, 300);
        searchInput.addEventListener('input', onClassesSearch);

        perPageInput.addEventListener('change', async () => {
            state.perPage = Number(perPageInput.value);
            state.page = 1;
            await loadClasses();
        });

        async function boot() {
            try {
                await loadSubjectsOptions();
                window.NSMS.lazyInit(classesBody, loadClasses);
            } catch (error) {
                classesBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">Failed to initialize classes module.</td></tr>';
            }
        }

        boot();
    </script>
</x-app-layout>
