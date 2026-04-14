<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Teacher Assignment Session Rollover</h2>
            <a
                href="{{ route('principal.teacher-assignments.index') }}"
                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Back to Assignments
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Rollover Setup</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Assignments will be copied to the next session. Existing old-session assignments will remain unchanged.
                </p>
                <p class="mt-1 text-sm text-slate-600">
                    After copying, you may modify the new session assignments teacher by teacher.
                </p>

                <form method="POST" action="{{ route('principal.teacher-assignments.rollover.preview') }}" class="mt-5 space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="from_session" class="mb-1 block text-sm font-medium text-slate-700">From Session</label>
                            <select
                                id="from_session"
                                name="from_session"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                @foreach ($sessions as $session)
                                    <option value="{{ $session }}" @selected($defaultFromSession === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="to_session" class="mb-1 block text-sm font-medium text-slate-700">To Session</label>
                            <select
                                id="to_session"
                                name="to_session"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                @foreach ($sessions as $session)
                                    <option value="{{ $session }}" @selected($defaultToSession === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="teacher_ids" class="mb-1 block text-sm font-medium text-slate-700">Teachers (Optional)</label>
                        <select
                            id="teacher_ids"
                            name="teacher_ids[]"
                            multiple
                            class="block h-60 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(in_array((int) $teacher->id, $selectedTeacherIds, true))>
                                    {{ $teacher->user?->name ?? 'Unknown Teacher' }} ({{ $teacher->teacher_id }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Leave empty to copy all teachers. Hold Ctrl/Command to select multiple teachers.</p>
                    </div>

                    <div class="flex items-start gap-2">
                        <input
                            id="overwrite"
                            type="checkbox"
                            name="overwrite"
                            value="1"
                            @checked($overwrite)
                            class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >
                        <label for="overwrite" class="text-sm text-slate-700">
                            Overwrite target session assignments for selected teachers (existing target rows for selected teachers will be replaced).
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Preview Rollover
                        </button>
                        <button
                            type="submit"
                            formaction="{{ route('principal.teacher-assignments.rollover.store') }}"
                            class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            onclick="return confirm('Copy assignments to the selected target session now?');"
                        >
                            Execute Copy
                        </button>
                    </div>
                </form>
            </div>

            @if (is_array($preview))
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Preview Summary</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Source: <span class="font-medium">{{ $preview['from_session'] }}</span>
                        | Target: <span class="font-medium">{{ $preview['to_session'] }}</span>
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-5">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Teachers</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($preview['teachers_count'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Assignments</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($preview['assignment_count'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Duplicates</p>
                            <p class="mt-1 text-lg font-semibold text-amber-700">{{ (int) ($preview['duplicate_count'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Class Teacher Conflicts</p>
                            <p class="mt-1 text-lg font-semibold text-rose-700">{{ (int) ($preview['class_teacher_conflicts'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Target Existing Rows</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ (int) ($preview['target_existing_assignments'] ?? 0) }}</p>
                        </div>
                    </div>

                    @if ((int) ($preview['target_existing_assignments'] ?? 0) > 0 || (int) ($preview['duplicate_count'] ?? 0) > 0 || (int) ($preview['class_teacher_conflicts'] ?? 0) > 0)
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <p class="font-semibold">Preview Warnings</p>
                            <ul class="mt-1 list-disc space-y-1 ps-5">
                                @if ((int) ($preview['target_existing_assignments'] ?? 0) > 0)
                                    <li>Target session already has existing assignments.</li>
                                @endif
                                @if ((int) ($preview['duplicate_count'] ?? 0) > 0)
                                    <li>Some copied rows will be skipped as duplicates.</li>
                                @endif
                                @if ((int) ($preview['class_teacher_conflicts'] ?? 0) > 0)
                                    <li>Some class teacher rows conflict with existing target session class teachers.</li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Teacher</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Source Assignments</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Duplicates</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Class Teacher Conflicts</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse(($preview['teacher_rows'] ?? collect()) as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-slate-700">{{ $row['teacher_name'] }}</td>
                                        <td class="px-4 py-2 text-sm text-slate-700">{{ (int) $row['source_assignments'] }}</td>
                                        <td class="px-4 py-2 text-sm text-amber-700">{{ (int) $row['duplicates'] }}</td>
                                        <td class="px-4 py-2 text-sm text-rose-700">{{ (int) $row['class_teacher_conflicts'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No teacher assignments found for this preview.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
