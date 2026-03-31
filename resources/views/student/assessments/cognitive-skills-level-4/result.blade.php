<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cognitive Skills Assessment Test Level 4 Result
        </h2>
    </x-slot>

    @php
        $summary = $result['summary'] ?? [];
        $attemptMeta = $result['attempt'] ?? [];
        $sectionRows = $result['sections'] ?? [];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-emerald-600 via-sky-700 to-slate-900 px-6 py-8 text-white">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200">Assessment Result</p>
                    <h1 class="mt-3 text-3xl font-semibold">{{ $assessment->title }}</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-100">
                        Student: {{ $student->name }} • Class: {{ trim((string) (($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? ''))) ?: '-' }}
                    </p>
                </div>

                <div class="space-y-6 px-6 py-6">
                    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Verbal</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['verbal_score'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Non-Verbal</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['non_verbal_score'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Quantitative</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['quantitative_score'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Spatial</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['spatial_score'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Overall</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['overall_score'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-emerald-700">Performance Band</p>
                            <p class="mt-2 text-lg font-semibold text-emerald-900">{{ $summary['performance_band'] ?? 'Not Graded' }}</p>
                            <p class="mt-1 text-sm font-semibold text-emerald-800">{{ number_format((float) ($summary['overall_percentage'] ?? 0), 2) }}%</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <h3 class="text-lg font-semibold text-slate-900">Section-wise Performance</h3>
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Section</th>
                                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Score</th>
                                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Correct</th>
                                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Answered</th>
                                            <th class="px-4 py-3 text-left font-semibold text-slate-600">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($sectionRows as $section)
                                            <tr>
                                                <td class="px-4 py-3 font-medium text-slate-900">{{ $section['title'] }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $section['awarded_marks'] }} / {{ $section['available_marks'] }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $section['correct_count'] }} / {{ $section['question_count'] }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $section['answered_count'] }} / {{ $section['question_count'] }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) $section['percentage'], 2) }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-lg font-semibold text-slate-900">Attempt Details</h3>
                            <div class="mt-4 space-y-4 text-sm">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', (string) ($attemptMeta['status'] ?? 'graded'))) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Started At</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $attemptMeta['started_at'] ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Submitted At</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $attemptMeta['submitted_at'] ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Overall Percentage</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ number_format((float) ($summary['overall_percentage'] ?? 0), 2) }}%</p>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-3">
                                <a
                                    href="{{ route('student.assessments.cognitive-skills-level-4.index') }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                                >
                                    Back to Assessment
                                </a>
                                <a
                                    href="{{ route('student.assessments.index') }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    View All Assessments
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
