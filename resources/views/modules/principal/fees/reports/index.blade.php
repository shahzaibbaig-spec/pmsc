<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Fee Reports
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                        <a href="{{ route('principal.fees.reports.arrears') }}" class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            View Arrears Report
                        </a>
                    </div>
                    <form method="GET" action="{{ route('principal.fees.reports.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sessions</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Classes</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                        {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="month" value="Month" />
                            <x-text-input id="month" name="month" type="month" class="mt-1 block min-h-11 w-full" value="{{ $filters['month'] }}" />
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                                Apply
                            </button>
                            <a href="{{ route('principal.fees.reports.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <x-ui.card title="Total Challans">
                    <p class="text-3xl font-semibold text-slate-900">{{ $summary['total_challans'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">Paid: {{ $summary['paid_challans'] }} | Pending: {{ $summary['pending_challans'] }}</p>
                </x-ui.card>
                <x-ui.card title="Total Billed">
                    <p class="text-3xl font-semibold text-slate-900">{{ number_format((float) $summary['total_billed'], 2) }}</p>
                </x-ui.card>
                <x-ui.card title="Total Collected">
                    <p class="text-3xl font-semibold text-emerald-700">{{ number_format((float) $summary['total_collected'], 2) }}</p>
                    <p class="mt-1 text-xs text-rose-600">Pending: {{ number_format((float) $summary['total_pending'], 2) }}</p>
                </x-ui.card>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Class/Month Breakdown</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[920px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Session</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Month</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Challans</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Billed</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Collected</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Pending</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($breakdown as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $row['class_name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $row['session'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $row['month_label'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $row['challans_count'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ number_format((float) $row['billed_amount'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-emerald-700">{{ number_format((float) $row['collected_amount'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-rose-700">{{ number_format((float) $row['pending_amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No fee report data found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
