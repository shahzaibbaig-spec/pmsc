<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Salary Slip
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                    <form method="GET" action="{{ route('principal.payroll.slips.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <x-input-label for="month" value="Month" />
                            <select id="month" name="month" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                @foreach($monthOptions as $month)
                                    <option value="{{ $month }}" @selected($filters['month'] === $month)>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="search" value="Employee" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['search'] }}" placeholder="Name or email" />
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                                Apply
                            </button>
                            <a href="{{ route('principal.payroll.slips.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Month</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Basic</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Allowances</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Deductions</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Net Salary</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        <div class="font-medium">{{ $item->user?->name ?? 'Employee' }}</div>
                                        <div class="text-xs text-gray-500">{{ $item->user?->email ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $item->payrollRun ? \Carbon\Carbon::createFromFormat('Y-m', $item->payrollRun->month)->format('F Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $item->basic_salary, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $item->allowances_total, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $item->deductions_total, 2) }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ number_format((float) $item->net_salary, 2) }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('principal.payroll.slips.pdf', $item) }}" target="_blank" class="inline-flex min-h-10 items-center rounded-md border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                            Print Slip
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No salary slips found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
