<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Teacher Result Entries Detail</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $teacher->user?->name ?? 'Teacher' }} ({{ $teacher->teacher_id }})</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('principal.results.teacher-entries.logs', ['teacher' => $teacher->id] + request()->query()) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    View Logs
                </a>
                <a href="{{ route('principal.results.teacher-entries.index', request()->query()) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('principal.results.teacher-entries.show', $teacher) }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>{{ $class['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                        <select name="subject_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All subjects</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject['id'] }}" @selected((int) ($filters['subject_id'] ?? 0) === (int) $subject['id'])>{{ $subject['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Type</label>
                        <select name="exam_type" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All exam types</option>
                            @foreach ($examTypes as $examType)
                                <option value="{{ $examType['value'] }}" @selected(($filters['exam_type'] ?? null) === $examType['value'])>{{ $examType['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date From</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date To</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Apply
                        </button>
                        <a href="{{ route('principal.results.teacher-entries.show', $teacher) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            @if ($completion)
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completion Status ({{ $completion['exam_type_label'] }})</p>
                    <p class="mt-2 text-sm text-slate-700">
                        Entered: <strong>{{ $completion['entered_student_count'] }}</strong> /
                        Eligible: <strong>{{ $completion['total_eligible_student_count'] }}</strong> |
                        Pending: <strong>{{ $completion['pending_entries'] }}</strong> |
                        Completion: <strong>{{ number_format((float) $completion['completion_percentage'], 2) }}%</strong>
                    </p>
                </section>
            @endif

            @forelse ($groups as $group)
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ $group['class_name'] }} | {{ $group['subject_name'] }} | {{ $group['exam_type_label'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $group['entry_count'] }} entry record(s)</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marks / Grade</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created At</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Updated At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($group['entries'] as $entry)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-slate-900">
                                            <p class="font-semibold">{{ $entry['student_name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $entry['student_code'] }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $entry['class_name'] }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $entry['subject_name'] }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $entry['exam_type_label'] }}</td>
                                        <td class="px-4 py-4 text-sm font-semibold text-slate-900">
                                            {{ $entry['grade'] !== null ? $entry['grade'] : number_format((float) ($entry['obtained_marks'] ?? 0), 2) }}
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $entry['created_at'] ? $entry['created_at']->format('d M Y, h:i A') : '-' }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $entry['updated_at'] ? $entry['updated_at']->format('d M Y, h:i A') : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @empty
                <section class="rounded-2xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500 shadow-sm">
                    No result entries found for this teacher and filter scope.
                </section>
            @endforelse
        </div>
    </div>
</x-app-layout>
