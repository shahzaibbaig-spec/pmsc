<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Daily Diary</h2>
                <p class="mt-1 text-sm text-slate-500">Homework shared by your teachers for your class and subjects.</p>
            </div>
            @if ($student)
                <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    Session: {{ $filters['session'] ?? '-' }}
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if ($message)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $message }}
                </div>
            @else
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Filters</h3>
                    <form method="GET" action="{{ route('student.daily-diary.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                            <select
                                id="session"
                                name="session"
                                class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                @foreach ($sessions as $session)
                                    <option value="{{ $session }}" @selected(($filters['session'] ?? null) === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="diary_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Specific Date</label>
                            <input
                                id="diary_date"
                                type="date"
                                name="diary_date"
                                value="{{ $filters['diary_date'] ?? '' }}"
                                class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>

                        <div>
                            <label for="date_from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date From</label>
                            <input
                                id="date_from"
                                type="date"
                                name="date_from"
                                value="{{ $filters['date_from'] ?? '' }}"
                                class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>

                        <div>
                            <label for="date_to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date To</label>
                            <input
                                id="date_to"
                                type="date"
                                name="date_to"
                                value="{{ $filters['date_to'] ?? '' }}"
                                class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>

                        <div class="flex items-end gap-2 md:col-span-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Apply
                            </button>
                            <a href="{{ route('student.daily-diary.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </section>

                @php
                    $sections = [
                        ['title' => 'Today', 'entries' => $todayEntries, 'empty' => 'No diary entries for today.'],
                        ['title' => 'This Week', 'entries' => $weekEntries, 'empty' => 'No additional diary entries in this week.'],
                        ['title' => 'History', 'entries' => $historyEntries, 'empty' => 'No older diary entries found.'],
                    ];
                @endphp

                @foreach ($sections as $section)
                    <section class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $section['title'] }}</h3>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {{ count($section['entries']) }} entries
                            </span>
                        </div>

                        @forelse ($section['entries'] as $entry)
                            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            {{ optional($entry->diary_date)->format('d M Y') }} | {{ $entry->subject?->name ?? 'Subject' }}
                                        </p>
                                        <h4 class="mt-1 text-base font-semibold text-slate-900">{{ $entry->title ?: 'Untitled Diary Entry' }}</h4>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                        {{ $entry->teacher?->user?->name ?? 'Teacher' }}
                                    </span>
                                </div>

                                <div class="mt-4 rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Homework</p>
                                    <p class="mt-2 whitespace-pre-line text-sm text-slate-800">{{ $entry->homework_text }}</p>
                                </div>

                                @if ($entry->instructions)
                                    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Instructions</p>
                                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $entry->instructions }}</p>
                                    </div>
                                @endif

                                @php
                                    $entryAttachmentPath = $entry->attachment_path ?: data_get($entry->attachments->first(), 'file_path');
                                    $entryAttachmentName = $entry->attachment_name
                                        ?: data_get($entry->attachments->first(), 'file_name')
                                        ?: ($entryAttachmentPath ? basename((string) $entryAttachmentPath) : null);
                                @endphp

                                @if ($entryAttachmentPath)
                                    <div class="mt-3 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Attachment</p>
                                        <a
                                            href="{{ route('daily-diary.attachment', $entry) }}"
                                            class="mt-2 inline-flex items-center text-sm font-semibold text-indigo-700 hover:text-indigo-600"
                                        >
                                            {{ $entryAttachmentName }}
                                        </a>
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                {{ $section['empty'] }}
                            </div>
                        @endforelse
                    </section>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
