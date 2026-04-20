<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Student Attendance Overview</h2>
                <p class="mt-1 text-sm text-slate-500">Class-wise attendance, absentees, and teacher marking status.</p>
            </div>
            <a
                href="{{ route('reports.pdf.attendance-report', ['date' => $selectedDate, 'class_id' => $selectedClassId]) }}"
                target="_blank"
                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
                Print Attendance PDF
            </a>
        </div>
    </x-slot>

    @php
        $summary = $overview['summary'] ?? [];
        $teacherMarking = ($overview['teacher_marking'] ?? collect()) instanceof \Illuminate\Support\Collection
            ? $overview['teacher_marking']
            : collect($overview['teacher_marking'] ?? []);
        $absentStudents = ($overview['absent_students'] ?? collect()) instanceof \Illuminate\Support\Collection
            ? $overview['absent_students']
            : collect($overview['absent_students'] ?? []);

        $markedByTeachers = $teacherMarking
            ->filter(fn (array $row): bool => (bool) ($row['is_marked'] ?? false) && ($row['teacher_id'] ?? null) !== null)
            ->values();
        $notMarkedTeachers = $teacherMarking
            ->filter(fn (array $row): bool => ! (bool) ($row['is_marked'] ?? false) && ($row['teacher_id'] ?? null) !== null)
            ->values();
    @endphp

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('principal.attendance.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <label for="date" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                    <input
                        id="date"
                        name="date"
                        type="date"
                        value="{{ $selectedDate }}"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label for="session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select
                        id="session"
                        name="session"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="class_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class (Optional)</label>
                    <select
                        id="class_id"
                        name="class_id"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Classes</option>
                        @foreach ($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((int) $selectedClassId === (int) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Apply Filters
                    </button>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Classes</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary['classes_count'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-slate-500">Session: {{ $selectedSession }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Students</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary['total_students'] ?? 0) }}</p>
                <p class="mt-1 text-xs text-slate-500">Date: {{ $selectedDate }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Present</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ (int) ($summary['present'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Absent Today</p>
                <p class="mt-2 text-2xl font-semibold text-rose-800">{{ (int) ($summary['absent'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Leave</p>
                <p class="mt-2 text-2xl font-semibold text-amber-800">{{ (int) ($summary['leave'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Classes Marked</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-800">{{ (int) ($summary['classes_marked'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Classes Not Marked</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary['classes_not_marked'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unassigned Classes</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary['unassigned_classes'] ?? 0) }}</p>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Teachers Who Marked Attendance</h3>
                </div>
                <div class="max-h-80 overflow-auto px-5 py-4">
                    @if ($markedByTeachers->isEmpty())
                        <p class="text-sm text-slate-500">No class teacher has marked attendance for selected filters.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($markedByTeachers as $row)
                                <li class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                    <span class="font-semibold">{{ $row['teacher_name'] }}</span>
                                    <span class="text-emerald-700"> ({{ $row['teacher_code'] }})</span>
                                    <span class="text-emerald-700"> - {{ $row['class_name'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Teachers Who Have Not Marked Attendance</h3>
                </div>
                <div class="max-h-80 overflow-auto px-5 py-4">
                    @if ($notMarkedTeachers->isEmpty())
                        <p class="text-sm text-slate-500">All assigned class teachers have marked attendance.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($notMarkedTeachers as $row)
                                <li class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                                    <span class="font-semibold">{{ $row['teacher_name'] }}</span>
                                    <span class="text-rose-700"> ({{ $row['teacher_code'] }})</span>
                                    <span class="text-rose-700"> - {{ $row['class_name'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Absent Students Today</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($absentStudents as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['student_id_value'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['student_name'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['class_name'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No absent students found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Class-wise Attendance List</h3>
                <p class="mt-1 text-xs text-slate-500">Marking status is based on assigned class teacher for selected session.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class Teacher</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Active Students</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Present</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Absent</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Leave</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marked/Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($teacherMarking as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['class_name'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div>{{ $row['teacher_name'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $row['teacher_code'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $row['active_students'] }}</td>
                                <td class="px-4 py-3 text-sm text-emerald-700">{{ (int) $row['present'] }}</td>
                                <td class="px-4 py-3 text-sm text-rose-700">{{ (int) $row['absent'] }}</td>
                                <td class="px-4 py-3 text-sm text-amber-700">{{ (int) $row['leave'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $row['marked_students'] }}/{{ (int) $row['active_students'] }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if (($row['teacher_id'] ?? null) === null)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">No class teacher</span>
                                    @elseif ($row['is_marked'] && ! $row['is_partial'])
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Marked</span>
                                    @elseif ($row['is_marked'] && $row['is_partial'])
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Partially Marked</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Not Marked</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">No class-wise attendance rows found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>

