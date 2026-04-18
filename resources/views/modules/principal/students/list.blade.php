<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Students List</h2>
    </x-slot>

    <x-ui.card title="Students" subtitle="Server-side pagination, search, and sorting.">
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
            <x-ui.input id="studentsSearch" label="Search" placeholder="Student name, ID, father name, class" />
            <div>
                <label for="studentsClassFilter" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Class</label>
                <select id="studentsClassFilter" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ trim($class->name.' '.($class->section ?? '')) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="studentsPerPage" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Per Page</label>
                <select id="studentsPerPage" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="flex flex-wrap items-end justify-end gap-2">
                <div>
                    <label for="idCardClassSelect" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Bulk ID Cards</label>
                    <select id="idCardClassSelect" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ trim($class->name.' '.($class->section ?? '')) }}</option>
                        @endforeach
                    </select>
                </div>
                <a id="bulkIdCardButton" href="#" target="_blank" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Generate Bulk
                </a>
                @can('manage_subject_assignments')
                    <x-ui.button href="{{ route('principal.subject-matrix.index') }}" variant="outline">Open Subject Matrix</x-ui.button>
                @endcan
            </div>
        </div>

        <x-ui.table>
            <thead class="bg-slate-50">
                <tr>
                    <th data-sort="student_id" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student ID</th>
                    <th data-sort="name" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                    <th data-sort="father_name" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Father Name</th>
                    <th data-sort="class_name" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                    <th data-sort="status" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody id="studentsRows" class="divide-y divide-slate-100 bg-white">
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Loading students...</td>
                </tr>
            </tbody>
        </x-ui.table>

        <x-ui.pagination infoId="studentsPaginationInfo" prevId="studentsPrevBtn" nextId="studentsNextBtn" />
    </x-ui.card>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const classFilter = document.getElementById('studentsClassFilter');
            const studentsTable = new window.AjaxTable({
                endpoint: `{{ route('principal.students.data') }}`,
                tbody: '#studentsRows',
                searchInput: '#studentsSearch',
                perPageInput: '#studentsPerPage',
                prevBtn: '#studentsPrevBtn',
                nextBtn: '#studentsNextBtn',
                paginationInfo: '#studentsPaginationInfo',
                sortHeaders: 'th[data-sort]',
                extraParams: () => ({
                    class_id: classFilter?.value || '',
                }),
                rowRenderer: (row) => `
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">${window.NSMS.escapeHtml(row.student_id)}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.name)}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.father_name || '-')}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.class_name || '-')}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold ${row.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}">
                                ${window.NSMS.escapeHtml(row.status || 'active')}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-wrap gap-2">
                                <a href="${row.profile_url}" class="font-medium text-indigo-600 hover:text-indigo-700">View Profile</a>
                                <a href="${row.id_card_url}" target="_blank" class="font-medium text-violet-600 hover:text-violet-700">ID Card</a>
                            </div>
                        </td>
                    </tr>
                `,
            });

            classFilter?.addEventListener('change', () => {
                studentsTable.state.page = 1;
                studentsTable.reload();
            });

            const classSelect = document.getElementById('idCardClassSelect');
            const bulkButton = document.getElementById('bulkIdCardButton');
            const routeTemplate = @json(route('idcards.class', ['class' => '__CLASS__']));

            const syncBulkButton = () => {
                const classId = (classSelect?.value || '').trim();
                if (!bulkButton) {
                    return;
                }

                if (classId === '') {
                    bulkButton.href = '#';
                    bulkButton.style.pointerEvents = 'none';
                    bulkButton.style.opacity = '0.5';
                    return;
                }

                bulkButton.href = routeTemplate.replace('__CLASS__', encodeURIComponent(classId));
                bulkButton.style.pointerEvents = 'auto';
                bulkButton.style.opacity = '1';
            };

            classSelect?.addEventListener('change', syncBulkButton);
            syncBulkButton();
        });
    </script>
</x-app-layout>
