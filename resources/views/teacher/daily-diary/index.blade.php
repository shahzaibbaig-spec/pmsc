<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Daily Diary</h2>
                <p class="mt-1 text-sm text-slate-500">Manage daily homework entries for your assigned classes and subjects.</p>
            </div>
            @can('create_daily_diary')
                <a
                    href="{{ route('teacher.daily-diary.create', ['session' => $filters['session'] ?? null]) }}"
                    class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Create Entry
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Filters</h3>
                <form method="GET" action="{{ route('teacher.daily-diary.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected(($filters['session'] ?? null) === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>
                                    {{ $class['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="subject_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                        <select id="subject_id" name="subject_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All subjects</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject['id'] }}" @selected((int) ($filters['subject_id'] ?? 0) === (int) $subject['id'])>
                                    {{ $subject['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="diary_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                        <input
                            id="diary_date"
                            type="date"
                            name="diary_date"
                            value="{{ $filters['diary_date'] ?? '' }}"
                            class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                    </div>

                    <div>
                        <label for="is_published" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="is_published" name="is_published" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All</option>
                            <option value="1" @selected(($filters['is_published'] ?? null) === true)>Published</option>
                            <option value="0" @selected(($filters['is_published'] ?? null) === false)>Draft</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Apply
                        </button>
                        <a href="{{ route('teacher.daily-diary.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($entries as $entry)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ optional($entry->diary_date)->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ trim(($entry->classRoom?->name ?? '').' '.($entry->classRoom?->section ?? '')) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $entry->subject?->name ?? '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $entry->title ?: 'Untitled Diary Entry' }}</p>
                                        <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ \Illuminate\Support\Str::limit($entry->homework_text, 95) }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm">
                                        @if ($entry->is_published)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Published</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Draft</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ optional($entry->updated_at)->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-4 text-right">
                                        @can('edit_own_daily_diary')
                                            <a
                                                href="{{ route('teacher.daily-diary.edit', $entry) }}"
                                                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                Edit
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                                        No diary entries found for the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $entries->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

