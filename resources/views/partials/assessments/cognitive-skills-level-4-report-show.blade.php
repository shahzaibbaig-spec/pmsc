@php
    $report = $profileReport;
    $studentRecord = $attempt->student;
    $classLabel = trim((string) (($studentRecord?->classRoom?->name ?? '').' '.($studentRecord?->classRoom?->section ?? '')));
    $summary = $report['summary'] ?? null;
    $interpretation = $report['interpretation'] ?? [];
    $pathway = $report['pathway'] ?? [];
    $reviewSections = $report['review_sections'] ?? [];
@endphp

<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">{{ $panelTitle }} View</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Cognitive Profile Report</h3>
                    <p class="mt-2 text-sm text-slate-600">Internal reasoning skills report for Cognitive Skills Assessment Test Level 4. This profile is for school guidance only and is not an official CAT4 product.</p>
                </div>
                <a href="{{ route($backRouteName) }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Back
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Student Details</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Student</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $report['student']['name'] ?? ($studentRecord?->name ?? 'Student') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $report['student']['student_id'] ?? ($studentRecord?->student_id ?? '-') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Class</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $classLabel !== '' ? $classLabel : '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Assessment Date</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $report['attempt']['assessment_date'] ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Attempt</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">#{{ $report['attempt']['id'] ?? $attempt->id }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $report['attempt']['status'] ?? $attempt->status)) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Overall Outcome</h3>
                @if ($summary)
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Overall Score</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['overall_score'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-emerald-700">Overall Percentage</p>
                            <p class="mt-2 text-2xl font-semibold text-emerald-900">{{ number_format((float) $summary['overall_percentage'], 2) }}%</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Performance Band</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ $summary['performance_band'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Reasoning Area Scores</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">
                                V {{ $summary['verbal_score'] }},
                                NV {{ $summary['non_verbal_score'] }},
                                S {{ $summary['spatial_score'] }},
                                Q {{ $summary['quantitative_score'] }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        This attempt has not completed scoring yet, so the cognitive profile report is still pending.
                    </div>
                @endif
            </div>
        </div>

        @if ($summary)
            <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Internal Reasoning Skills Report</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">Interpretation</h3>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                            Internal Use
                        </span>
                    </div>

                    <div class="mt-5 rounded-2xl border border-sky-100 bg-sky-50 p-5">
                        <p class="text-sm leading-7 text-slate-700">{{ $interpretation['summary_paragraph'] ?? 'Interpretation will appear after scoring.' }}</p>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Strengths</h4>
                            @if (!empty($interpretation['strengths']))
                                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                                    @foreach ($interpretation['strengths'] as $strength)
                                        <li class="rounded-xl bg-white/80 px-3 py-2">{{ $strength }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-3 text-sm text-slate-600">No clear strengths are available until the assessment is fully scored.</p>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.22em] text-amber-700">Development Areas</h4>
                            @if (!empty($interpretation['development_areas']))
                                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                                    @foreach ($interpretation['development_areas'] as $area)
                                        <li class="rounded-xl bg-white/80 px-3 py-2">{{ $area }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-3 text-sm text-slate-600">No major development area stands out from this current internal profile.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-indigo-700">{{ $pathway['support_direction'] ?? 'Suggested academic support direction' }}</p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900">Pathway Recommendation</h3>
                    <div class="mt-5 rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                        <p class="text-lg font-semibold text-indigo-900">{{ $pathway['pathway'] ?? 'Pending' }}</p>
                        <p class="mt-3 text-sm leading-7 text-slate-700">{{ $pathway['text'] ?? 'The pathway recommendation will appear after the profile is scored.' }}</p>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-slate-500">
                        This recommendation is a school support suggestion only. It should be used with classroom performance, teacher observation, and pastoral guidance rather than as a fixed placement decision.
                    </p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Section-wise Scores</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach (($report['sections'] ?? []) as $section)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ $section['title'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $section['awarded_marks'] }} / {{ $section['available_marks'] }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ $section['correct_count'] }} correct • {{ number_format((float) $section['percentage'], 2) }}%</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @foreach ($reviewSections as $section)
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">{{ str_replace('_', ' ', $section['skill']) }}</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ $section['title'] }}</h3>
                        </div>
                        <div class="text-xs font-semibold text-slate-500">
                            {{ (int) round($section['duration_seconds'] / 60) }} min • {{ $section['total_marks'] }} marks
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Question</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Selected Answer</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Correct Answer</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Result</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Marks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($section['rows'] as $row)
                                @php
                                    $resultClass = match (true) {
                                        $row['is_correct'] === true => 'bg-emerald-100 text-emerald-700',
                                        $row['is_correct'] === false => 'bg-rose-100 text-rose-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                    $resultText = match (true) {
                                        $row['is_correct'] === true => 'Correct',
                                        $row['is_correct'] === false => 'Incorrect',
                                        default => 'Pending',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-900">{{ $row['question_text'] }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs uppercase tracking-wide text-slate-500">
                                            <span>{{ $row['question_type'] }}</span>
                                            @if (!empty($row['question_bank_title']))
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 normal-case tracking-normal text-slate-600">{{ $row['question_bank_title'] }}</span>
                                            @endif
                                        </div>
                                        @if (!empty($row['question_image_url']))
                                            <div class="mt-3">
                                                <img
                                                    src="{{ $row['question_image_url'] }}"
                                                    alt="Question illustration"
                                                    class="max-h-40 rounded-2xl border border-slate-200 object-contain"
                                                >
                                            </div>
                                        @endif
                                        @if (!empty($row['explanation']))
                                            <p class="mt-3 rounded-2xl border border-sky-100 bg-sky-50 px-3 py-2 text-xs normal-case tracking-normal text-sky-800">
                                                Explanation: {{ $row['explanation'] }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row['selected_answer'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row['correct_answer'] ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $resultClass }}">
                                            {{ $resultText }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $row['awarded_marks'] ?? 0 }} / {{ $row['available_marks'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
