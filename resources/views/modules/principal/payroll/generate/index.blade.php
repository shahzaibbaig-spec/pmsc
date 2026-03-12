<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Generate Monthly Payroll
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Run Payroll</h3>
                    <p class="mt-1 text-sm text-gray-600">Generate salary sheet month-wise for all active payroll profiles.</p>

                    <form method="POST" action="{{ route('principal.payroll.generate.run') }}" class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <x-input-label for="month" value="Month" />
                            <select id="month" name="month" class="mt-1 block min-h-11 w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                @foreach($monthOptions as $month)
                                    <option value="{{ $month }}" @selected(old('month', $defaultMonth) === $month)>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Generate Payroll
                        </button>
                    </form>
                </div>
            </div>

            @if ($latestSummary)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-ui.card title="Profiles Processed">
                        <p class="text-3xl font-semibold text-slate-900">{{ $latestSummary['profiles_processed'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $latestSummary['month_label'] }}</p>
                    </x-ui.card>
                    <x-ui.card title="Total Gross">
                        <p class="text-3xl font-semibold text-slate-900">{{ number_format((float) $latestSummary['total_gross'], 2) }}</p>
                    </x-ui.card>
                    <x-ui.card title="Total Net">
                        <p class="text-3xl font-semibold text-emerald-700">{{ number_format((float) $latestSummary['total_net'], 2) }}</p>
                    </x-ui.card>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Recent Payroll Runs</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[760px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Month</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Run Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Employees</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Net Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($recentRuns as $run)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ \Carbon\Carbon::createFromFormat('Y-m', $run->month)->format('F Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ optional($run->run_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $run->items_count }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) ($run->net_total ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $run->status)) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No payroll runs generated yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
