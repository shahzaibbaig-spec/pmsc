<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Fee Arrears Report</h2>
            <p class="mt-1 text-sm text-slate-500">Track student-wise outstanding fee balances and overdue challans.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900">Filters</h3>
                <a href="{{ route('principal.fees.reports.index') }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Back To Fee Reports
                </a>
            </div>

            <form method="GET" action="{{ route('principal.fees.reports.arrears') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <x-input-label for="session" value="Session" />
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Sessions</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="class_id" value="Class" />
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Classes</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="search" value="Student" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['search'] }}" placeholder="Name or student ID" />
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                        Apply
                    </button>
                    <a href="{{ route('principal.fees.reports.arrears') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.card title="Students With Arrears">
                <p class="text-3xl font-semibold text-slate-900">{{ (int) ($summary['students_with_arrears'] ?? 0) }}</p>
            </x-ui.card>
            <x-ui.card title="Total Arrears">
                <p class="text-3xl font-semibold text-rose-700">{{ number_format((float) ($summary['total_arrears'] ?? 0), 2) }}</p>
            </x-ui.card>
            <x-ui.card title="Unpaid Challans">
                <p class="text-3xl font-semibold text-amber-700">{{ (int) ($summary['total_unpaid_challans'] ?? 0) }}</p>
            </x-ui.card>
            <x-ui.card title="Overdue Challans">
                <p class="text-3xl font-semibold text-indigo-700">{{ (int) ($summary['total_overdue_challans'] ?? 0) }}</p>
            </x-ui.card>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[980px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Unpaid</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Overdue</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Earliest Due</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Latest Due</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total Arrears</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-800">
                                    <p class="font-medium">{{ $row['student_name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $row['student_code'] }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['class_name'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $row['unpaid_challans'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $row['overdue_challans'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['earliest_due_date'] ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['latest_due_date'] ?: '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-rose-700">{{ number_format((float) $row['total_arrears'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No arrears found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>

