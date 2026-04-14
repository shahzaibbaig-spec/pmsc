<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Teacher Results Access</h2>
                <p class="mt-1 text-sm text-slate-500">View class results by session and exam type based on your teacher role.</p>
            </div>
            @if ($isClassTeacherView)
                <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">Class Teacher View</span>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('teacher.results.class') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <x-input-label for="session" value="Session" />
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="class_id" value="Class" />
                        <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @if($classes->isEmpty())
                                <option value="">No class assigned</option>
                            @endif
                            @foreach($classes as $classRoom)
                                <option value="{{ $classRoom->id }}" @selected((int) $selectedClassId === (int) $classRoom->id)>
                                    {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                    @if((int) ($classRoom->class_teacher_id ?? 0) === (int) (auth()->user()?->teacherProfile?->id ?? 0))
                                        (Class Teacher)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="exam_type" value="Exam Type" />
                        <select id="exam_type" name="exam_type" class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($examTypes as $examType)
                                <option value="{{ $examType['value'] }}" @selected($selectedExamType === $examType['value'])>{{ $examType['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Load Results
                        </button>
                    </div>
                </form>
            </div>

            @if ($message)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $message }}
                </div>
            @endif

            @if ($usesGradeSystem)
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                    The selected exam scope is configured for grade-based assessment. Numeric totals, percentages, and rankings are hidden.
                </div>
            @endif

            <div x-data="{ tab: '{{ $isClassTeacherView ? 'class' : 'subject' }}' }" class="space-y-4">
                <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                    <button
                        type="button"
                        @click="tab = 'subject'"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold"
                        :class="tab === 'subject' ? 'bg-white text-slate-900 shadow' : 'text-slate-600'"
                    >
                        My Subject Results
                    </button>
                    @if ($isClassTeacherView)
                        <button
                            type="button"
                            @click="tab = 'class'"
                            class="rounded-md px-3 py-1.5 text-xs font-semibold"
                            :class="tab === 'class' ? 'bg-white text-slate-900 shadow' : 'text-slate-600'"
                        >
                            My Class Results
                        </button>
                    @endif
                </div>

                <div x-show="tab === 'subject'" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-ui.card title="Subjects" subtitle="Visible in this tab">
                            <p class="text-2xl font-semibold text-slate-900">{{ count($mySubjectResults['subjects'] ?? []) }}</p>
                        </x-ui.card>
                        <x-ui.card title="Result Rows" subtitle="{{ $usesGradeSystem ? 'Students with graded entries' : 'Students with marks' }}">
                            <p class="text-2xl font-semibold text-slate-900">{{ (int) ($mySubjectResults['total_rows'] ?? 0) }}</p>
                        </x-ui.card>
                        <x-ui.card title="Access Scope" subtitle="Edit only your taught subjects">
                            <p class="text-sm font-medium text-slate-700">{{ $usesGradeSystem ? 'Grade Entry Access' : 'Subject Teacher Access' }}</p>
                        </x-ui.card>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-[980px] divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Student</th>
                                        @if ($usesGradeSystem)
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Grade</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Description</th>
                                        @else
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Obtained</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Total</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">%</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Grade</th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @forelse(($mySubjectResults['rows'] ?? []) as $row)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-800">{{ $row['subject_name'] }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-800">
                                                <div class="font-medium">{{ $row['student_name'] }}</div>
                                                <div class="text-xs text-slate-500">{{ $row['student_id'] }}</div>
                                            </td>
                                            @if ($usesGradeSystem)
                                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $row['grade'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['grade_label'] ?? '-' }}</td>
                                            @else
                                                <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['obtained_marks'], 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['total_marks'], 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['percentage'], 2) }}%</td>
                                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $row['grade'] }}</td>
                                            @endif
                                            <td class="px-4 py-3 text-sm">
                                                @if($row['can_edit'])
                                                    <a href="{{ $row['edit_url'] }}" class="inline-flex min-h-9 items-center rounded-md border border-emerald-300 px-3 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                                                        Edit in Entry Screen
                                                    </a>
                                                @else
                                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">View Only</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $usesGradeSystem ? 5 : 7 }}" class="px-4 py-8 text-center text-sm text-slate-500">No result records found for selected filters.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if ($isClassTeacherView)
                    <div x-show="tab === 'class'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <x-ui.card title="Class Teacher Scope" subtitle="All class subjects visible">
                                <p class="text-sm font-medium text-indigo-700">Class Teacher View</p>
                            </x-ui.card>
                            <x-ui.card title="Class Subjects" subtitle="Subjects in selected class">
                                <p class="text-2xl font-semibold text-slate-900">{{ count($classResults['subjects'] ?? []) }}</p>
                            </x-ui.card>
                            <x-ui.card title="Result Rows" subtitle="{{ $usesGradeSystem ? 'Students with graded entries' : 'Students with marks' }}">
                                <p class="text-2xl font-semibold text-slate-900">{{ (int) ($classResults['total_rows'] ?? 0) }}</p>
                            </x-ui.card>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="min-w-[980px] divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Subject</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Student</th>
                                            @if ($usesGradeSystem)
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Grade</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Description</th>
                                            @else
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Obtained</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Total</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">%</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Grade</th>
                                            @endif
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white">
                                        @forelse(($classResults['rows'] ?? []) as $row)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-slate-800">{{ $row['subject_name'] }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-800">
                                                    <div class="font-medium">{{ $row['student_name'] }}</div>
                                                    <div class="text-xs text-slate-500">{{ $row['student_id'] }}</div>
                                                </td>
                                                @if ($usesGradeSystem)
                                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $row['grade'] ?? '-' }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $row['grade_label'] ?? '-' }}</td>
                                                @else
                                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['obtained_marks'], 2) }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['total_marks'], 2) }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['percentage'], 2) }}%</td>
                                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $row['grade'] }}</td>
                                                @endif
                                                <td class="px-4 py-3 text-sm">
                                                    @if($row['can_edit'])
                                                        <a href="{{ $row['edit_url'] }}" class="inline-flex min-h-9 items-center rounded-md border border-emerald-300 px-3 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                                                            Edit in Entry Screen
                                                        </a>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">View Only</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $usesGradeSystem ? 5 : 7 }}" class="px-4 py-8 text-center text-sm text-slate-500">No class result records found for selected filters.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
