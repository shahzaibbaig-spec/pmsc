<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Warden Daily Reports</h2>
                <p class="mt-1 text-sm text-slate-500">Track nightly attendance, discipline, and health updates by day.</p>
            </div>
            <a href="{{ route('warden.daily-reports.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Create Today's Report
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('warden.daily-reports.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Report Date</label>
                        <input id="date" type="date" name="date" value="{{ $date ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="per_page" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Per Page</label>
                        <select id="per_page" name="per_page" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ([10, 15, 25, 50] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Filter</button>
                        <a href="{{ route('warden.daily-reports.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Present</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Absent</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Discipline</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Health</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created By</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($reports as $report)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">{{ optional($report->report_date)->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ (int) ($report->present_count ?? 0) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ (int) ($report->absent_count ?? 0) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ (int) ($report->discipline_count ?? 0) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ (int) ($report->health_count ?? 0) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $report->createdBy?->name ?? '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        <a href="{{ route('warden.daily-reports.show', $report) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No daily reports found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $reports->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

