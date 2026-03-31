<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Assessments
        </h2>
    </x-slot>

    @php
        $classLabel = trim((string) (($student?->classRoom?->name ?? '').' '.($student?->classRoom?->section ?? '')));
        $durationMinutes = $assessment ? (int) round($assessment->sections->sum('duration_seconds') / 60) : 0;
        $questionCount = $assessment ? (int) $assessment->sections->sum(fn ($section) => $section->resolvedQuestions()->count()) : 0;
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
        <div class="max-w-6xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
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

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Student Assessments</p>
                <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900">Internal Assessment Center</h3>
                        <p class="mt-2 max-w-2xl text-sm text-slate-600">
                            Objective assessments for internal school evaluation with backend-enforced timing and instant auto-checking.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <p><span class="font-semibold text-slate-900">Student:</span> {{ $student?->name ?? 'Not linked' }}</p>
                        <p class="mt-1"><span class="font-semibold text-slate-900">Class:</span> {{ $classLabel !== '' ? $classLabel : '-' }}</p>
                    </div>
                </div>
            </div>

            @if ($message)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $message }}
                </div>
            @endif

            @if ($assessment && $visible)
                <div class="rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-cyan-50 p-6 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Available Assessment</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $assessment->title }}</h3>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                                {{ $assessment->description ?: 'Internal cognitive assessment for Grades 8 to 12.' }}
                            </p>
                        </div>
                        <div class="inline-flex items-center rounded-full bg-white px-4 py-2 text-xs font-semibold text-sky-700 shadow-sm ring-1 ring-sky-100">
                            {{ $attemptStatus }}
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-4">
                        <div class="rounded-2xl border border-white/80 bg-white/80 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Sections</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $assessment->sections->count() }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/80 bg-white/80 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Questions</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $questionCount }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/80 bg-white/80 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Duration</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $durationMinutes }} min</p>
                        </div>
                        <div class="rounded-2xl border border-white/80 bg-white/80 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Checking</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">Fully Auto-Checked</p>
                        </div>
                    </div>

                    @if ($attempt)
                        <div class="mt-6 grid gap-4 md:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Started At</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($attempt->started_at)->format('Y-m-d H:i') ?? 'Not started yet' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Submitted At</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($attempt->submitted_at)->format('Y-m-d H:i') ?? 'Pending' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Performance Band</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $attempt->performance_band ?? 'Not graded yet' }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('student.assessments.cognitive-skills-level-4.index') }}"
                            class="inline-flex items-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700"
                        >
                            Open Assessment
                        </a>

                        @if ($attempt?->status === 'graded')
                            <a
                                href="{{ route('student.assessments.cognitive-skills-level-4.result', $attempt) }}"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                View Latest Result
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">No assessment is available in your panel right now</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Cognitive Skills Assessment Test Level 4 appears only after the Principal enables it for an eligible student in Grades 8, 9, 10, 11, or 12.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
