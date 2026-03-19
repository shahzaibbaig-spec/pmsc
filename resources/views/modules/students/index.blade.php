<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Student List
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('students_import_error'))
                        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            {{ session('students_import_error') }}
                        </div>
                    @endif

                    @if (session('students_bulk_error'))
                        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            {{ session('students_bulk_error') }}
                        </div>
                    @endif

                    @if (session('students_import_summary'))
                        @php($importSummary = session('students_import_summary'))
                        <div class="mb-4 rounded-md border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">
                            <p class="font-semibold">Excel Import Summary</p>
                            <p class="mt-1">
                                Total: {{ $importSummary['total_rows'] ?? 0 }},
                                Created: {{ $importSummary['created'] ?? 0 }},
                                Updated: {{ $importSummary['updated'] ?? 0 }},
                                Skipped: {{ $importSummary['skipped'] ?? 0 }}
                            </p>
                            @if (!empty($importSummary['errors']))
                                <p class="mt-2 font-medium">Errors (first 10):</p>
                                <ul class="mt-1 list-disc ps-5">
                                    @foreach(array_slice($importSummary['errors'], 0, 10) as $error)
                                        <li>Row {{ $error['row'] ?? '-' }}: {{ $error['message'] ?? 'Unknown error' }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    @if (session('students_bulk_summary'))
                        @php($bulkSummary = session('students_bulk_summary'))
                        <div class="mb-4 rounded-md border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">
                            <p class="font-semibold">Bulk Add Summary</p>
                            <p class="mt-1">
                                Total: {{ $bulkSummary['total_rows'] ?? 0 }},
                                Created: {{ $bulkSummary['created'] ?? 0 }},
                                Updated: {{ $bulkSummary['updated'] ?? 0 }},
                                Skipped: {{ $bulkSummary['skipped'] ?? 0 }}
                            </p>
                            @if (!empty($bulkSummary['errors']))
                                <p class="mt-2 font-medium">Errors (first 10):</p>
                                <ul class="mt-1 list-disc ps-5">
                                    @foreach(array_slice($bulkSummary['errors'], 0, 10) as $error)
                                        <li>Row {{ $error['row'] ?? '-' }}: {{ $error['message'] ?? 'Unknown error' }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Students</h3>
                                <p class="text-sm text-gray-600">Admin can import from Excel, bulk add, and bulk delete students.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.students.create') }}"
                                   class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                    Add Student
                                </a>
                                <select id="idCardClassSelect" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ trim($class->name.' '.($class->section ?? '')) }}</option>
                                    @endforeach
                                </select>
                                <a
                                    id="bulkIdCardButton"
                                    href="#"
                                    target="_blank"
                                    class="inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                                    aria-disabled="true"
                                >
                                    Bulk ID Cards
                                </a>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-2 rounded-md border border-gray-200 bg-gray-50 p-3 md:grid-cols-6">
                            @csrf
                            <div class="md:col-span-3">
                                <label for="workbook" class="block text-xs font-medium uppercase tracking-wide text-gray-600">Import Excel (xls/xlsx/csv)</label>
                                <input id="workbook" name="workbook" type="file" accept=".xls,.xlsx,.csv,.txt" required class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-2 flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="update_existing" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Update existing rows by student_id
                                </label>
                            </div>
                            <div class="md:col-span-1 flex items-end justify-end">
                                <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                    Import
                                </button>
                            </div>
                        </form>

                        <details class="rounded-md border border-gray-200 bg-gray-50 p-3">
                            <summary class="cursor-pointer text-sm font-medium text-gray-800">Bulk Add Students (Paste CSV)</summary>
                            <form method="POST" action="{{ route('admin.students.bulk-add') }}" class="mt-3 grid grid-cols-1 gap-3">
                                @csrf
                                <p class="text-xs text-gray-600">
                                    Required header format:
                                    <code>student_id,name,father_name,class,status,contact,address,date_of_birth</code>
                                </p>
                                <textarea
                                    name="rows"
                                    rows="7"
                                    class="w-full rounded-md border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="student_id,name,father_name,class,status,contact,address,date_of_birth&#10;KORT-000001,Ali Khan,Ahmed Khan,Class 6,active,03001234567,Kotli,2012-05-10"
                                    required
                                ></textarea>
                                <div class="flex items-center justify-between">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="update_existing" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        Update existing rows by student_id
                                    </label>
                                    <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                        Bulk Add
                                    </button>
                                </div>
                            </form>
                        </details>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mt-1 flex flex-col md:flex-row gap-2 md:items-center md:justify-between">
                        <div class="flex items-center gap-2">
                            <button
                                id="bulkDeleteButton"
                                type="button"
                                class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                disabled
                            >
                                Delete Selected
                            </button>
                            <span id="selectedMeta" class="text-sm text-gray-600">0 selected</span>
                        </div>

                        <div class="mt-2 flex flex-col md:mt-0 md:flex-row gap-2 md:items-center">
                            <input
                                id="searchInput"
                                type="text"
                                placeholder="Search by ID, name, father, class, contact"
                                class="w-full md:w-96 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            >
                            <select id="perPage" class="w-24 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
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
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">
                                        <input id="selectAll" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Father Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Contact</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentsBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Loading students...</td>
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
        const studentsBody = document.getElementById('studentsBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageButton = document.getElementById('prevPage');
        const nextPageButton = document.getElementById('nextPage');
        const searchInput = document.getElementById('searchInput');
        const perPageInput = document.getElementById('perPage');
        const selectAllCheckbox = document.getElementById('selectAll');
        const bulkDeleteButton = document.getElementById('bulkDeleteButton');
        const selectedMeta = document.getElementById('selectedMeta');
        const idCardClassSelect = document.getElementById('idCardClassSelect');
        const bulkIdCardButton = document.getElementById('bulkIdCardButton');
        const bulkIdCardRouteTemplate = @json(route('idcards.class', ['class' => '__CLASS__']));

        let state = {
            page: 1,
            perPage: 10,
            search: ''
        };

        const selectedIds = new Set();
        let currentPageIds = [];

        function statusBadge(status) {
            if (status === 'active') {
                return '<span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">active</span>';
            }
            return '<span class="inline-flex rounded-full bg-gray-200 px-2 py-1 text-xs font-medium text-gray-800">inactive</span>';
        }

        function syncSelectionUi() {
            const selectedCount = selectedIds.size;
            selectedMeta.textContent = `${selectedCount} selected`;
            bulkDeleteButton.disabled = selectedCount === 0;

            if (!currentPageIds.length) {
                selectAllCheckbox.checked = false;
                return;
            }

            const allCurrentSelected = currentPageIds.every((id) => selectedIds.has(String(id)));
            selectAllCheckbox.checked = allCurrentSelected;
        }

        async function loadStudents() {
            studentsBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Loading students...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.perPage,
                search: state.search
            });

            try {
                const response = await fetch(`{{ route('admin.students.data') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch');
                }

                const payload = await response.json();
                currentPageIds = payload.data.map((student) => student.id);

                if (payload.data.length === 0) {
                    studentsBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No students found.</td></tr>';
                } else {
                    studentsBody.innerHTML = payload.data.map((student) => {
                        const checked = selectedIds.has(String(student.id)) ? 'checked' : '';

                        return `
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-800">
                                    <input type="checkbox" class="row-select rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-id="${student.id}" ${checked}>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800">${window.NSMS.escapeHtml(student.student_id)}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">${window.NSMS.escapeHtml(student.name)}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">${window.NSMS.escapeHtml(student.father_name ?? '-')}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">${window.NSMS.escapeHtml(student.class_name || '-')}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">${window.NSMS.escapeHtml(student.contact ?? '-')}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">${statusBadge(student.status)}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="${student.profile_url}" class="rounded-md bg-sky-600 px-3 py-1 text-white hover:bg-sky-700">Profile</a>
                                        <a href="${student.id_card_url}" target="_blank" class="rounded-md bg-violet-600 px-3 py-1 text-white hover:bg-violet-700">ID Card</a>
                                        <a href="${student.edit_url}" class="rounded-md bg-amber-500 px-3 py-1 text-white hover:bg-amber-600">Edit</a>
                                        <a href="${student.delete_url}" class="rounded-md bg-red-600 px-3 py-1 text-white hover:bg-red-700">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }

                paginationInfo.textContent = `Page ${payload.meta.current_page} of ${payload.meta.last_page} | Total: ${payload.meta.total}`;
                prevPageButton.disabled = payload.meta.current_page <= 1;
                nextPageButton.disabled = payload.meta.current_page >= payload.meta.last_page;
                syncSelectionUi();
            } catch (error) {
                studentsBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
                currentPageIds = [];
                syncSelectionUi();
            }
        }

        async function bulkDeleteSelected() {
            if (!selectedIds.size) {
                return;
            }

            if (!confirm(`Delete ${selectedIds.size} selected student(s)?`)) {
                return;
            }

            const response = await fetch(`{{ route('admin.students.bulk-delete') }}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ ids: Array.from(selectedIds).map((id) => Number(id)) })
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                alert(payload.message || 'Bulk delete failed.');
                return;
            }

            selectedIds.clear();
            await loadStudents();
        }

        prevPageButton.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadStudents();
            }
        });

        nextPageButton.addEventListener('click', async () => {
            state.page += 1;
            await loadStudents();
        });

        const onStudentsSearch = window.NSMS.debounce(async () => {
            state.search = searchInput.value.trim();
            state.page = 1;
            await loadStudents();
        }, 300);
        searchInput.addEventListener('input', onStudentsSearch);

        perPageInput.addEventListener('change', async () => {
            state.perPage = Number(perPageInput.value);
            state.page = 1;
            await loadStudents();
        });

        studentsBody.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.classList.contains('row-select')) {
                return;
            }

            const id = String(target.dataset.id || '');
            if (id === '') {
                return;
            }

            if (target.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }

            syncSelectionUi();
        });

        selectAllCheckbox.addEventListener('change', () => {
            const checked = selectAllCheckbox.checked;
            currentPageIds.forEach((id) => {
                const idString = String(id);
                if (checked) {
                    selectedIds.add(idString);
                } else {
                    selectedIds.delete(idString);
                }
            });

            document.querySelectorAll('.row-select').forEach((checkbox) => {
                checkbox.checked = checked;
            });

            syncSelectionUi();
        });

        bulkDeleteButton.addEventListener('click', bulkDeleteSelected);

        const syncBulkIdCardButton = () => {
            const classId = (idCardClassSelect?.value || '').trim();
            if (!bulkIdCardButton) {
                return;
            }

            if (classId === '') {
                bulkIdCardButton.href = '#';
                bulkIdCardButton.style.pointerEvents = 'none';
                bulkIdCardButton.style.opacity = '0.5';
                bulkIdCardButton.setAttribute('aria-disabled', 'true');
                return;
            }

            bulkIdCardButton.href = bulkIdCardRouteTemplate.replace('__CLASS__', encodeURIComponent(classId));
            bulkIdCardButton.style.pointerEvents = 'auto';
            bulkIdCardButton.style.opacity = '1';
            bulkIdCardButton.setAttribute('aria-disabled', 'false');
        };

        idCardClassSelect?.addEventListener('change', syncBulkIdCardButton);
        syncBulkIdCardButton();

        window.NSMS.lazyInit(studentsBody, loadStudents);
    </script>
</x-app-layout>
