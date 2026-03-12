<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Accountant Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Finance and payroll overview for {{ $currentMonthLabel }}.</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card title="Total Students">
            <p class="text-3xl font-semibold text-slate-900">{{ (int) ($stats['total_students'] ?? 0) }}</p>
        </x-ui.card>

        <x-ui.card title="Pending Fees">
            <p class="text-3xl font-semibold text-amber-700">PKR {{ number_format((float) ($stats['pending_fees'] ?? 0), 2) }}</p>
        </x-ui.card>

        <x-ui.card title="Monthly Payroll">
            <p class="text-3xl font-semibold text-indigo-700">PKR {{ number_format((float) ($stats['monthly_payroll'] ?? 0), 2) }}</p>
        </x-ui.card>

        <x-ui.card title="Recent Payments">
            <p class="text-3xl font-semibold text-emerald-700">{{ (int) ($stats['recent_payments'] ?? 0) }}</p>
            <p class="mt-1 text-xs text-slate-500">Payments recorded this month</p>
        </x-ui.card>
    </div>

    <x-ui.card class="mt-6" title="Finance Shortcuts">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.button href="{{ route('principal.fees.structures.index') }}" variant="outline">Fee Structure</x-ui.button>
            <x-ui.button href="{{ route('principal.fees.challans.generate') }}" variant="outline">Generate Challans</x-ui.button>
            <x-ui.button href="{{ route('principal.fees.payments.index') }}" variant="outline">Record Fee Payments</x-ui.button>
            <x-ui.button href="{{ route('principal.fees.reports.index') }}" variant="outline">Fee Reports</x-ui.button>
            <x-ui.button href="{{ route('principal.payroll.profiles.index') }}" variant="outline">Payroll Profiles</x-ui.button>
            <x-ui.button href="{{ route('principal.payroll.generate.index') }}" variant="outline">Generate Payroll</x-ui.button>
            <x-ui.button href="{{ route('principal.payroll.slips.index') }}" variant="outline">Salary Slips</x-ui.button>
            <x-ui.button href="{{ route('principal.payroll.reports.index') }}" variant="outline">Payroll Reports</x-ui.button>
        </div>
    </x-ui.card>

    <x-ui.card class="mt-6" title="Recent Fee Payments">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Method</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($recentPayments as $payment)
                        <tr>
                            <td class="px-4 py-2 text-sm text-slate-700">{{ optional($payment->payment_date)->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-slate-700">
                                {{ $payment->challan?->student?->name ?? 'Student' }}
                                <span class="text-xs text-slate-500">({{ $payment->challan?->student?->student_id ?? '-' }})</span>
                            </td>
                            <td class="px-4 py-2 text-sm font-semibold text-slate-900">PKR {{ number_format((float) $payment->amount_paid, 2) }}</td>
                            <td class="px-4 py-2 text-sm text-slate-700">{{ $payment->payment_method ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-slate-700">{{ $payment->reference_no ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No recent payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-app-layout>

