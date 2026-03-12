<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Payroll Reports
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                    <form method="GET" action="{{ route('principal.payroll.reports.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <x-input-label for="month_from" value="From Month" />
                            <select id="month_from" name="month_from" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Any</option>
                                @foreach($monthOptions as $month)
                                    <option value="{{ $month }}" @selected($filters['month_from'] === $month)>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="month_to" value="To Month" />
                            <select id="month_to" name="month_to" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Any</option>
                                @foreach($monthOptions as $month)
                                    <option value="{{ $month }}" @selected($filters['month_to'] === $month)>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-2 md:col-span-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Apply</button>
                            <a href="{{ route('principal.payroll.reports.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.card title="Runs"><p class="text-3xl font-semibold text-slate-900">{{ $summary['runs'] }}</p></x-ui.card>
                <x-ui.card title="Employees Processed"><p class="text-3xl font-semibold text-slate-900">{{ $summary['employees_processed'] }}</p></x-ui.card>
                <x-ui.card title="Basic Total"><p class="text-3xl font-semibold text-slate-900">{{ number_format((float) $summary['basic_total'], 2) }}</p></x-ui.card>
                <x-ui.card title="Allowances Total"><p class="text-3xl font-semibold text-emerald-700">{{ number_format((float) $summary['allowances_total'], 2) }}</p></x-ui.card>
                <x-ui.card title="Deductions Total"><p class="text-3xl font-semibold text-rose-700">{{ number_format((float) $summary['deductions_total'], 2) }}</p></x-ui.card>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Monthly Breakdown</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Month</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Run Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Employees</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Basic</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Allowances</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Deductions</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $row['month_label'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['run_date'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['employees'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $row['basic_total'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-emerald-700">{{ number_format((float) $row['allowances_total'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-rose-700">{{ number_format((float) $row['deductions_total'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ number_format((float) $row['net_total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No payroll report data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
