<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Subjects
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Add New Custom Subject</h3>
                    <p class="text-sm text-gray-600 mt-1">Federal Board default subjects are marked and protected from deletion.</p>

                    <div id="formErrors" class="mt-4 hidden rounded-md bg-red-50 p-3 text-sm text-red-700"></div>

                    <form id="subjectForm" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="hidden" id="subjectId" name="subject_id">

                        <div>
                            <x-input-label for="name" value="Subject Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                        </div>

                        <div>
                            <x-input-label for="code" value="Code" />
                            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" />
                        </div>

                        <div class="flex items-end gap-3">
                            <x-primary-button id="saveBtn">Save Subject</x-primary-button>
                            <button type="button" id="cancelEdit" class="hidden inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Subject List</h3>
                        <div class="flex items-center gap-2">
                            <input id="searchInput" type="text" placeholder="Search by name or code"
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
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Code</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="subjectsBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Loading subjects...</td>
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

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const subjectForm = document.getElementById('subjectForm');
        const formErrors = document.getElementById('formErrors');
        const saveBtn = document.getElementById('saveBtn');
        const cancelEditBtn = document.getElementById('cancelEdit');
        const subjectIdInput = document.getElementById('subjectId');
        const nameInput = document.getElementById('name');
        const codeInput = document.getElementById('code');

        const subjectsBody = document.getElementById('subjectsBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageButton = document.getElementById('prevPage');
        const nextPageButton = document.getElementById('nextPage');
        const searchInput = document.getElementById('searchInput');
        const perPageInput = document.getElementById('perPage');

        let state = {
            page: 1,
            perPage: 10,
            search: ''
        };

        let subjectsCache = [];

        function showErrors(messages) {
            formErrors.classList.remove('hidden');
            formErrors.innerHTML = '<ul class="list-disc ps-5">' + messages.map(m => `<li>${m}</li>`).join('') + '</ul>';
        }

        function hideErrors() {
            formErrors.classList.add('hidden');
            formErrors.innerHTML = '';
        }

        function resetForm() {
            subjectForm.reset();
            subjectIdInput.value = '';
            saveBtn.textContent = 'Save Subject';
            cancelEditBtn.classList.add('hidden');
            hideErrors();
        }

        async function loadSubjects() {
            subjectsBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Loading subjects...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.perPage,
                search: state.search
            });

            try {
                const response = await fetch(`{{ route('principal.subjects.data') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Failed');
                }

                const payload = await response.json();
                subjectsCache = payload.data;

                if (payload.data.length === 0) {
                    subjectsBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No subjects found.</td></tr>';
                } else {
                    subjectsBody.innerHTML = payload.data.map(subject => `
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800">${subject.name}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${subject.code ?? '-'}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">
                                ${subject.is_default
                                    ? '<span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">Default</span>'
                                    : '<span class="inline-flex rounded-full bg-gray-200 px-2 py-1 text-xs font-medium text-gray-800">Custom</span>'
                                }
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <div class="flex gap-2">
                                    <button type="button" class="edit-btn rounded-md bg-amber-500 px-3 py-1 text-white hover:bg-amber-600" data-id="${subject.id}">Edit</button>
                                    <button type="button" class="delete-btn rounded-md bg-red-600 px-3 py-1 text-white hover:bg-red-700 ${subject.is_default ? 'opacity-50 cursor-not-allowed' : ''}" data-id="${subject.id}" ${subject.is_default ? 'disabled' : ''}>Delete</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }

                paginationInfo.textContent = `Page ${payload.meta.current_page} of ${payload.meta.last_page} | Total: ${payload.meta.total}`;
                prevPageButton.disabled = payload.meta.current_page <= 1;
                nextPageButton.disabled = payload.meta.current_page >= payload.meta.last_page;
            } catch (error) {
                subjectsBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">Failed to load subjects.</td></tr>';
            }
        }

        subjectForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideErrors();

            const subjectId = subjectIdInput.value;
            const isEdit = subjectId !== '';
            const endpoint = isEdit ? `/principal/subjects/${subjectId}` : `{{ route('principal.subjects.store') }}`;
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                name: nameInput.value.trim(),
                code: codeInput.value.trim() || null
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
                await loadSubjects();
            } catch (error) {
                showErrors(['Unexpected error occurred.']);
            }
        });

        subjectsBody.addEventListener('click', async (event) => {
            const target = event.target;

            if (target.classList.contains('edit-btn')) {
                const id = target.dataset.id;
                const subject = subjectsCache.find(s => String(s.id) === String(id));
                if (!subject) return;

                subjectIdInput.value = subject.id;
                nameInput.value = subject.name;
                codeInput.value = subject.code ?? '';
                saveBtn.textContent = 'Update Subject';
                cancelEditBtn.classList.remove('hidden');
                hideErrors();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            if (target.classList.contains('delete-btn')) {
                const id = target.dataset.id;
                if (!confirm('Delete this subject?')) {
                    return;
                }

                const response = await fetch(`/principal/subjects/${id}`, {
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

                await loadSubjects();
            }
        });

        cancelEditBtn.addEventListener('click', resetForm);

        prevPageButton.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadSubjects();
            }
        });

        nextPageButton.addEventListener('click', async () => {
            state.page += 1;
            await loadSubjects();
        });

        const onSubjectsSearch = window.NSMS.debounce(async () => {
            state.search = searchInput.value.trim();
            state.page = 1;
            await loadSubjects();
        }, 300);
        searchInput.addEventListener('input', onSubjectsSearch);

        perPageInput.addEventListener('change', async () => {
            state.perPage = Number(perPageInput.value);
            state.page = 1;
            await loadSubjects();
        });

        window.NSMS.lazyInit(subjectsBody, loadSubjects);
    </script>
</x-app-layout>
