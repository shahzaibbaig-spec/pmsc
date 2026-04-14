@php
    $sessionGroups = $summary['sessions'] ?? collect();
    $activeSessionGroup = $sessionGroups instanceof \Illuminate\Support\Collection ? $sessionGroups->first() : null;
@endphp

<div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ $teacher->user?->name ?? 'Unknown Teacher' }}</h3>
            <p class="text-sm text-slate-600">
                Teacher Code: {{ $teacher->teacher_id ?? '-' }}
                @if ($teacher->employee_code)
                    | Employee Code: {{ $teacher->employee_code }}
                @endif
                @if ($teacher->user?->email)
                    | {{ $teacher->user->email }}
                @endif
            </p>
        </div>
        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
            Session: {{ $selectedSession }}
        </span>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div>
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Current Class Teacher Assignments</h4>
            @if (! $activeSessionGroup || $activeSessionGroup['class_teacher_assignments']->isEmpty())
                <p class="mt-2 text-sm text-slate-500">No class teacher assignments in this session.</p>
            @else
                <ul class="mt-2 space-y-2">
                    @foreach ($activeSessionGroup['class_teacher_assignments'] as $assignment)
                        <li class="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            {{ trim(($assignment->classRoom?->name ?? '-') . ' ' . ($assignment->classRoom?->section ?? '')) }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Current Subject Assignments</h4>
            @if (! $activeSessionGroup || $activeSessionGroup['subject_assignments_by_class']->isEmpty())
                <p class="mt-2 text-sm text-slate-500">No subject assignments in this session.</p>
            @else
                <div class="mt-2 space-y-3">
                    @foreach ($activeSessionGroup['subject_assignments_by_class'] as $classGroup)
                        <div class="rounded-md border border-slate-200 p-3">
                            <p class="text-sm font-medium text-slate-800">
                                {{ trim(($classGroup['class']?->name ?? '-') . ' ' . ($classGroup['class']?->section ?? '')) }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($classGroup['assignments'] as $assignment)
                                    <span class="inline-flex rounded-full border border-slate-300 bg-slate-50 px-3 py-1 text-xs text-slate-700">
                                        {{ $assignment->subject?->name ?? '-' }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <h4 class="text-sm font-semibold text-slate-900">Update Assignments for This Teacher</h4>
        <p class="mt-1 text-xs text-slate-600">
            Use Save Assignments to add/update without removing existing rows. Use Replace Session Assignments to overwrite this teacher's selected session assignments.
        </p>

        <form
            method="POST"
            action="{{ route('principal.teacher-assignments.teacher.bulk-store', $teacher->id) }}"
            class="mt-4 space-y-4"
            x-data="teacherPanelAssignmentForm(
                @js($classes->map(fn ($class) => ['id' => (string) $class->id, 'label' => trim($class->name . ' ' . ($class->section ?? ''))])->values()),
                [],
                null
            )"
            x-init="init()"
        >
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="quick_session_{{ $teacher->id }}" class="mb-1 block text-sm font-medium text-slate-700">Session</label>
                    <select
                        id="quick_session_{{ $teacher->id }}"
                        name="session"
                        required
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="quick_class_ids_{{ $teacher->id }}" class="mb-1 block text-sm font-medium text-slate-700">Classes</label>
                    <select
                        id="quick_class_ids_{{ $teacher->id }}"
                        name="class_ids[]"
                        multiple
                        required
                        x-model="selectedClassIds"
                        class="block h-48 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">
                                {{ trim($class->name . ' ' . ($class->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="quick_subject_ids_{{ $teacher->id }}" class="mb-1 block text-sm font-medium text-slate-700">Subjects</label>
                    <select
                        id="quick_subject_ids_{{ $teacher->id }}"
                        name="subject_ids[]"
                        multiple
                        required
                        class="block h-48 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="quick_class_teacher_class_id_{{ $teacher->id }}" class="mb-1 block text-sm font-medium text-slate-700">Optional Class Teacher Class</label>
                <select
                    id="quick_class_teacher_class_id_{{ $teacher->id }}"
                    name="class_teacher_class_id"
                    x-model="classTeacherClassId"
                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >
                    <option value="">No class teacher assignment</option>
                    <template x-for="classOption in selectedClassOptions()" :key="classOption.id">
                        <option :value="classOption.id" x-text="classOption.label"></option>
                    </template>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Save Assignments
                </button>
                <button
                    type="submit"
                    formaction="{{ route('principal.teacher-assignments.teacher.replace-session-assignments', $teacher->id) }}"
                    class="inline-flex min-h-10 items-center rounded-md border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50"
                    onclick="return confirm('Replace this teacher\\'s assignments for selected session? Existing assignments for this teacher/session will be removed first.');"
                >
                    Replace Session Assignments
                </button>
            </div>
        </form>
    </div>
</div>

