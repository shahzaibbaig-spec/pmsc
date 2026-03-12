<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Payroll Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Generate monthly payroll, review salary items, and manage payroll profiles.</p>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="payrollDashboardPage({
            dataUrl: @js(route('principal.payroll.dashboard.data')),
            itemDetailUrlTemplate: @js(route('principal.payroll.dashboard.item', ['payrollItem' => '__ID__'])),
            profileDetailUrlTemplate: @js(route('principal.payroll.dashboard.profile', ['payrollProfile' => '__ID__'])),
            slipPdfUrlTemplate: @js(route('principal.payroll.slips.pdf', ['payrollItem' => '__ID__'])),
            generateUrl: @js(route('principal.payroll.generate.run')),
            salarySheetUrl: @js(route('principal.payroll.sheet.index')),
            reportsUrl: @js(route('principal.payroll.reports.index')),
            profilesUrl: @js(route('principal.payroll.profiles.index')),
            csrfToken: @js(csrf_token()),
            defaultMonth: @js($defaultMonth),
            defaultYear: @js($defaultYear),
            canGenerate: @js($canGenerate),
            canViewSlips: @js($canViewSlips),
            canEditProfiles: @js($canEditProfiles),
            canViewSheet: @js($canViewSheet),
            canViewReports: @js($canViewReports),
            canViewProfiles: @js($canViewProfiles),
        })"
        x-init="init()"
    >
        <div
            x-show="status.message !== ''"
            x-cloak
            class="rounded-xl border px-4 py-3 text-sm"
            :class="status.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
            x-text="status.message"
        ></div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-6">
                <div>
                    <label for="payroll_month" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Month</label>
                    <select id="payroll_month" x-model.number="month" @change="loadTable(true)" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($monthOptions as $month)
                            <option value="{{ $month['value'] }}">{{ $month['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="payroll_year" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Year</label>
                    <select id="payroll_year" x-model.number="year" @change="loadTable(true)" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($yearOptions as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="xl:col-span-2">
                    <label for="payroll_search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search Employee</label>
                    <input id="payroll_search" type="text" x-model="search" @input="onSearchInput()" placeholder="Name or email" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="payroll_status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select id="payroll_status" x-model="statusFilter" @change="loadTable(true)" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        <option value="generated">Generated</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div>
                    <label for="payroll_per_page" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rows</label>
                    <select id="payroll_per_page" x-model.number="perPage" @change="loadTable(true)" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="generatePayroll()"
                    :disabled="!canGenerate || generating"
                    class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span x-text="generating ? 'Generating...' : 'Generate Payroll'"></span>
                </button>
                <a
                    x-show="canViewSheet"
                    :href="`${salarySheetUrl}?month=${selectedMonthKey()}`"
                    class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Salary Sheet
                </a>
                <a
                    x-show="canViewReports"
                    :href="reportsUrl"
                    class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Reports
                </a>
                <a
                    x-show="canViewProfiles"
                    :href="profilesUrl"
                    class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Payroll Profiles
                </a>
                <button
                    type="button"
                    @click="loadTable(true)"
                    :disabled="loadingTable"
                    class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Refresh
                </button>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Staff</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900" x-text="Number(kpis.total_staff || 0).toLocaleString()"></p>
                <p class="mt-1 text-xs text-slate-500">Active payroll profiles</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">This Month Payroll</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900" x-text="money(kpis.this_month_payroll)"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="monthLabel"></p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Paid Staff</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-800" x-text="Number(kpis.paid_staff || 0).toLocaleString()"></p>
                <p class="mt-1 text-xs text-emerald-700">Payroll items with paid status</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pending Staff</p>
                <p class="mt-3 text-3xl font-semibold text-amber-800" x-text="Number(kpis.pending_staff || 0).toLocaleString()"></p>
                <p class="mt-1 text-xs text-amber-700">Awaiting payment update</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Monthly Payroll Items</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Month:
                        <span class="font-medium text-slate-700" x-text="monthLabel"></span>
                    </p>
                </div>
                <p
                    x-show="!runExists"
                    class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700"
                >
                    Payroll is not generated for this month yet.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1150px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Month</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Basic</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Allowances</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Deductions</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Net Salary</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="loadingTable">
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Loading payroll items...</td>
                            </tr>
                        </template>
                        <template x-if="!loadingTable && rows.length === 0">
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">No payroll items found for selected month and filters.</td>
                            </tr>
                        </template>
                        <template x-for="row in rows" :key="`payroll-row-${row.id}`">
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-medium text-slate-900" x-text="row.employee_name"></div>
                                    <div class="text-xs text-slate-500" x-text="row.employee_email"></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="row.month_label"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="money(row.basic_salary)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="money(row.allowances_total)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="money(row.deductions_total)"></td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900" x-text="money(row.net_salary)"></td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium" :class="statusBadgeClass(row.status)" x-text="statusLabel(row.status)"></span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="openSlip(row.id)" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                            View Item
                                        </button>
                                        <button
                                            x-show="canViewSlips"
                                            type="button"
                                            @click="downloadSlip(row.id)"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                                        >
                                            Download PDF
                                        </button>
                                        <button
                                            x-show="canViewSlips"
                                            type="button"
                                            @click="printSlip(row.id)"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        >
                                            Print
                                        </button>
                                        <button
                                            type="button"
                                            @click="openProfile(row.payroll_profile_id)"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-emerald-300 px-3 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                        >
                                            <span x-text="canEditProfiles ? 'Edit Profile' : 'View Profile'"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-slate-500" x-text="paginationLabel()"></p>
                <div class="inline-flex items-center gap-2">
                    <button type="button" @click="changePage(meta.current_page - 1)" :disabled="meta.current_page <= 1 || loadingTable" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Previous</button>
                    <button type="button" @click="changePage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page || loadingTable" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                </div>
            </div>
        </section>

        <div x-show="slip.open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/50" @click="closeSlip()"></div>
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col border-l border-slate-200 bg-slate-50 shadow-xl">
                <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Salary Slip Preview</h3>
                        <p class="mt-1 text-xs text-slate-500" x-text="slip.data?.payload?.employee?.name || 'Loading...'"></p>
                    </div>
                    <button type="button" @click="closeSlip()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100">
                        <span class="sr-only">Close</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </header>

                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    <template x-if="slip.loading">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">Loading salary slip details...</div>
                    </template>

                    <template x-if="!slip.loading && slip.data">
                        <div class="space-y-4">
                            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Employee</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900" x-text="slip.data.payload.employee.name"></p>
                                        <p class="text-xs text-slate-500" x-text="slip.data.payload.employee.email"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Payroll Month</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900" x-text="slip.data.payload.payroll.month_label"></p>
                                        <p class="text-xs text-slate-500" x-text="`Run Date: ${slip.data.payload.payroll.run_date || '-'}`"></p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button x-show="canViewSlips" type="button" @click="downloadSlip(slip.data.item_id)" class="inline-flex min-h-9 items-center rounded-lg border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50">Download PDF</button>
                                    <button x-show="canViewSlips" type="button" @click="printSlip(slip.data.item_id)" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">Print Slip</button>
                                    <button type="button" @click="openProfileFromSlip()" class="inline-flex min-h-9 items-center rounded-lg border border-emerald-300 px-3 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Payroll Profile</button>
                                </div>
                            </section>

                            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <h4 class="text-sm font-semibold text-slate-900">Salary Breakdown</h4>
                                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Basic: <span class="font-semibold" x-text="money(slip.data.payload.components.basic_salary)"></span></div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Allowances: <span class="font-semibold" x-text="money(slip.data.payload.summary.allowances_total)"></span></div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Deductions: <span class="font-semibold" x-text="money(slip.data.payload.summary.deductions_total)"></span></div>
                                </div>
                                <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                    Net Salary:
                                    <span class="font-semibold" x-text="money(slip.data.payload.summary.net_salary)"></span>
                                </div>
                            </section>

                            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Allowances</h5>
                                        <ul class="mt-2 space-y-1">
                                            <template x-for="row in slip.data.payload.components.allowances" :key="`allowance-${row.title}-${row.amount}`">
                                                <li class="flex items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-700">
                                                    <span x-text="row.title"></span>
                                                    <span class="font-semibold" x-text="money(row.amount)"></span>
                                                </li>
                                            </template>
                                            <template x-if="(slip.data.payload.components.allowances || []).length === 0">
                                                <li class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-500">No allowance components.</li>
                                            </template>
                                        </ul>
                                    </div>
                                    <div>
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deductions</h5>
                                        <ul class="mt-2 space-y-1">
                                            <template x-for="row in slip.data.payload.components.deductions" :key="`deduction-${row.title}-${row.amount}`">
                                                <li class="flex items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-700">
                                                    <span x-text="row.title"></span>
                                                    <span class="font-semibold" x-text="money(row.amount)"></span>
                                                </li>
                                            </template>
                                            <template x-if="(slip.data.payload.components.deductions || []).length === 0">
                                                <li class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-500">No deduction components.</li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </template>
                </div>
            </aside>
        </div>

        <div x-show="profile.open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/50" @click="closeProfile()"></div>
            <div class="absolute inset-x-0 top-10 mx-auto w-full max-w-3xl px-4">
                <section class="max-h-[85vh] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                    <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Payroll Profile</h3>
                            <p class="mt-1 text-xs text-slate-500" x-text="profile.data?.profile?.employee_name || 'Loading...'"></p>
                        </div>
                        <button type="button" @click="closeProfile()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100">
                            <span class="sr-only">Close</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </header>
                    <div class="max-h-[calc(85vh-74px)] space-y-4 overflow-y-auto p-5">
                        <template x-if="profile.loading">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Loading payroll profile...</div>
                        </template>

                        <template x-if="!profile.loading && profile.data">
                            <div class="space-y-4">
                                <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-slate-500">Basic Salary</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900" x-text="money(profile.data.profile.basic_salary)"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-slate-500">Allowances Total</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900" x-text="money(profile.data.profile.allowances_total)"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-slate-500">Deductions Total</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900" x-text="money(profile.data.profile.deductions_total)"></p>
                                        </div>
                                    </div>
                                    <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                        Net Estimate:
                                        <span class="font-semibold" x-text="money(profile.data.profile.net_estimate)"></span>
                                    </div>
                                </section>

                                <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <h4 class="text-sm font-semibold text-slate-900">Profile Breakdown</h4>
                                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <h5 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Allowance Rows</h5>
                                            <ul class="mt-2 space-y-1">
                                                <template x-for="row in profile.data.profile.allowance_rows" :key="`profile-allow-${row.title}-${row.amount}`">
                                                    <li class="flex items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-700">
                                                        <span x-text="row.title"></span>
                                                        <span class="font-semibold" x-text="money(row.amount)"></span>
                                                    </li>
                                                </template>
                                                <template x-if="(profile.data.profile.allowance_rows || []).length === 0">
                                                    <li class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-500">No allowance rows added.</li>
                                                </template>
                                            </ul>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deduction Rows</h5>
                                            <ul class="mt-2 space-y-1">
                                                <template x-for="row in profile.data.profile.deduction_rows" :key="`profile-deduct-${row.title}-${row.amount}`">
                                                    <li class="flex items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-700">
                                                        <span x-text="row.title"></span>
                                                        <span class="font-semibold" x-text="money(row.amount)"></span>
                                                    </li>
                                                </template>
                                                <template x-if="(profile.data.profile.deduction_rows || []).length === 0">
                                                    <li class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-500">No deduction rows added.</li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
                                        Bank:
                                        <span class="font-medium" x-text="profile.data.profile.bank_name || '-'"></span>
                                        <span class="mx-1">|</span>
                                        Account:
                                        <span class="font-medium" x-text="profile.data.profile.account_no || '-'"></span>
                                    </div>
                                </section>

                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" @click="closeProfile()" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Close</button>
                                    <a
                                        x-show="canEditProfiles"
                                        :href="profile.data.profile.edit_url"
                                        class="inline-flex min-h-10 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                    >
                                        Edit Payroll Profile
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        function payrollDashboardPage(config) {
            return {
                month: Number(config.defaultMonth || 1),
                year: Number(config.defaultYear || new Date().getFullYear()),
                search: '',
                statusFilter: '',
                perPage: 20,
                searchTimer: null,
                loadingTable: false,
                generating: false,
                monthLabel: '',
                runExists: false,
                rows: [],
                kpis: {
                    total_staff: 0,
                    this_month_payroll: 0,
                    paid_staff: 0,
                    pending_staff: 0,
                },
                meta: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 20,
                    total: 0,
                    from: null,
                    to: null,
                },
                status: {
                    message: '',
                    type: 'success',
                },
                canGenerate: Boolean(config.canGenerate),
                canViewSlips: Boolean(config.canViewSlips),
                canEditProfiles: Boolean(config.canEditProfiles),
                canViewSheet: Boolean(config.canViewSheet),
                canViewReports: Boolean(config.canViewReports),
                canViewProfiles: Boolean(config.canViewProfiles),
                salarySheetUrl: config.salarySheetUrl,
                reportsUrl: config.reportsUrl,
                profilesUrl: config.profilesUrl,
                slip: {
                    open: false,
                    loading: false,
                    data: null,
                },
                profile: {
                    open: false,
                    loading: false,
                    data: null,
                },

                init() {
                    this.loadTable(true);
                },

                setStatus(message, type = 'success') {
                    this.status.message = message;
                    this.status.type = type;
                },

                clearStatus() {
                    this.status.message = '';
                    this.status.type = 'success';
                },

                money(value) {
                    const amount = Number(value || 0);
                    return amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                selectedMonthKey() {
                    const year = String(Number(this.year || new Date().getFullYear()));
                    const month = String(Number(this.month || 1)).padStart(2, '0');
                    return `${year}-${month}`;
                },

                statusLabel(status) {
                    return String(status || '').replace('_', ' ').replace(/\b\w/g, (ch) => ch.toUpperCase());
                },

                statusBadgeClass(status) {
                    if (status === 'paid') {
                        return 'bg-emerald-100 text-emerald-700';
                    }
                    return 'bg-amber-100 text-amber-700';
                },

                buildQuery(page) {
                    const params = new URLSearchParams({
                        page: String(Math.max(Number(page || 1), 1)),
                        per_page: String(this.perPage || 20),
                        year: String(this.year),
                        month: String(this.month),
                    });
                    if (this.search.trim() !== '') {
                        params.set('search', this.search.trim());
                    }
                    if (this.statusFilter) {
                        params.set('status', this.statusFilter);
                    }
                    return params;
                },

                onSearchInput() {
                    if (this.searchTimer) {
                        window.clearTimeout(this.searchTimer);
                    }
                    this.searchTimer = window.setTimeout(() => this.loadTable(true), 300);
                },

                paginationLabel() {
                    const total = Number(this.meta.total || 0);
                    if (total === 0) {
                        return 'No payroll items available';
                    }
                    const from = this.meta.from ?? 1;
                    const to = this.meta.to ?? Math.min(Number(this.meta.per_page || 20), total);
                    return `Showing ${from} to ${to} of ${total} payroll items`;
                },

                itemUrl(template, id) {
                    return String(template).replace('__ID__', String(id));
                },

                async loadTable(resetPage = true, targetPage = null) {
                    this.loadingTable = true;
                    const page = targetPage !== null
                        ? Number(targetPage)
                        : (resetPage ? 1 : Number(this.meta.current_page || 1));

                    try {
                        const params = this.buildQuery(page);
                        const response = await fetch(`${config.dataUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to load payroll items.', 'error');
                            this.rows = [];
                            return;
                        }

                        this.rows = result.rows || [];
                        this.kpis = result.kpis || this.kpis;
                        this.meta = {
                            current_page: Number(result.meta?.current_page || 1),
                            last_page: Number(result.meta?.last_page || 1),
                            per_page: Number(result.meta?.per_page || this.perPage),
                            total: Number(result.meta?.total || 0),
                            from: result.meta?.from ?? null,
                            to: result.meta?.to ?? null,
                        };
                        this.monthLabel = result.month_label || this.selectedMonthKey();
                        this.runExists = Boolean(result.run_exists);
                    } catch (error) {
                        this.setStatus('Unexpected error while loading payroll data.', 'error');
                    } finally {
                        this.loadingTable = false;
                    }
                },

                changePage(page) {
                    if (page < 1 || page > Number(this.meta.last_page || 1)) {
                        return;
                    }
                    this.loadTable(false, page);
                },

                async generatePayroll() {
                    if (!this.canGenerate) {
                        this.setStatus('You do not have permission to generate payroll.', 'error');
                        return;
                    }

                    this.generating = true;
                    this.clearStatus();
                    try {
                        const response = await fetch(config.generateUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                month: this.selectedMonthKey(),
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to generate payroll.', 'error');
                            return;
                        }

                        this.setStatus(result.message || 'Payroll generated successfully.');
                        await this.loadTable(true);
                    } catch (error) {
                        this.setStatus('Unexpected error while generating payroll.', 'error');
                    } finally {
                        this.generating = false;
                    }
                },

                downloadSlip(itemId) {
                    window.open(this.itemUrl(config.slipPdfUrlTemplate, itemId), '_blank');
                },

                printSlip(itemId) {
                    const windowRef = window.open(this.itemUrl(config.slipPdfUrlTemplate, itemId), '_blank');
                    if (windowRef) {
                        windowRef.focus();
                    }
                },

                async openSlip(itemId) {
                    this.slip.open = true;
                    this.slip.loading = true;
                    this.slip.data = null;

                    try {
                        const response = await fetch(this.itemUrl(config.itemDetailUrlTemplate, itemId), {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to load payroll item details.', 'error');
                            this.closeSlip();
                            return;
                        }

                        this.slip.data = result;
                    } catch (error) {
                        this.setStatus('Unexpected error while loading payroll item details.', 'error');
                        this.closeSlip();
                    } finally {
                        this.slip.loading = false;
                    }
                },

                closeSlip() {
                    this.slip.open = false;
                    this.slip.loading = false;
                    this.slip.data = null;
                },

                async openProfile(profileId) {
                    if (!profileId) {
                        this.setStatus('Payroll profile is not available for this item.', 'error');
                        return;
                    }

                    this.profile.open = true;
                    this.profile.loading = true;
                    this.profile.data = null;

                    try {
                        const response = await fetch(this.itemUrl(config.profileDetailUrlTemplate, profileId), {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to load payroll profile.', 'error');
                            this.closeProfile();
                            return;
                        }

                        this.profile.data = result;
                    } catch (error) {
                        this.setStatus('Unexpected error while loading payroll profile.', 'error');
                        this.closeProfile();
                    } finally {
                        this.profile.loading = false;
                    }
                },

                openProfileFromSlip() {
                    const profileId = this.slip.data?.profile?.id || null;
                    if (!profileId) {
                        this.setStatus('Payroll profile is not available for this employee.', 'error');
                        return;
                    }

                    this.openProfile(profileId);
                },

                closeProfile() {
                    this.profile.open = false;
                    this.profile.loading = false;
                    this.profile.data = null;
                },
            };
        }
    </script>
</x-app-layout>
