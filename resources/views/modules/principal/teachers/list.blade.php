<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Teachers List</h2>
    </x-slot>

    <x-ui.card title="Teachers" subtitle="Principal directory with server-side table updates.">
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-ui.input id="teachersSearch" label="Search" placeholder="Name, email, employee code" />
            <div>
                <label for="teachersPerPage" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Per Page</label>
                <select id="teachersPerPage" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="flex items-end justify-end">
                <x-ui.button href="{{ route('principal.teacher-assignments.index') }}" variant="outline">Manage Assignments</x-ui.button>
            </div>
        </div>

        <x-ui.table>
            <thead class="bg-slate-50">
                <tr>
                    <th data-sort="name" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                    <th data-sort="email" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                    <th data-sort="employee_code" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee Code</th>
                    <th data-sort="designation" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Designation</th>
                    <th data-sort="assignments_count" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Assignments</th>
                    <th data-sort="status" class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                </tr>
            </thead>
            <tbody id="teachersRows" class="divide-y divide-slate-100 bg-white">
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Loading teachers...</td>
                </tr>
            </tbody>
        </x-ui.table>

        <x-ui.pagination infoId="teachersPaginationInfo" prevId="teachersPrevBtn" nextId="teachersNextBtn" />
    </x-ui.card>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            new window.AjaxTable({
                endpoint: `{{ route('principal.teachers.data') }}`,
                tbody: '#teachersRows',
                searchInput: '#teachersSearch',
                perPageInput: '#teachersPerPage',
                prevBtn: '#teachersPrevBtn',
                nextBtn: '#teachersNextBtn',
                paginationInfo: '#teachersPaginationInfo',
                sortHeaders: 'th[data-sort]',
                rowRenderer: (row) => `
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">${window.NSMS.escapeHtml(row.name)}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.email)}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.employee_code || '-')}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.designation || '-')}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(String(row.assignments_count || 0))}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold ${row.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}">
                                ${window.NSMS.escapeHtml(row.status || 'active')}
                            </span>
                        </td>
                    </tr>
                `,
            });
        });
    </script>
</x-app-layout>
