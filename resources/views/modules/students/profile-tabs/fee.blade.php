<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Billed</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">Rs. {{ number_format((float) ($feeStats['total_billed'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Paid</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">Rs. {{ number_format((float) ($feeStats['total_paid'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Installments Due</p>
            <p class="mt-2 text-2xl font-semibold text-indigo-700">Rs. {{ number_format((float) ($feeStats['installment_due'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Manual Arrears Due</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">Rs. {{ number_format((float) ($feeStats['arrears_due'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Due</p>
            <p class="mt-2 text-2xl font-semibold text-rose-700">Rs. {{ number_format((float) ($feeStats['pending_amount'] ?? 0), 2) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Installment Schedule</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Plan</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Installment</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Due Date</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Amount</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Paid</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Remaining</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse(($installmentSchedule ?? collect()) as $installment)
                        @php
                            $status = strtolower((string) ($installment['status'] ?? 'pending'));
                            $statusClass = match ($status) {
                                'paid' => 'bg-emerald-100 text-emerald-700',
                                'partial' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">
                                <div>{{ strtoupper((string) ($installment['plan_type'] ?? 'plan')) }}</div>
                                <div class="text-xs text-slate-500">{{ $installment['plan_session'] ?: '-' }}</div>
                            </td>
                            <td class="px-3 py-2 text-sm text-slate-700">
                                <div>{{ $installment['title'] ?: 'Installment '.$installment['installment_no'] }}</div>
                                <div class="text-xs text-slate-500">#{{ (int) $installment['installment_no'] }}</div>
                            </td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($installment['due_date'])->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">Rs. {{ number_format((float) $installment['amount'], 2) }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">Rs. {{ number_format((float) $installment['paid_amount'], 2) }}</td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">Rs. {{ number_format((float) $installment['remaining_amount'], 2) }}</td>
                            <td class="px-3 py-2 text-sm">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500">No installment schedule found for this student.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Manual Arrears</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Title</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Session</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Due Date</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Amount</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Paid</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Remaining</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse(($manualArrears ?? collect()) as $arrear)
                        @php
                            $status = strtolower((string) ($arrear['status'] ?? 'pending'));
                            $statusClass = match ($status) {
                                'paid' => 'bg-emerald-100 text-emerald-700',
                                'partial' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $arrear['title'] ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $arrear['session'] ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($arrear['due_date'])->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">Rs. {{ number_format((float) $arrear['amount'], 2) }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">Rs. {{ number_format((float) $arrear['paid_amount'], 2) }}</td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">Rs. {{ number_format((float) $arrear['remaining_amount'], 2) }}</td>
                            <td class="px-3 py-2 text-sm">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500">No manual arrears found for this student.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Fee Challans</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Challan #</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Month</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Due Date</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Total</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Paid</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Due</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($challans as $challan)
                        @php
                            $status = strtolower((string) ($challan['status'] ?? 'pending'));
                            $statusClass = match ($status) {
                                'paid' => 'bg-emerald-100 text-emerald-700',
                                'partial', 'partially_paid' => 'bg-amber-100 text-amber-700',
                                'overdue' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $challan['challan_number'] ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $challan['month'] ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($challan['due_date'])->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">Rs. {{ number_format((float) $challan['total_amount'], 2) }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">Rs. {{ number_format((float) $challan['paid_amount'], 2) }}</td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">Rs. {{ number_format((float) $challan['due_amount'], 2) }}</td>
                            <td class="px-3 py-2 text-sm">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst((string) ($challan['status'] ?? 'Pending')) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm">
                                @if((auth()->user()?->hasAnyRole(['Admin', 'Accountant']) ?? false) && (auth()->user()?->can('view_fee_challans') ?? false))
                                    <a
                                        href="{{ route('principal.fees.challans.pdf', $challan['id']) }}"
                                        target="_blank"
                                        class="inline-flex min-h-8 items-center rounded-md border border-indigo-300 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                                    >
                                        View PDF
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">No access</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-500">No fee challans found for this student.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
