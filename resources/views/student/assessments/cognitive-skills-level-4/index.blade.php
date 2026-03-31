<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cognitive Skills Assessment Test Level 4
        </h2>
    </x-slot>

    @php
        $classLabel = trim((string) (($student?->classRoom?->name ?? '').' '.($student?->classRoom?->section ?? '')));
        $totalDurationSeconds = $assessment ? (int) $assessment->sections->sum('duration_seconds') : 0;
        $totalDurationMinutes = (int) round($totalDurationSeconds / 60);
        $totalQuestions = $assessment ? (int) $assessment->sections->sum(fn ($section) => $section->resolvedQuestions()->count()) : 0;
        $totalMarks = $assessment ? (int) $assessment->sections->sum(fn ($section) => $section->resolvedQuestions()->sum('marks')) : 0;
        $attemptStatus = match ($attempt?->status) {
            'in_progress' => 'In Progress',
            'graded' => 'Completed',
            'auto_submitted' => 'Auto Submitted',
            'submitted' => 'Submitted',
            'reset' => 'Reset - Ready to Retake',
            default => 'Not Started',
        };
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-slate-900 via-sky-900 to-cyan-800 px-6 py-8 text-white">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-cyan-200">Internal School Assessment</p>
                    <h1 class="mt-3 text-3xl font-semibold">{{ $assessment?->title ?? 'Cognitive Skills Assessment Test Level 4' }}</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-200">
                        {{ $assessment?->description ?? 'Internal cognitive assessment for Grades 8 to 12.' }}
                    </p>
                </div>

                <div class="grid gap-6 px-6 py-6 lg:grid-cols-[1.7fr_1fr]">
                    <div class="space-y-6">
                        @if ($message)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                {{ $message }}
                            </div>
                        @endif

                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-lg font-semibold text-slate-900">Assessment Instructions</h3>
                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div class="rounded-2xl border border-white bg-white p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Assessment Format</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Objective questions only</p>
                                    <p class="mt-2 text-sm text-slate-600">Every answer is checked automatically against the configured correct answer.</p>
                                </div>
                                <div class="rounded-2xl border border-white bg-white p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Timer Rule</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Backend enforced</p>
                                    <p class="mt-2 text-sm text-slate-600">The frontend timer is only for display. Late submissions are auto-submitted by the system.</p>
                                </div>
                                <div class="rounded-2xl border border-white bg-white p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Eligibility</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">Grades 8 to 12 only</p>
                                    <p class="mt-2 text-sm text-slate-600">Current class: {{ $classLabel !== '' ? $classLabel : '-' }}</p>
                                </div>
                                <div class="rounded-2xl border border-white bg-white p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Assessment Scope</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">4 reasoning sections</p>
                                    <p class="mt-2 text-sm text-slate-600">Verbal, Non-Verbal, Quantitative, and Spatial reasoning.</p>
                                </div>
                            </div>
                        </div>

                        @if ($assessment)
                            <div class="rounded-3xl border border-slate-200 bg-white p-5">
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-lg font-semibold text-slate-900">Assessment Structure</h3>
                                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                        {{ $assessment->sections->count() }} Sections
                                    </span>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    @foreach ($assessment->sections as $section)
                                        <div class="rounded-2xl border border-slate-200 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ str_replace('_', ' ', $section->skill) }}</p>
                                            <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ $section->title }}</h4>
                                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                                <div>
                                                    <p class="text-xs uppercase tracking-wide text-slate-500">Duration</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ (int) round($section->duration_seconds / 60) }} min</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs uppercase tracking-wide text-slate-500">Questions</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $section->resolvedQuestions()->count() }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs uppercase tracking-wide text-slate-500">Marks</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $section->resolvedQuestions()->sum('marks') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-lg font-semibold text-slate-900">Assessment Summary</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Duration</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $totalDurationMinutes }} minutes</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Questions</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $totalQuestions }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Marks</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $totalMarks }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $attemptStatus }}</p>
                                </div>
                            </div>
                        </div>

                        @if ($attempt)
                            <div class="rounded-3xl border border-slate-200 bg-white p-5">
                                <h3 class="text-lg font-semibold text-slate-900">Latest Attempt</h3>
                                <div class="mt-4 space-y-3 text-sm text-slate-600">
                                    <p><span class="font-semibold text-slate-900">Started:</span> {{ optional($attempt->started_at)->format('Y-m-d H:i') ?? 'Not started' }}</p>
                                    <p><span class="font-semibold text-slate-900">Submitted:</span> {{ optional($attempt->submitted_at)->format('Y-m-d H:i') ?? 'Pending' }}</p>
                                    <p><span class="font-semibold text-slate-900">Performance Band:</span> {{ $attempt->performance_band ?? 'Not graded yet' }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="rounded-3xl border border-sky-200 bg-sky-50 p-5">
                            <h3 class="text-lg font-semibold text-slate-900">Ready to proceed?</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Once the timer begins, the system will keep tracking your remaining time until submission or expiry.
                            </p>

                            <div class="mt-5 flex flex-col gap-3">
                                @if ($assessment && $visible)
                                    @if ($attempt?->status === 'in_progress')
                                        <a
                                            href="{{ route('student.assessments.cognitive-skills-level-4.attempt', $attempt) }}"
                                            class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                                        >
                                            Resume Attempt
                                        </a>
                                    @elseif ($attempt?->status === 'graded')
                                        <a
                                            href="{{ route('student.assessments.cognitive-skills-level-4.result', $attempt) }}"
                                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                        >
                                            View Result
                                        </a>
                                        <p class="text-xs text-slate-500">This assessment is already completed and cannot be retaken from the student panel.</p>
                                    @else
                                        <form method="POST" action="{{ route('student.assessments.cognitive-skills-level-4.start') }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex w-full items-center justify-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                                            >
                                                Start Assessment
                                            </button>
                                        </form>
                                        @if ($attempt?->status === 'reset')
                                            <p class="text-xs text-slate-500">Your earlier attempt was reset by the Principal, so you can begin a fresh attempt.</p>
                                        @endif
                                    @endif
                                @endif

                                <a
                                    href="{{ route('student.assessments.index') }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Back to Assessments
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
