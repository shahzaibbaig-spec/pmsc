<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Fee Challan Generator</h2>
            <p class="mt-1 text-sm text-slate-500">Generate class challans, preview fee heads, track payments, and print challans.</p>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="feeChallanGeneratorPage({
            defaultSession: @js($defaultSession),
            defaultMonth: @js($defaultMonth),
            defaultDueDate: @js($defaultDueDate),
            dataUrl: @js(route('principal.fees.challans.data')),
            generateUrl: @js(route('principal.fees.challans.store')),
            feeStructureUrl: @js(route('principal.fees.challans.fee-structure-preview')),
            challanDetailUrlTemplate: @js(route('principal.fees.challans.show', ['feeChallan' => '__ID__'])),
            challanPdfUrlTemplate: @js(route('principal.fees.challans.pdf', ['feeChallan' => '__ID__'])),
            markPaidUrlTemplate: @js(route('principal.fees.challans.mark-paid', ['feeChallan' => '__ID__'])),
            waiveLateFeeUrlTemplate: @js(route('principal.fees.challans.waive-late-fee', ['feeChallan' => '__ID__'])),
            csrfToken: @js(csrf_token()),
            canGenerate: @js($canGenerateChallans),
            canRecord: @js($canRecordPayment),
            canWaive: @js($canWaiveLateFee),
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
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <label for="session_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session_filter" x-model="session" @change="onTopFiltersChange()" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($sessions as $session)
                            <option value="{{ $session }}">{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_filter" x-model="classId" @change="onTopFiltersChange()" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select class</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="month_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Month</label>
                    <input id="month_filter" type="month" x-model="month" @change="loadTable(true)" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="due_date_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Due Date</label>
                    <input id="due_date_filter" type="date" x-model="dueDate" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="flex items-end gap-2">
                    <button
                        type="button"
                        @click="generateChallans()"
                        :disabled="!canGenerate || generating"
                        class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-text="generating ? 'Generating...' : 'Generate Challans'"></span>
                    </button>
                    <button
                        type="button"
                        @click="loadTable(true)"
                        :disabled="loadingTable"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Refresh
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Fee Structure Preview</h3>
                    <p class="mt-1 text-xs text-slate-500">Active fee heads for selected session and class.</p>
                </div>
                <div class="text-xs text-slate-500" x-text="loadingStructure ? 'Loading...' : ''"></div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Monthly Total</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="money(feeSummary.monthly_total)"></p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">One-Time Total</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="money(feeSummary.one_time_total)"></p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Heads</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900" x-text="feeSummary.total_heads || 0"></p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Head</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mode</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="feeHeads.length === 0">
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No active fee heads found for current selection.</td>
                            </tr>
                        </template>
                        <template x-for="head in feeHeads" :key="`head-${head.id}`">
                            <tr>
                                <td class="px-4 py-2 text-sm font-medium text-slate-800" x-text="head.title"></td>
                                <td class="px-4 py-2 text-sm text-slate-700" x-text="head.fee_type"></td>
                                <td class="px-4 py-2 text-sm text-slate-700" x-text="head.is_monthly ? 'Monthly' : 'One Time'"></td>
                                <td class="px-4 py-2 text-sm text-slate-700" x-text="money(head.amount)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label for="search_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                    <input id="search_filter" type="text" x-model="search" @input="onSearchInput()" placeholder="Challan #, student name, student ID" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="status_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select id="status_filter" x-model="statusFilter" @change="loadTable(true)" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div>
                    <label for="per_page_filter" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rows</label>
                    <select id="per_page_filter" x-model.number="perPage" @change="loadTable(true)" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-[1460px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Challan #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Session / Month</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Fee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Arrears</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Late Fee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Paid</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Remaining</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Due Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="loadingTable">
                            <tr>
                                <td colspan="13" class="px-4 py-8 text-center text-sm text-slate-500">Loading challans...</td>
                            </tr>
                        </template>
                        <template x-if="!loadingTable && rows.length === 0">
                            <tr>
                                <td colspan="13" class="px-4 py-8 text-center text-sm text-slate-500">No challans found for selected filters.</td>
                            </tr>
                        </template>
                        <template x-for="row in rows" :key="`challan-row-${row.id}`">
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900" x-text="row.challan_number"></td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-medium" x-text="row.student_name"></div>
                                    <div class="text-xs text-slate-500" x-text="row.student_id"></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="row.class_name"></td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div x-text="row.session"></div>
                                    <div class="text-xs text-slate-500" x-text="row.month_label"></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="money(row.fee_amount)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="money(row.arrears)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="money(row.late_fee)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="money(row.total_amount)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="money(row.paid_amount)"></td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-800" x-text="money(row.remaining_amount)"></td>
                                <td class="px-4 py-3 text-sm text-slate-700" x-text="row.due_date || '-'"></td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium" :class="statusBadgeClass(row.status)" x-text="statusLabel(row.status)"></span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="openDrawer(row.id)" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">View</button>
                                        <button type="button" @click="downloadPdf(row.id)" class="inline-flex min-h-9 items-center rounded-lg border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50">Download PDF</button>
                                        <button type="button" @click="printChallan(row.id)" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">Print</button>
                                        <button
                                            x-show="canWaive && Number(row.late_fee || 0) > 0"
                                            type="button"
                                            @click="waiveLateFee(row.id)"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-amber-300 px-3 text-xs font-medium text-amber-700 hover:bg-amber-50"
                                        >
                                            Waive Late Fee
                                        </button>
                                        <button
                                            x-show="canRecord && row.status !== 'paid'"
                                            type="button"
                                            @click="openDrawer(row.id)"
                                            class="inline-flex min-h-9 items-center rounded-lg border border-emerald-300 px-3 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                                        >
                                            Mark Paid
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
        <div x-show="drawer.open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/50" @click="closeDrawer()"></div>
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col border-l border-slate-200 bg-slate-50 shadow-xl">
                <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Challan Preview</h3>
                        <p class="mt-1 text-xs text-slate-500" x-text="drawer.data?.payload?.challan?.number || 'Loading...'"></p>
                    </div>
                    <button type="button" @click="closeDrawer()" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 text-slate-600 hover:bg-slate-100">
                        <span class="sr-only">Close</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </header>

                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    <template x-if="drawer.loading">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">Loading challan details...</div>
                    </template>

                    <template x-if="!drawer.loading && drawer.data">
                        <div class="space-y-4">
                            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Student</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900" x-text="drawer.data.payload.student.name"></p>
                                        <p class="text-xs text-slate-500" x-text="drawer.data.payload.student.student_id"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Class</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900" x-text="drawer.data.payload.student.class"></p>
                                        <p class="text-xs text-slate-500" x-text="`${drawer.data.payload.challan.session} / ${drawer.data.payload.challan.month_label}`"></p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" @click="downloadPdf(drawer.data.challan_id)" class="inline-flex min-h-9 items-center rounded-lg border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50">Download PDF</button>
                                    <button type="button" @click="printChallan(drawer.data.challan_id)" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50">Print Challan</button>
                                    <button
                                        x-show="canWaive && Number(drawer.data.payload.summary.late_fee || 0) > 0"
                                        type="button"
                                        @click="waiveLateFee(drawer.data.challan_id)"
                                        class="inline-flex min-h-9 items-center rounded-lg border border-amber-300 px-3 text-xs font-medium text-amber-700 hover:bg-amber-50"
                                    >
                                        Waive Late Fee
                                    </button>
                                </div>
                            </section>

                            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <h4 class="text-sm font-semibold text-slate-900">Fee Heads</h4>
                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">Title</th>
                                                <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                                <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <template x-for="item in drawer.data.payload.items" :key="`${item.title}-${item.fee_type}`">
                                                <tr>
                                                    <td class="px-3 py-2 text-xs text-slate-700" x-text="item.title"></td>
                                                    <td class="px-3 py-2 text-xs text-slate-700" x-text="item.fee_type"></td>
                                                    <td class="px-3 py-2 text-xs text-slate-700" x-text="money(item.amount)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Fee: <span class="font-semibold" x-text="money(drawer.data.payload.summary.fee_amount)"></span></div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Arrears: <span class="font-semibold" x-text="money(drawer.data.payload.summary.arrears)"></span></div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Late Fee: <span class="font-semibold" x-text="money(drawer.data.payload.summary.late_fee)"></span></div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Total: <span class="font-semibold" x-text="money(drawer.data.payload.summary.total_amount)"></span></div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Paid: <span class="font-semibold" x-text="money(drawer.data.payload.summary.paid_amount)"></span></div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">Remaining: <span class="font-semibold" x-text="money(drawer.data.payload.summary.remaining_amount)"></span></div>
                                </div>
                            </section>

                            <section x-show="canRecord && Number(drawer.data.payload.summary.remaining_amount || 0) > 0" class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                                <h4 class="text-sm font-semibold text-slate-900">Mark Paid</h4>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</label>
                                        <input type="number" min="0.01" step="0.01" x-model="paymentForm.amount_paid" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Date</label>
                                        <input type="date" x-model="paymentForm.payment_date" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Method</label>
                                        <select x-model="paymentForm.payment_method" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                            <option value="">Select</option>
                                            <option value="cash">Cash</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="mobile_wallet">Mobile Wallet</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Reference No</label>
                                        <input type="text" x-model="paymentForm.reference_no" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                                        <textarea x-model="paymentForm.notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <button type="button" @click="submitPayment()" :disabled="markingPaid" class="inline-flex min-h-10 items-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                                            <span x-text="markingPaid ? 'Saving...' : 'Mark Paid'"></span>
                                        </button>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </template>
                </div>
            </aside>
        </div>
    </div>

    <script>
        function feeChallanGeneratorPage(config) {
            return {
                session: config.defaultSession || '',
                classId: '',
                month: config.defaultMonth || '',
                dueDate: config.defaultDueDate || '',
                search: '',
                statusFilter: '',
                perPage: 20,
                searchTimer: null,
                generating: false,
                loadingStructure: false,
                loadingTable: false,
                rows: [],
                feeHeads: [],
                feeSummary: {
                    monthly_total: 0,
                    one_time_total: 0,
                    total_heads: 0,
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
                canRecord: Boolean(config.canRecord),
                canWaive: Boolean(config.canWaive),
                drawer: {
                    open: false,
                    loading: false,
                    data: null,
                },
                markingPaid: false,
                paymentForm: {
                    amount_paid: '',
                    payment_date: '',
                    payment_method: '',
                    reference_no: '',
                    notes: '',
                },

                init() {
                    this.paymentForm.payment_date = new Date().toISOString().slice(0, 10);
                    this.loadFeeStructurePreview();
                    this.loadTable(true);
                },

                clearStatus() {
                    this.status.message = '';
                    this.status.type = 'success';
                },

                setStatus(message, type = 'success') {
                    this.status.message = message;
                    this.status.type = type;
                },

                money(value) {
                    const amount = Number(value || 0);
                    return amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                statusLabel(status) {
                    const normalized = String(status || '').toLowerCase() === 'partially_paid'
                        ? 'partial'
                        : String(status || '');
                    return normalized.replace('_', ' ').replace(/\b\w/g, (ch) => ch.toUpperCase());
                },

                statusBadgeClass(status) {
                    if (status === 'paid') {
                        return 'bg-emerald-100 text-emerald-700';
                    }
                    if (status === 'partial' || status === 'partially_paid') {
                        return 'bg-amber-100 text-amber-700';
                    }
                    return 'bg-rose-100 text-rose-700';
                },

                paginationLabel() {
                    const total = Number(this.meta.total || 0);
                    if (total === 0) {
                        return 'No challans available';
                    }
                    const from = this.meta.from ?? 1;
                    const to = this.meta.to ?? Math.min(Number(this.meta.per_page || 20), total);
                    return `Showing ${from} to ${to} of ${total} challans`;
                },

                buildQuery(page) {
                    const params = new URLSearchParams({
                        page: String(Math.max(Number(page || 1), 1)),
                        per_page: String(this.perPage || 20),
                    });
                    if (this.session) params.set('session', this.session);
                    if (this.classId) params.set('class_id', String(this.classId));
                    if (this.month) params.set('month', this.month);
                    if (this.statusFilter) params.set('status', this.statusFilter);
                    if (this.search.trim() !== '') params.set('search', this.search.trim());
                    return params;
                },

                onTopFiltersChange() {
                    this.loadFeeStructurePreview();
                    this.loadTable(true);
                },

                onSearchInput() {
                    if (this.searchTimer) {
                        window.clearTimeout(this.searchTimer);
                    }
                    this.searchTimer = window.setTimeout(() => this.loadTable(true), 300);
                },

                async loadFeeStructurePreview() {
                    this.feeHeads = [];
                    this.feeSummary = {
                        monthly_total: 0,
                        one_time_total: 0,
                        total_heads: 0,
                    };

                    if (!this.session || !this.classId) {
                        return;
                    }

                    this.loadingStructure = true;
                    try {
                        const params = new URLSearchParams({
                            session: this.session,
                            class_id: String(this.classId),
                        });
                        const response = await fetch(`${config.feeStructureUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to load fee structure preview.', 'error');
                            return;
                        }

                        this.feeHeads = result.heads || [];
                        this.feeSummary = result.summary || this.feeSummary;
                    } catch (error) {
                        this.setStatus('Unexpected error while loading fee structure preview.', 'error');
                    } finally {
                        this.loadingStructure = false;
                    }
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
                            this.setStatus(result.message || 'Failed to load challans.', 'error');
                            this.rows = [];
                            return;
                        }

                        this.rows = result.rows || [];
                        this.meta = {
                            current_page: Number(result.meta?.current_page || 1),
                            last_page: Number(result.meta?.last_page || 1),
                            per_page: Number(result.meta?.per_page || this.perPage),
                            total: Number(result.meta?.total || 0),
                            from: result.meta?.from ?? null,
                            to: result.meta?.to ?? null,
                        };
                    } catch (error) {
                        this.setStatus('Unexpected error while loading challans.', 'error');
                        this.rows = [];
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

                async generateChallans() {
                    this.clearStatus();
                    if (!this.canGenerate) {
                        this.setStatus('You do not have permission to generate challans.', 'error');
                        return;
                    }
                    if (!this.session || !this.classId || !this.month || !this.dueDate) {
                        this.setStatus('Session, class, month, and due date are required.', 'error');
                        return;
                    }

                    this.generating = true;
                    try {
                        const response = await fetch(config.generateUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                session: this.session,
                                class_id: Number(this.classId),
                                month: this.month,
                                due_date: this.dueDate,
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to generate challans.', 'error');
                            return;
                        }

                        this.setStatus(result.message || 'Challans generated successfully.');
                        await this.loadTable(true);
                    } catch (error) {
                        this.setStatus('Unexpected error while generating challans.', 'error');
                    } finally {
                        this.generating = false;
                    }
                },

                challanUrl(template, id) {
                    return String(template).replace('__ID__', String(id));
                },

                downloadPdf(id) {
                    window.open(this.challanUrl(config.challanPdfUrlTemplate, id), '_blank');
                },

                printChallan(id) {
                    const windowRef = window.open(this.challanUrl(config.challanPdfUrlTemplate, id), '_blank');
                    if (windowRef) {
                        windowRef.focus();
                    }
                },

                async waiveLateFee(challanId) {
                    if (!this.canWaive) {
                        this.setStatus('You do not have permission to waive late fee.', 'error');
                        return;
                    }

                    this.clearStatus();
                    try {
                        const response = await fetch(this.challanUrl(config.waiveLateFeeUrlTemplate, challanId), {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to waive late fee.', 'error');
                            return;
                        }

                        this.setStatus(result.message || 'Late fee waived successfully.');
                        await this.loadTable(false, this.meta.current_page);
                        if (this.drawer.open && Number(this.drawer.data?.challan_id || 0) === Number(challanId)) {
                            await this.openDrawer(challanId);
                        }
                    } catch (error) {
                        this.setStatus('Unexpected error while waiving late fee.', 'error');
                    }
                },

                async openDrawer(challanId) {
                    this.drawer.open = true;
                    this.drawer.loading = true;
                    this.drawer.data = null;

                    try {
                        const response = await fetch(this.challanUrl(config.challanDetailUrlTemplate, challanId), {
                            headers: { Accept: 'application/json' },
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to load challan.', 'error');
                            this.drawer.open = false;
                            return;
                        }

                        this.drawer.data = result;
                        this.paymentForm.amount_paid = String(result.payload?.summary?.remaining_amount ?? '');
                        this.paymentForm.payment_date = new Date().toISOString().slice(0, 10);
                        this.paymentForm.payment_method = '';
                        this.paymentForm.reference_no = '';
                        this.paymentForm.notes = '';
                    } catch (error) {
                        this.setStatus('Unexpected error while loading challan.', 'error');
                        this.drawer.open = false;
                    } finally {
                        this.drawer.loading = false;
                    }
                },

                closeDrawer() {
                    this.drawer.open = false;
                    this.drawer.loading = false;
                    this.drawer.data = null;
                },

                async submitPayment() {
                    if (!this.canRecord || !this.drawer.data) {
                        return;
                    }

                    this.markingPaid = true;
                    this.clearStatus();
                    try {
                        const challanId = Number(this.drawer.data.challan_id);
                        const response = await fetch(this.challanUrl(config.markPaidUrlTemplate, challanId), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                challan_id: challanId,
                                amount_paid: Number(this.paymentForm.amount_paid),
                                payment_date: this.paymentForm.payment_date,
                                payment_method: this.paymentForm.payment_method || null,
                                reference_no: this.paymentForm.reference_no || null,
                                notes: this.paymentForm.notes || null,
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to save payment.', 'error');
                            return;
                        }

                        this.setStatus(result.message || 'Payment recorded.');
                        await this.loadTable(false, this.meta.current_page);
                        await this.openDrawer(challanId);
                    } catch (error) {
                        this.setStatus('Unexpected error while saving payment.', 'error');
                    } finally {
                        this.markingPaid = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
