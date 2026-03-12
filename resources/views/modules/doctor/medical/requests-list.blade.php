<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Medical Requests</h2>
    </x-slot>

    <x-ui.card title="Medical Requests Queue" subtitle="Doctor view with AJAX pagination and filters.">
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
            <x-ui.input id="medicalSearch" label="Search" placeholder="Student name, ID, illness" />
            <div>
                <label for="medicalStatus" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                <select id="medicalStatus" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div>
                <label for="medicalPerPage" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Per Page</label>
                <select id="medicalPerPage" class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="flex items-end justify-end">
                <x-ui.button href="{{ route('doctor.medical.referrals.index') }}" variant="outline">Open Full Referral Panel</x-ui.button>
            </div>
        </div>

        <x-ui.table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Illness</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Referred At</th>
                </tr>
            </thead>
            <tbody id="medicalRows" class="divide-y divide-slate-100 bg-white">
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Loading medical requests...</td>
                </tr>
            </tbody>
        </x-ui.table>

        <x-ui.pagination infoId="medicalPaginationInfo" prevId="medicalPrevBtn" nextId="medicalNextBtn" />
    </x-ui.card>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const statusInput = document.getElementById('medicalStatus');
            const table = new window.AjaxTable({
                endpoint: `{{ route('doctor.medical.referrals.data') }}`,
                tbody: '#medicalRows',
                searchInput: '#medicalSearch',
                perPageInput: '#medicalPerPage',
                prevBtn: '#medicalPrevBtn',
                nextBtn: '#medicalNextBtn',
                paginationInfo: '#medicalPaginationInfo',
                sortHeaders: null,
                extraParams: () => ({
                    status: statusInput.value,
                }),
                rowRenderer: (row) => `
                    <tr>
                        <td class="px-4 py-3 text-sm">
                            <p class="font-medium text-slate-900">${window.NSMS.escapeHtml(row.student_name || '-')}</p>
                            <p class="text-xs text-slate-500">${window.NSMS.escapeHtml(row.student_id || '-')}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.class_name || '-')}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.illness_label || row.illness_type || '-')}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold ${row.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                                ${window.NSMS.escapeHtml(row.status || 'pending')}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">${window.NSMS.escapeHtml(row.referred_at || row.created_at || '-')}</td>
                    </tr>
                `,
            });

            statusInput.addEventListener('change', () => table.reload());
        });
    </script>
</x-app-layout>
