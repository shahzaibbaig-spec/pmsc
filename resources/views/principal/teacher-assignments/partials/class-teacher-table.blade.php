@php
    $searchRoute = route('principal.teacher-assignments.search');
    $assignRoute = route('principal.teacher-assignments.class-teachers.assign');
@endphp

@if (empty($classTeacherRows))
    <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        No classes found for class teacher assignment.
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Class</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Current Class Teacher</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Assign / Change Class Teacher</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($classTeacherRows as $row)
                    <tr
                        x-data="classTeacherPicker({
                            searchUrl: @js($searchRoute),
                            currentTeacherId: @js($row['current_teacher_id']),
                            currentTeacherName: @js($row['current_teacher_name']),
                            currentTeacherEmail: @js($row['current_teacher_email']),
                            currentTeacherCode: @js($row['current_teacher_code']),
                            currentEmployeeCode: @js($row['current_employee_code'])
                        })"
                        class="align-top"
                    >
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">
                            {{ $row['class_name'] }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            @if ($row['current_teacher_id'])
                                <div class="font-medium text-slate-900">{{ $row['current_teacher_name'] }}</div>
                                <div class="text-xs text-slate-600">
                                    @if (!empty($row['current_teacher_email']))
                                        {{ $row['current_teacher_email'] }}
                                    @elseif (!empty($row['current_employee_code']))
                                        Employee: {{ $row['current_employee_code'] }}
                                    @elseif (!empty($row['current_teacher_code']))
                                        Teacher Code: {{ $row['current_teacher_code'] }}
                                    @else
                                        Teacher assigned
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    Not Assigned
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="relative max-w-md" @click.away="open = false">
                                <input
                                    type="text"
                                    x-model="query"
                                    @input.debounce.300ms="searchTeachers"
                                    @focus="onFocus"
                                    autocomplete="off"
                                    placeholder="Search teacher by name, email, employee code..."
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                >

                                <div
                                    x-show="open"
                                    x-transition
                                    class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
                                    style="display: none;"
                                >
                                    <div x-show="loading" class="px-3 py-2 text-xs text-slate-500">Searching...</div>

                                    <template x-for="teacher in results" :key="teacher.id">
                                        <button
                                            type="button"
                                            @click="chooseTeacher(teacher)"
                                            class="block w-full border-b border-slate-100 px-3 py-2 text-left hover:bg-slate-50"
                                        >
                                            <div class="text-sm font-medium text-slate-900" x-text="teacher.name || 'Unknown Teacher'"></div>
                                            <div class="text-xs text-slate-600" x-text="teacher.email || ''"></div>
                                            <div class="text-xs text-slate-500">
                                                <span x-text="'Teacher Code: ' + (teacher.teacher_code || '-')"></span>
                                                <span x-text="' | Employee Code: ' + (teacher.employee_code || '-')"></span>
                                            </div>
                                        </button>
                                    </template>

                                    <div x-show="!loading && noResults" class="px-3 py-2 text-xs text-slate-500">
                                        No teachers found.
                                    </div>
                                </div>
                            </div>

                            <p class="mt-1 text-xs text-slate-600" x-show="selectedTeacherId" style="display: none;">
                                Selected: <span class="font-medium text-slate-800" x-text="selectedTeacherLabel"></span>
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <form
                                method="POST"
                                action="{{ $assignRoute }}"
                                @submit="return confirmReplacement()"
                                class="inline-flex"
                            >
                                @csrf
                                <input type="hidden" name="class_id" value="{{ $row['class_id'] }}">
                                <input type="hidden" name="session" value="{{ $selectedSession }}">
                                <input type="hidden" name="teacher_id" :value="selectedTeacherId || ''">

                                <button
                                    type="submit"
                                    :disabled="!selectedTeacherId"
                                    class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

