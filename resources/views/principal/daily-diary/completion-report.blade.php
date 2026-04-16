<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Daily Diary Completion Report</h2>
                <p class="mt-1 text-sm text-slate-500">Completion dashboard for teacher diary posting compliance.</p>
            </div>
            <a
                href="{{ route('principal.daily-diary.index', ['session' => $filters['session'], 'date' => $filters['date']]) }}"
                class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to Monitoring
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('principal.daily-diary.completion-report') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                        <input id="date" type="date" name="date" value="{{ $filters['date'] }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Apply
                        </button>
                        <a href="{{ route('principal.daily-diary.completion-report') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Expected Postings</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) ($report['total_expected_postings'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Posted</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format((int) ($report['total_posted'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Missing Postings</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-700">{{ number_format((int) ($report['missing_postings'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completion Percentage</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($report['completion_percentage'] ?? 0), 2) }}%</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900">Missing Diary Postings</h3>
                    <p class="mt-1 text-xs text-slate-500">Rows where a diary entry was expected but not posted.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Session</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($missingRows as $row)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $row['teacher_name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $row['teacher_code'] ?: '-' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row['class_name'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row['subject_name'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ \Illuminate\Support\Carbon::parse($row['diary_date'])->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row['session'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                        No missing diary postings for the selected date and session.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

