<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Daily Diary</h2>
                <p class="mt-1 text-sm text-slate-500">Read-only access to all teacher diary entries across classes and grades.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Filters</h3>
                <form method="GET" action="{{ route('warden.daily-diary.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-6">
                    <div>
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected(($filters['session'] ?? null) === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                        <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
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

                    <div>
                        <label for="is_published" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="is_published" name="is_published" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All</option>
                            <option value="1" @selected(($filters['is_published'] ?? null) === true || (string) ($filters['is_published'] ?? '') === '1')>Published</option>
                            <option value="0" @selected(($filters['is_published'] ?? null) === false || (string) ($filters['is_published'] ?? '') === '0')>Draft</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Apply
                        </button>
                        <a href="{{ route('warden.daily-diary.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Homework Preview</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Attachment</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($entries as $entry)
                                @php
                                    $attachmentPath = $entry->attachment_path ?: data_get($entry->attachments->first(), 'file_path');
                                    $attachmentName = $entry->attachment_name
                                        ?: data_get($entry->attachments->first(), 'file_name')
                                        ?: ($attachmentPath ? basename((string) $attachmentPath) : null);
                                @endphp
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ optional($entry->diary_date)->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ trim(($entry->classRoom?->name ?? '').' '.($entry->classRoom?->section ?? '')) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $entry->subject?->name }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $entry->teacher?->user?->name ?? 'Teacher' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-900 font-semibold">{{ $entry->title ?: 'Untitled Diary Entry' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit((string) $entry->homework_text, 110) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        @if ($attachmentPath)
                                            <a
                                                href="{{ route('daily-diary.attachment', $entry) }}"
                                                class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-200"
                                            >
                                                {{ $attachmentName }}
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm">
                                        <a
                                            href="{{ route('warden.daily-diary.show', $entry) }}"
                                            class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No diary entries found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($entries->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $entries->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
