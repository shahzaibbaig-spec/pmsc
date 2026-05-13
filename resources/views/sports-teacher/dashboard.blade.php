<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Sports Teacher Dashboard</h2>
                <p class="mt-1 text-sm text-slate-500">Monitor hygiene and discipline observations for sports classes.</p>
            </div>
            <a href="{{ route('sports-teacher.observations.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                New Observation
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('sports-teacher.dashboard') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected(($filters['session'] ?? null) === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                    <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Refresh</button>
                    <a href="{{ route('sports-teacher.dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Today's Observations</p>
                <p class="mt-2 text-2xl font-semibold text-blue-800">{{ number_format((int) ($cards['today_observations'] ?? 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Open Observations</p>
                <p class="mt-2 text-2xl font-semibold text-amber-800">{{ number_format((int) ($cards['open_observations'] ?? 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Repeated Issues</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-800">{{ number_format((int) ($cards['repeated_issues'] ?? 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Resolved</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ number_format((int) ($cards['resolved_observations'] ?? 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Most Common Issue Today</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $cards['most_common_issue_today'] ?? 'N/A' }}</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h3 class="text-base font-semibold text-slate-900">Recent Observations</h3>
                <a href="{{ route('sports-teacher.observations.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Issues</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($observations as $observation)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-900">
                                    <p class="font-semibold">{{ $observation->student?->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $observation->student?->student_id }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ trim(($observation->classRoom?->name ?? '').' '.($observation->classRoom?->section ?? '')) ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $observation->resolvedIssueLabelText() }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $observation->status === 'resolved' ? 'bg-emerald-100 text-emerald-700' : ($observation->status === 'acknowledged' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ucfirst($observation->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <a href="{{ route('sports-teacher.observations.show', $observation) }}" class="font-semibold text-blue-700 hover:text-blue-800">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No observations recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
