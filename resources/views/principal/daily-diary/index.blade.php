<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Daily Diary Monitoring</h2>
                <p class="mt-1 text-sm text-slate-500">Track expected teacher postings and verify submitted diary entries.</p>
            </div>
            <a
                href="{{ route('principal.daily-diary.completion-report', ['session' => $filters['session'], 'date' => $filters['date']]) }}"
                class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                View Completion Report
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Filters</h3>
                <form method="GET" action="{{ route('principal.daily-diary.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
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

                    <div>
                        <label for="teacher_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</label>
                        <select id="teacher_id" name="teacher_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All teachers</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher['id'] }}" @selected((int) ($filters['teacher_id'] ?? 0) === (int) $teacher['id'])>
                                    {{ $teacher['name'] }}{{ $teacher['teacher_code'] ? ' ('.$teacher['teacher_code'].')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>{{ $class['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="subject_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                        <select id="subject_id" name="subject_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All subjects</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject['id'] }}" @selected((int) ($filters['subject_id'] ?? 0) === (int) $subject['id'])>{{ $subject['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Apply
                        </button>
                        <a href="{{ route('principal.daily-diary.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Expected Postings</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) ($stats['total_expected_postings'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Posted</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format((int) ($stats['total_posted'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Missing Postings</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-700">{{ number_format((int) ($stats['missing_postings'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completion</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($stats['completion_percentage'] ?? 0), 2) }}%</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Posted</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Entry Preview</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $row['teacher_name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $row['teacher_code'] ?: '-' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row['class_name'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row['subject_name'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ \Illuminate\Support\Carbon::parse($row['diary_date'])->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        @if ($row['posted'])
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Posted</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Missing</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        @if ($row['posted'])
                                            <p class="font-semibold text-slate-900">{{ $row['title'] ?: 'Untitled Diary Entry' }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $row['homework_preview'] ?: '-' }}</p>
                                        @else
                                            <span class="text-xs text-slate-500">No diary entry posted for this scope.</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        {{ $row['updated_at'] ? $row['updated_at']->format('d M Y, h:i A') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                                        No teacher assignment rows found for the selected filters.
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

