@php
    $visibleAttempts = collect($attempts->items());
    $gradedAttempts = $visibleAttempts->where('status', 'graded');
    $averagePercentage = $gradedAttempts->isNotEmpty() ? round((float) $gradedAttempts->avg('overall_percentage'), 2) : 0;
    $profileSummary = $profile_summary ?? null;
@endphp

<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">{{ $panelTitle }} Reports</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $assessment->title }}</h3>
                    <p class="mt-2 text-sm text-slate-600">Review student performance by class, student, and submission date.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @can('manage_student_cognitive_assessment_access')
                        <a
                            href="{{ route('principal.assessments.cognitive-skills-level-4.students.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100"
                        >
                            Assessment Access
                        </a>
                    @endcan
                    @canany(['manage_cognitive_question_banks', 'manage_cognitive_assessment_setup'])
                        <a
                            href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:bg-sky-100"
                        >
                            Open Setup
                        </a>
                    @endcanany
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Attempts Found</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">{{ $attempts->total() }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Graded on Page</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">{{ $gradedAttempts->count() }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Average %</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">{{ number_format((float) $averagePercentage, 2) }}%</p>
                    </div>
                    @if ($profileSummary)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Profiles on Page</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $profileSummary['completed_on_page'] ?? 0 }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route($indexRouteName) }}" class="grid gap-4 md:grid-cols-5">
                <div>
                    <label for="class_id" class="block text-sm font-medium text-slate-700">Class</label>
                    <select id="class_id" name="class_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">All classes</option>
                        @foreach ($classes as $classRoom)
                            @php
                                $label = trim((string) ($classRoom->name.' '.($classRoom->section ?? '')));
                            @endphp
                            <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="student_id" class="block text-sm font-medium text-slate-700">Student</label>
                    <select id="student_id" name="student_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">All students</option>
                        @foreach ($students as $studentOption)
                            <option value="{{ $studentOption->id }}" @selected((string) $filters['student_id'] === (string) $studentOption->id)>
                                {{ $studentOption->name }} ({{ $studentOption->student_id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-medium text-slate-700">Date From</label>
                    <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-slate-700">Date To</label>
                    <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Apply Filters
                    </button>
                    <a href="{{ route($indexRouteName) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Student</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Class</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Attempt Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Verbal</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Non-Verbal</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Quantitative</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Spatial</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Overall %</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Band</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-600"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($attempts as $row)
                            @php
                                $rowClass = trim((string) (($row->student?->classRoom?->name ?? '').' '.($row->student?->classRoom?->section ?? '')));
                                $attemptDate = optional($row->submitted_at ?: $row->created_at)->format('Y-m-d');
                                $statusClass = match ($row->status) {
                                    'graded' => 'bg-emerald-100 text-emerald-700',
                                    'in_progress' => 'bg-amber-100 text-amber-700',
                                    'auto_submitted' => 'bg-rose-100 text-rose-700',
                                    'reset' => 'bg-indigo-100 text-indigo-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $row->student?->name ?? 'Student' }}</p>
                                    <p class="text-xs text-slate-500">{{ $row->student?->student_id ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $rowClass !== '' ? $rowClass : '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $attemptDate ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->verbal_score ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->non_verbal_score ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->quantitative_score ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->spatial_score ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->overall_percentage !== null ? number_format((float) $row->overall_percentage, 2).'%' : '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->performance_band ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $row->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route($showRouteName, $row) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                        View Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No assessment attempts matched the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $attempts->links() }}
        </div>
    </div>
</div>
