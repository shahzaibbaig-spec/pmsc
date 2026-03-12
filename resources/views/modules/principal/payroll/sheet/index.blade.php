<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Salary Sheet
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <form method="GET" action="{{ route('principal.payroll.sheet.index') }}" class="flex flex-wrap items-end gap-3">
                        <div>
                            <x-input-label for="month" value="Month" />
                            <select id="month" name="month" class="mt-1 block min-h-11 w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Latest run</option>
                                @foreach($monthOptions as $month)
                                    <option value="{{ $month }}" @selected($selectedMonth === $month)>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                            Load Sheet
                        </button>
                    </form>
                </div>
            </div>

            @if ($run)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <x-ui.card title="Month">
                        <p class="text-lg font-semibold text-slate-900">{{ $monthLabel }}</p>
                    </x-ui.card>
                    <x-ui.card title="Employees">
                        <p class="text-2xl font-semibold text-slate-900">{{ $summary['employees'] }}</p>
                    </x-ui.card>
                    <x-ui.card title="Basic Total">
                        <p class="text-2xl font-semibold text-slate-900">{{ number_format((float) $summary['basic_total'], 2) }}</p>
                    </x-ui.card>
                    <x-ui.card title="Allowances Total">
                        <p class="text-2xl font-semibold text-emerald-700">{{ number_format((float) $summary['allowances_total'], 2) }}</p>
                    </x-ui.card>
                    <x-ui.card title="Deductions Total">
                        <p class="text-2xl font-semibold text-rose-700">{{ number_format((float) $summary['deductions_total'], 2) }}</p>
                    </x-ui.card>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-[980px] divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Employee</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Basic Salary</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Allowances</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Deductions</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Net Salary</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($items as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            <div class="font-medium">{{ $item->user?->name ?? 'Employee' }}</div>
                                            <div class="text-xs text-gray-500">{{ $item->user?->email ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $item->basic_salary, 2) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $item->allowances_total, 2) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $item->deductions_total, 2) }}</td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ number_format((float) $item->net_salary, 2) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $item->status)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Total</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">{{ number_format((float) $summary['basic_total'], 2) }}</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">{{ number_format((float) $summary['allowances_total'], 2) }}</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">{{ number_format((float) $summary['deductions_total'], 2) }}</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">{{ number_format((float) $summary['net_total'], 2) }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-600 shadow-sm">
                    No payroll run found. Generate monthly payroll first.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
