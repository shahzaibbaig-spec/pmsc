<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Results Gazette</h2>
                <p class="mt-1 text-sm text-slate-500">Class-wise result gazette for the latest exam session.</p>
            </div>
            <a
                href="{{ route('principal.results.tabulation', ['class_id' => $filters['class_id'], 'session' => $filters['session']]) }}"
                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Open Tabulation Sheet
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errorMessage)
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errorMessage }}
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('principal.results.gazette') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="class_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select
                        id="class_id"
                        name="class_id"
                        required
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</label>
                    <select
                        id="session"
                        name="session"
                        required
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected((string) $filters['session'] === (string) $session)>
                                {{ $session }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2 md:col-span-2">
                    <x-ui.button type="submit">Apply Filters</x-ui.button>
                    @if($report)
                        <a
                            href="{{ route('reports.pdf.gazette', ['class_id' => $filters['class_id'], 'session' => $filters['session']]) }}"
                            target="_blank"
                            class="inline-flex min-h-11 items-center rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                        >
                            Export Gazette PDF
                        </a>
                    @endif
                </div>
            </form>
        </section>

        @if($report)
            @php
                $usesGradeSystem = (bool) ($report['uses_grade_system'] ?? false);
            @endphp

            @if ($usesGradeSystem)
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                    This class uses grade-based assessment only. Positions, totals, and percentages are not calculated.
                </div>
            @endif

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Class</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $report['class']['name'] }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Session</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $report['exam']['session'] }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Type</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $report['exam']['exam_type_label'] }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Students</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $report['summary']['students_count'] }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $usesGradeSystem ? 'Assessment Mode' : 'Class Average' }}</p>
                    <p class="mt-2 text-2xl font-semibold {{ $usesGradeSystem ? 'text-indigo-700' : 'text-indigo-700' }}">
                        {{ $usesGradeSystem ? 'Grade-based' : number_format((float) $report['summary']['class_average_percentage'], 2).'%' }}
                    </p>
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                @unless ($usesGradeSystem)
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pos</th>
                                @endunless
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                @foreach($report['subjects'] as $subject)
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {{ $subject['name'] }}
                                        @unless ($usesGradeSystem)
                                            <div class="text-[10px] font-normal text-slate-400">/{{ (int) $subject['total_marks'] }}</div>
                                        @endunless
                                    </th>
                                @endforeach
                                @if ($usesGradeSystem)
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Overall Grade</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                                @else
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Obtained</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">%</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Grade</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($report['rows'] as $row)
                                <tr>
                                    @unless ($usesGradeSystem)
                                        <td class="px-3 py-2 text-sm font-semibold text-slate-900">{{ (int) $row['position'] }}</td>
                                    @endunless
                                    <td class="px-3 py-2 text-sm text-slate-700">
                                        <div class="font-semibold text-slate-900">{{ $row['student_name'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $row['student_code'] }}</div>
                                    </td>
                                    @foreach($report['subjects'] as $subject)
                                        @php
                                            $subjectId = (int) $subject['id'];
                                            $subjectMark = $row['subject_marks'][$subjectId] ?? ($usesGradeSystem
                                                ? ['grade' => null, 'label' => null]
                                                : ['obtained' => 0, 'total' => (int) $subject['total_marks']]);
                                        @endphp
                                        <td class="px-3 py-2 text-sm text-slate-700">
                                            @if ($usesGradeSystem)
                                                <div class="font-semibold text-slate-900">{{ $subjectMark['grade'] ?? '-' }}</div>
                                                @if (!empty($subjectMark['label']))
                                                    <div class="text-xs text-slate-500">{{ $subjectMark['label'] }}</div>
                                                @endif
                                            @else
                                                {{ (int) $subjectMark['obtained'] }}
                                            @endif
                                        </td>
                                    @endforeach
                                    @if ($usesGradeSystem)
                                        <td class="px-3 py-2 text-sm font-semibold text-slate-900">{{ $row['grade'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-slate-700">{{ $row['grade_label'] ?? '-' }}</td>
                                    @else
                                        <td class="px-3 py-2 text-sm font-semibold text-slate-900">{{ (int) $row['total_marks'] }}</td>
                                        <td class="px-3 py-2 text-sm font-semibold text-indigo-700">{{ (int) $row['obtained_marks'] }}</td>
                                        <td class="px-3 py-2 text-sm text-slate-700">{{ number_format((float) $row['percentage'], 2) }}</td>
                                        <td class="px-3 py-2 text-sm font-semibold text-slate-900">{{ $row['grade'] }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
