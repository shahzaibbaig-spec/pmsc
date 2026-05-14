<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Teacher Assignments</h2>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="#copyAllocationSection"
                    class="inline-flex min-h-10 items-center justify-center rounded-md border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50"
                >
                    Copy Allocation to Section
                </a>
                <a
                    href="{{ route('principal.teacher-assignments.rollover.index') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-md border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
                >
                    Session Rollover
                </a>
                <a
                    href="{{ route('principal.teacher-assignments.create') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    New Bulk Assignment
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
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
                <h3 class="text-base font-semibold text-slate-900">Teacher Search & Assign</h3>
                <p class="mt-1 text-sm text-slate-600">Search by teacher name, email, employee code, or teacher code.</p>

                <div class="relative mt-4">
                    <input
                        id="globalTeacherSearchInput"
                        type="text"
                        autocomplete="off"
                        placeholder="Type at least 2 characters..."
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >
                    <div
                        id="globalTeacherSearchResults"
                        class="absolute z-20 mt-2 hidden max-h-72 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
                    ></div>
                </div>

                <div id="selectedTeacherPanel" class="mt-6"></div>
            </div>

            <div id="sectionHeadAssignmentSection" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Section Head Assignment</h3>
                        <p class="mt-1 text-sm text-slate-600">Assign a teacher as Early Years, Middle School, or Senior School Section Head for a session.</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                        Historical records preserved
                    </span>
                </div>

                @can('assign_section_heads')
                    <form method="POST" action="{{ route('principal.teacher-assignments.section-head-assignments.store') }}" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
                        @csrf
                        <div class="md:col-span-2">
                            <label for="sh_teacher_id" class="mb-1 block text-sm font-medium text-slate-700">Teacher</label>
                            <select
                                id="sh_teacher_id"
                                name="teacher_id"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select teacher</option>
                                @foreach ($teachersList as $teacherOption)
                                    <option value="{{ $teacherOption->id }}" @selected((int) old('teacher_id') === (int) $teacherOption->id)>
                                        {{ $teacherOption->user?->name ?? ('Teacher #'.$teacherOption->id) }}
                                        @if ($teacherOption->teacher_id)
                                            ({{ $teacherOption->teacher_id }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="sh_section_head_type" class="mb-1 block text-sm font-medium text-slate-700">Section Head Type</label>
                            <select
                                id="sh_section_head_type"
                                name="section_head_type"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select type</option>
                                @foreach ($sectionHeadTypeOptions as $typeOption)
                                    <option value="{{ $typeOption }}" @selected(old('section_head_type') === $typeOption)>{{ $typeOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="sh_scope" class="mb-1 block text-sm font-medium text-slate-700">Scope</label>
                            <select
                                id="sh_scope"
                                name="scope"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select scope</option>
                                @foreach ($sectionHeadScopeOptions as $scopeKey => $scopeLabel)
                                    <option value="{{ $scopeKey }}" @selected(old('scope') === $scopeKey)>{{ $scopeLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="sh_session_assign" class="mb-1 block text-sm font-medium text-slate-700">Session</label>
                            <select
                                id="sh_session_assign"
                                name="session"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                @foreach ($sessions as $session)
                                    <option value="{{ $session }}" @selected(old('session', $selectedSession ?: $classTeacherSession) === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-4">
                            <button
                                type="submit"
                                class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                Assign Section Head
                            </button>
                        </div>
                    </form>
                @endcan

                <form method="GET" action="{{ route('principal.teacher-assignments.index') }}" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-5">
                    <input type="hidden" name="session" value="{{ $selectedSession }}">
                    <div>
                        <label for="sh_session" class="mb-1 block text-sm font-medium text-slate-700">Session</label>
                        <select id="sh_session" name="sh_session" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All Sessions</option>
                            @foreach (collect(array_merge($sessions, $sectionHeadSessions))->unique()->values() as $sessionOption)
                                <option value="{{ $sessionOption }}" @selected(($sectionHeadFilters['session'] ?? null) === $sessionOption)>{{ $sessionOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sh_scope_filter" class="mb-1 block text-sm font-medium text-slate-700">Scope</label>
                        <select id="sh_scope_filter" name="sh_scope" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All Scopes</option>
                            @foreach ($sectionHeadScopeOptions as $scopeKey => $scopeLabel)
                                <option value="{{ $scopeKey }}" @selected(($sectionHeadFilters['scope'] ?? null) === $scopeKey)>{{ $scopeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sh_status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                        <select id="sh_status" name="sh_status" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="active" @selected(($sectionHeadFilters['status'] ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(($sectionHeadFilters['status'] ?? null) === 'inactive')>Inactive</option>
                            <option value="all" @selected(($sectionHeadFilters['status'] ?? null) === 'all')>All</option>
                        </select>
                    </div>
                    <div>
                        <label for="sh_teacher_name" class="mb-1 block text-sm font-medium text-slate-700">Teacher Name</label>
                        <input
                            id="sh_teacher_name"
                            type="text"
                            name="sh_teacher_name"
                            value="{{ $sectionHeadFilters['teacher_name'] ?? '' }}"
                            placeholder="Search teacher"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                    </div>
                    <div>
                        <label for="sh_per_page" class="mb-1 block text-sm font-medium text-slate-700">Rows</label>
                        <select id="sh_per_page" name="sh_per_page" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ([10, 20, 50] as $size)
                                <option value="{{ $size }}" @selected((int) ($sectionHeadFilters['per_page'] ?? 10) === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-5 flex flex-wrap items-center gap-2">
                        <button type="submit" class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Filter Section Heads</button>
                        <a href="{{ route('principal.teacher-assignments.index') }}#sectionHeadAssignmentSection" class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Sr #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Scope</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Session</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Assigned By</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Assigned At</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @php
                                $shPageOffset = ($sectionHeadAssignments->currentPage() - 1) * $sectionHeadAssignments->perPage();
                            @endphp
                            @forelse ($sectionHeadAssignments as $index => $sectionHeadAssignment)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $shPageOffset + $index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        {{ $sectionHeadAssignment->teacher?->user?->name ?? '-' }}
                                        <p class="text-xs text-slate-500">{{ $sectionHeadAssignment->teacher?->user?->email ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $sectionHeadAssignment->section_head_type }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $sectionHeadScopeOptions[$sectionHeadAssignment->scope] ?? $sectionHeadAssignment->scope }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $sectionHeadAssignment->session }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $sectionHeadAssignment->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                            {{ ucfirst($sectionHeadAssignment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $sectionHeadAssignment->assignedBy?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ optional($sectionHeadAssignment->assigned_at)->format('d M Y h:i A') ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @can('assign_section_heads')
                                            @if ($sectionHeadAssignment->status === 'active')
                                                <form method="POST" action="{{ route('principal.teacher-assignments.section-head-assignments.deactivate', $sectionHeadAssignment) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex min-h-9 items-center rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                                                        onclick="return confirm('Deactivate this section head assignment? History will be preserved.');"
                                                    >
                                                        Deactivate
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-slate-500">-</span>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">No section head assignments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($sectionHeadAssignments->hasPages())
                    <div class="mt-4 border-t border-slate-200 pt-3">
                        {{ $sectionHeadAssignments->links() }}
                    </div>
                @endif
            </div>

            <div id="copyAllocationSection" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Copy Allocation to Section</h3>
                        <p class="mt-1 text-sm text-slate-600">Copy subject and class-teacher allocations between sections of the same class.</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        Same class only
                    </span>
                </div>

                <form
                    method="POST"
                    action="{{ route('principal.teacher-assignments.copy-section') }}"
                    class="mt-5 space-y-5"
                    onsubmit="return this.copy_mode.value !== 'replace_target_allocations' || confirm('Replace mode will archive current target allocations for the selected session before copying. Continue?');"
                >
                    @csrf
                    <input type="hidden" id="copy_source_section" name="source_section" value="{{ old('source_section') }}">
                    <input type="hidden" id="copy_target_section" name="target_section" value="{{ old('target_section') }}">

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="copy_source_class_id" class="mb-1 block text-sm font-medium text-slate-700">Source Section</label>
                            <select
                                id="copy_source_class_id"
                                name="source_class_id"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select source section</option>
                                @foreach ($classes as $class)
                                    <option
                                        value="{{ $class->id }}"
                                        data-section="{{ $class->section }}"
                                        @selected((string) old('source_class_id') === (string) $class->id)
                                    >
                                        {{ trim($class->name . ' ' . ($class->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="copy_target_class_id" class="mb-1 block text-sm font-medium text-slate-700">Target Section</label>
                            <select
                                id="copy_target_class_id"
                                name="target_class_id"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select target section</option>
                                @foreach ($classes as $class)
                                    <option
                                        value="{{ $class->id }}"
                                        data-section="{{ $class->section }}"
                                        @selected((string) old('target_class_id') === (string) $class->id)
                                    >
                                        {{ trim($class->name . ' ' . ($class->section ?? '')) }}
                                        @if (($class->status ?? 'active') !== 'active')
                                            (Inactive)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="copy_session" class="mb-1 block text-sm font-medium text-slate-700">Session</label>
                            <select
                                id="copy_session"
                                name="session"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                @foreach ($sessions as $session)
                                    <option value="{{ $session }}" @selected(old('session', $selectedSession ?: $classTeacherSession) === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="copy_mode" class="mb-1 block text-sm font-medium text-slate-700">Copy Mode</label>
                            <select
                                id="copy_mode"
                                name="copy_mode"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="copy_missing_only" @selected(old('copy_mode', 'copy_missing_only') === 'copy_missing_only')>Copy missing only</option>
                                <option value="replace_target_allocations" @selected(old('copy_mode') === 'replace_target_allocations')>Replace target allocations</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Copy Allocation to Section
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Class Teacher Assignment</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Changing the class teacher will automatically remove the previous class teacher assignment for this class in the selected session.
                </p>

                <div class="mt-4 max-w-xs">
                    <label for="classTeacherSessionSelect" class="mb-1 block text-sm font-medium text-slate-700">Session</label>
                    <select
                        id="classTeacherSessionSelect"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected($classTeacherSession === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="classTeacherMatrixContainer" class="mt-4">
                    @include('principal.teacher-assignments.partials.class-teacher-table', [
                        'selectedSession' => $classTeacherSession,
                        'classTeacherRows' => $classTeacherRows,
                    ])
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Session Rollover</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Copy assignments to the next session, then review and modify teacher-by-teacher as needed.
                </p>
                <div class="mt-4">
                    <a
                        href="{{ route('principal.teacher-assignments.rollover.index') }}"
                        class="inline-flex min-h-10 items-center rounded-md border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
                    >
                        Open Session Rollover
                    </a>
                </div>
            </div>

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

    <script>
        function classTeacherPicker(config) {
            return {
                searchUrl: config.searchUrl || '',
                currentTeacherId: Number(config.currentTeacherId || 0),
                selectedTeacherId: Number(config.currentTeacherId || 0) || null,
                selectedTeacherLabel: config.currentTeacherName || '',
                query: config.currentTeacherName || '',
                results: [],
                open: false,
                loading: false,
                noResults: false,
                onFocus() {
                    const value = this.query.trim();
                    if (value.length >= 2) {
                        this.searchTeachers();
                    } else if (this.results.length > 0) {
                        this.open = true;
                    }
                },
                async searchTeachers() {
                    const value = this.query.trim();
                    if (value.length < 2) {
                        this.results = [];
                        this.noResults = false;
                        this.open = false;
                        return;
                    }

                    this.loading = true;
                    this.open = true;
                    this.noResults = false;

                    try {
                        const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(value)}&limit=12`, {
                            headers: { 'Accept': 'application/json' },
                        });

                        if (!response.ok) {
                            throw new Error('Teacher search failed.');
                        }

                        const payload = await response.json();
                        this.results = Array.isArray(payload) ? payload : [];
                        this.noResults = this.results.length === 0;
                    } catch (error) {
                        this.results = [];
                        this.noResults = true;
                    } finally {
                        this.loading = false;
                    }
                },
                chooseTeacher(teacher) {
                    this.selectedTeacherId = Number(teacher?.id || 0) || null;
                    this.selectedTeacherLabel = String(teacher?.name || '').trim();
                    this.query = this.selectedTeacherLabel;
                    this.results = [];
                    this.noResults = false;
                    this.open = false;
                },
                confirmReplacement() {
                    if (!this.selectedTeacherId) {
                        alert('Please select a teacher first.');
                        return false;
                    }

                    if (this.currentTeacherId > 0 && this.currentTeacherId !== this.selectedTeacherId) {
                        return confirm('The existing class teacher for this class will be removed and replaced. Do you want to continue?');
                    }

                    return true;
                },
            };
        }

        function teacherPanelAssignmentForm(classes, initialClassIds, initialClassTeacherClassId) {
            return {
                classes: classes || [],
                selectedClassIds: (initialClassIds || []).map(String),
                classTeacherClassId: initialClassTeacherClassId ? String(initialClassTeacherClassId) : '',
                init() {
                    this.$watch('selectedClassIds', () => {
                        if (this.classTeacherClassId && !this.selectedClassIds.includes(this.classTeacherClassId)) {
                            this.classTeacherClassId = '';
                        }
                    });
                },
                selectedClassOptions() {
                    return this.classes.filter((classOption) => this.selectedClassIds.includes(String(classOption.id)));
                },
            };
        }

        (() => {
            const searchInput = document.getElementById('globalTeacherSearchInput');
            const resultsBox = document.getElementById('globalTeacherSearchResults');
            const panel = document.getElementById('selectedTeacherPanel');
            const sessionSelect = document.getElementById('session');
            const classTeacherSessionSelect = document.getElementById('classTeacherSessionSelect');
            const classTeacherMatrixContainer = document.getElementById('classTeacherMatrixContainer');
            const copySourceClassSelect = document.getElementById('copy_source_class_id');
            const copyTargetClassSelect = document.getElementById('copy_target_class_id');
            const copySourceSectionInput = document.getElementById('copy_source_section');
            const copyTargetSectionInput = document.getElementById('copy_target_section');
            const sectionHeadTypeSelect = document.getElementById('sh_section_head_type');
            const sectionHeadScopeSelect = document.getElementById('sh_scope');
            const escapeHtml = (window.NSMS && typeof window.NSMS.escapeHtml === 'function')
                ? window.NSMS.escapeHtml
                : (value) => String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');

            const searchUrl = @json(route('principal.teacher-assignments.search'));
            const showUrlTemplate = @json(route('principal.teacher-assignments.teacher.show', ['teacher' => '__TEACHER__']));
            const classTeacherMatrixUrl = @json(route('principal.teacher-assignments.class-teachers'));
            const focusTeacherId = Number(@json((int) request()->query('focus_teacher', 0)));
            const sectionHeadTypeScopeMap = @json($sectionHeadTypeScopeMap);
            let selectedTeacherId = null;
            let activeTeacherPanelSession = '';

            if (sessionSelect && sessionSelect.value) {
                activeTeacherPanelSession = sessionSelect.value;
            } else if (classTeacherSessionSelect && classTeacherSessionSelect.value) {
                activeTeacherPanelSession = classTeacherSessionSelect.value;
            }

            function showTeacherUrl(teacherId) {
                return showUrlTemplate.replace('__TEACHER__', String(teacherId));
            }

            function syncSelectedSection(select, input) {
                if (!select || !input) {
                    return;
                }

                input.value = select.selectedOptions[0]?.dataset?.section || '';
            }

            function setResultsLoading() {
                resultsBox.classList.remove('hidden');
                resultsBox.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">Searching...</div>';
            }

            function clearResults() {
                resultsBox.classList.add('hidden');
                resultsBox.innerHTML = '';
            }

            function renderSearchResults(rows) {
                if (!rows || rows.length === 0) {
                    resultsBox.classList.remove('hidden');
                    resultsBox.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">No teachers found.</div>';
                    return;
                }

                resultsBox.classList.remove('hidden');
                resultsBox.innerHTML = rows.map((row) => {
                    const label = escapeHtml(row.name || 'Unknown Teacher');
                    const email = escapeHtml(row.email || '');
                    const teacherCode = escapeHtml(row.teacher_code || '-');
                    const employeeCode = escapeHtml(row.employee_code || '-');

                    return `
                        <button
                            type="button"
                            class="block w-full border-b border-slate-100 px-3 py-2 text-left text-sm hover:bg-slate-50"
                            data-teacher-id="${row.id}"
                        >
                            <div class="font-medium text-slate-900">${label}</div>
                            <div class="text-xs text-slate-600">${email}</div>
                            <div class="mt-0.5 text-xs text-slate-500">Teacher Code: ${teacherCode} | Employee Code: ${employeeCode}</div>
                        </button>
                    `;
                }).join('');
            }

            async function loadTeacherPanel(teacherId) {
                selectedTeacherId = Number(teacherId);
                panel.innerHTML = '<div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">Loading teacher assignments...</div>';

                try {
                    const url = new URL(showTeacherUrl(teacherId), window.location.origin);
                    const resolvedPanelSession = String(activeTeacherPanelSession || '').trim();
                    if (resolvedPanelSession !== '') {
                        url.searchParams.set('session', resolvedPanelSession);
                    }

                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load teacher assignment panel.');
                    }

                    const payload = await response.json();
                    panel.innerHTML = payload.html || '';

                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                        window.Alpine.initTree(panel);
                    }
                } catch (error) {
                    panel.innerHTML = '<div class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">Failed to load teacher assignment panel.</div>';
                }
            }

            async function loadClassTeacherMatrix(session) {
                if (!classTeacherMatrixContainer || !session) {
                    return;
                }

                classTeacherMatrixContainer.innerHTML = '<div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">Loading class teacher assignments...</div>';

                try {
                    const response = await fetch(`${classTeacherMatrixUrl}?session=${encodeURIComponent(session)}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load class teacher matrix.');
                    }

                    const payload = await response.json();
                    classTeacherMatrixContainer.innerHTML = payload.html || '';

                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                        window.Alpine.initTree(classTeacherMatrixContainer);
                    }
                } catch (error) {
                    classTeacherMatrixContainer.innerHTML = '<div class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">Failed to load class teacher assignments.</div>';
                }
            }

            const performSearch = window.NSMS.debounce(async () => {
                const query = searchInput.value.trim();
                if (query.length < 2) {
                    clearResults();
                    return;
                }

                setResultsLoading();

                try {
                    const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('Search failed.');
                    }

                    const payload = await response.json();
                    renderSearchResults(Array.isArray(payload) ? payload : []);
                } catch (error) {
                    resultsBox.classList.remove('hidden');
                    resultsBox.innerHTML = '<div class="px-3 py-2 text-sm text-rose-700">Search failed. Please try again.</div>';
                }
            }, 300);

            searchInput.addEventListener('input', performSearch);
            resultsBox.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-teacher-id]');
                if (!button) {
                    return;
                }

                const teacherId = Number(button.dataset.teacherId || 0);
                if (teacherId <= 0) {
                    return;
                }

                clearResults();
                searchInput.value = '';
                loadTeacherPanel(teacherId);
            });

            document.addEventListener('click', (event) => {
                if (!resultsBox.contains(event.target) && event.target !== searchInput) {
                    clearResults();
                }
            });

            sessionSelect?.addEventListener('change', () => {
                activeTeacherPanelSession = sessionSelect.value;
                if (selectedTeacherId) {
                    loadTeacherPanel(selectedTeacherId);
                }
            });

            classTeacherSessionSelect?.addEventListener('change', () => {
                activeTeacherPanelSession = classTeacherSessionSelect.value;
                loadClassTeacherMatrix(classTeacherSessionSelect.value);
                if (selectedTeacherId) {
                    loadTeacherPanel(selectedTeacherId);
                }
            });

            copySourceClassSelect?.addEventListener('change', () => syncSelectedSection(copySourceClassSelect, copySourceSectionInput));
            copyTargetClassSelect?.addEventListener('change', () => syncSelectedSection(copyTargetClassSelect, copyTargetSectionInput));
            syncSelectedSection(copySourceClassSelect, copySourceSectionInput);
            syncSelectedSection(copyTargetClassSelect, copyTargetSectionInput);
            sectionHeadTypeSelect?.addEventListener('change', () => {
                if (!sectionHeadScopeSelect) {
                    return;
                }
                const mappedScope = sectionHeadTypeScopeMap?.[sectionHeadTypeSelect.value];
                if (mappedScope) {
                    sectionHeadScopeSelect.value = mappedScope;
                }
            });

            if (focusTeacherId > 0) {
                loadTeacherPanel(focusTeacherId);
            }
        })();
    </script>
</x-app-layout>
