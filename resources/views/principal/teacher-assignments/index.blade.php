<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Teacher Assignments</h2>
            <a
                href="{{ route('principal.teacher-assignments.create') }}"
                class="inline-flex min-h-10 items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                New Bulk Assignment
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('principal.teacher-assignments.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="search" class="mb-1 block text-sm font-medium text-slate-700">Search</label>
                        <input
                            id="search"
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Teacher, class, subject, session"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                    </div>

                    <div>
                        <label for="session" class="mb-1 block text-sm font-medium text-slate-700">Session</label>
                        <select
                            id="session"
                            name="session"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            <option value="">All Sessions</option>
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Filter
                        </button>
                        <a
                            href="{{ route('principal.teacher-assignments.index') }}"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            @forelse ($assignmentsGrouped as $group)
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">
                                {{ $group['teacher']?->user?->name ?? 'Unknown Teacher' }}
                            </h3>
                            <p class="text-sm text-slate-600">
                                Teacher ID: {{ $group['teacher']?->teacher_id ?? '-' }}
                                @if ($group['teacher']?->employee_code)
                                    | Employee Code: {{ $group['teacher']?->employee_code }}
                                @endif
                            </p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            Session: {{ $group['session'] }}
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div>
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Class Teacher Of</h4>

                            @if ($group['class_teacher_assignments']->isEmpty())
                                <p class="mt-2 text-sm text-slate-500">No class teacher assignment.</p>
                            @else
                                <ul class="mt-3 space-y-2">
                                    @foreach ($group['class_teacher_assignments'] as $assignment)
                                        <li class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2">
                                            <span class="text-sm text-slate-700">
                                                {{ trim(($assignment->classRoom?->name ?? '-') . ' ' . ($assignment->classRoom?->section ?? '')) }}
                                            </span>
                                            <form method="POST" action="{{ route('principal.teacher-assignments.destroy', $assignment->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="rounded-md border border-rose-300 px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                                                    onclick="return confirm('Delete this class teacher assignment?')"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Subject Assignments</h4>

                            @if ($group['subject_assignments_by_class']->isEmpty())
                                <p class="mt-2 text-sm text-slate-500">No subject assignments.</p>
                            @else
                                <div class="mt-3 space-y-4">
                                    @foreach ($group['subject_assignments_by_class'] as $classGroup)
                                        <div class="rounded-md border border-slate-200 p-3">
                                            <p class="text-sm font-medium text-slate-800">
                                                {{ trim(($classGroup['class']?->name ?? '-') . ' ' . ($classGroup['class']?->section ?? '')) }}
                                            </p>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($classGroup['assignments'] as $assignment)
                                                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-slate-50 px-3 py-1 text-xs text-slate-700">
                                                        <span>{{ $assignment->subject?->name ?? '-' }}</span>
                                                        <form method="POST" action="{{ route('principal.teacher-assignments.destroy', $assignment->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="font-semibold text-rose-700 hover:text-rose-800"
                                                                title="Delete assignment"
                                                                onclick="return confirm('Delete this subject assignment?')"
                                                            >
                                                                x
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
                    No assignments found for the selected filters.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
