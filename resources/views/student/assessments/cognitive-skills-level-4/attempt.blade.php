<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cognitive Skills Assessment Test Level 4
        </h2>
    </x-slot>

    @php
        $sectionBlocks = $attemptView['sections'] ?? [];
        $initialAnswers = $attemptView['initial_answers'] ?? [];
        $totalQuestions = (int) ($attemptView['total_questions'] ?? 0);
        $totalMarks = (int) ($attemptView['total_marks'] ?? 0);
    @endphp

    <div
        class="py-8"
        x-data="cognitiveAssessmentAttemptPage({
            initialAnswers: @js($initialAnswers),
            remainingSeconds: @js((int) $remainingSeconds),
            saveUrl: @js(route('student.assessments.cognitive-skills-level-4.responses.store', $attempt)),
            resultUrl: @js(route('student.assessments.cognitive-skills-level-4.result', $attempt)),
            csrfToken: @js(csrf_token()),
        })"
        x-init="init()"
    >
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[300px_1fr]">
                <aside class="space-y-5">
                    <div class="rounded-3xl border border-slate-200 bg-slate-900 p-5 text-white shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200">Live Timer</p>
                        <p class="mt-4 text-4xl font-semibold tabular-nums" :class="remainingSeconds <= 300 ? 'text-amber-300' : 'text-white'" x-text="formatTime(remainingSeconds)"></p>
                        <p class="mt-2 text-sm text-slate-300">The backend will auto-submit when this timer reaches zero.</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Attempt Summary</h3>
                        <div class="mt-4 space-y-4 text-sm">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500">Student</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $student->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500">Questions Answered</p>
                                <p class="mt-1 font-semibold text-slate-900"><span x-text="answeredCount()"></span> / {{ $totalQuestions }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500">Total Marks</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $totalMarks }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500">Autosave</p>
                                <p class="mt-1 font-semibold text-slate-900" x-text="saveState"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Sections</h3>
                        <div class="mt-4 space-y-3">
                            @foreach ($sectionBlocks as $section)
                                <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-500">{{ str_replace('_', ' ', $section['skill']) }}</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $section['title'] }}</p>
                                    <p class="mt-2 text-xs text-slate-500">{{ (int) round($section['duration_seconds'] / 60) }} min • {{ $section['question_count'] }} questions</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </aside>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Assessment in Progress</p>
                                <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $assessment->title }}</h1>
                                <p class="mt-2 text-sm text-slate-600">Answer every section carefully. You can save progress any time before the timer expires.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    @click="saveProgress(false)"
                                    :disabled="saving || submitting"
                                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Save Progress
                                </button>
                                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700" x-text="saveFlash"></span>
                            </div>
                        </div>
                    </div>

                    <form
                        x-ref="submitForm"
                        method="POST"
                        action="{{ route('student.assessments.cognitive-skills-level-4.submit', $attempt) }}"
                        class="space-y-6"
                        @submit="prepareManualSubmit()"
                    >
                        @csrf

                        @foreach ($sectionBlocks as $section)
                            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">{{ str_replace('_', ' ', $section['skill']) }}</p>
                                            <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ $section['title'] }}</h3>
                                        </div>
                                        <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-600">
                                            <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ (int) round($section['duration_seconds'] / 60) }} min</span>
                                            <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ $section['question_count'] }} questions</span>
                                            <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ $section['available_marks'] }} marks</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6 px-6 py-6">
                                    @foreach ($section['questions'] as $question)
                                        <div class="rounded-2xl border border-slate-200 p-5">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ strtoupper((string) $question['question_type']) }}</p>
                                                    <h4 class="mt-2 text-base font-semibold text-slate-900">
                                                        Q{{ $loop->parent->iteration }}.{{ $loop->iteration }} {{ $question['question_text'] }}
                                                    </h4>
                                                    @if (!empty($question['question_bank_title']))
                                                        <p class="mt-2 text-xs text-slate-500">Bank: {{ $question['question_bank_title'] }}</p>
                                                    @endif
                                                </div>
                                                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                                    {{ $question['marks'] }} mark{{ $question['marks'] > 1 ? 's' : '' }}
                                                </span>
                                            </div>

                                            @if (!empty($question['question_image_url']))
                                                <div class="mt-4">
                                                    <img
                                                        src="{{ $question['question_image_url'] }}"
                                                        alt="Question illustration"
                                                        class="max-h-72 rounded-2xl border border-slate-200 object-contain"
                                                    >
                                                </div>
                                            @elseif (in_array($question['question_type'], ['matrix', 'pattern', 'shape_rotation', 'mirror_image'], true))
                                                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                                    The visual prompt for this question is missing. Please contact the school administrator before continuing.
                                                </div>
                                            @endif

                                            <div class="mt-5 grid gap-3">
                                                @foreach (($question['options'] ?? []) as $option)
                                                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50">
                                                        <input
                                                            type="radio"
                                                            name="answers[{{ $question['response_key'] }}]"
                                                            value="{{ $option }}"
                                                            x-model="answers['{{ $question['response_key'] }}']"
                                                            class="mt-1 h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500"
                                                            :disabled="timedOut"
                                                        >
                                                        <span class="text-sm text-slate-700">{{ $option }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach

                        <div class="sticky bottom-4 z-20 rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Ready to submit?</p>
                                    <p class="text-xs text-slate-500">Submitted answers are locked immediately and scored automatically.</p>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        @click="saveProgress(false)"
                                        :disabled="saving || submitting"
                                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        Save Progress
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="timedOut"
                                        class="inline-flex items-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        Submit Assessment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cognitiveAssessmentAttemptPage(config) {
            return {
                answers: { ...(config.initialAnswers || {}) },
                remainingSeconds: Number(config.remainingSeconds || 0),
                saveUrl: config.saveUrl,
                resultUrl: config.resultUrl,
                csrfToken: config.csrfToken,
                saving: false,
                submitting: false,
                timedOut: false,
                saveState: 'Autosave every 45 seconds',
                saveFlash: 'Not saved yet',
                timerHandle: null,
                autosaveHandle: null,

                init() {
                    this.startTimer();
                    this.autosaveHandle = window.setInterval(() => {
                        if (!this.submitting && !this.timedOut) {
                            this.saveProgress(true);
                        }
                    }, 45000);
                },

                answeredCount() {
                    return Object.values(this.answers).filter((value) => value !== null && value !== '').length;
                },

                formatTime(totalSeconds) {
                    const seconds = Math.max(Number(totalSeconds || 0), 0);
                    const hours = Math.floor(seconds / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);
                    const remainder = seconds % 60;

                    if (hours > 0) {
                        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
                    }

                    return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
                },

                startTimer() {
                    this.timerHandle = window.setInterval(() => {
                        if (this.remainingSeconds <= 0) {
                            this.handleExpiry();
                            return;
                        }

                        this.remainingSeconds -= 1;
                        if (this.remainingSeconds <= 0) {
                            this.handleExpiry();
                        }
                    }, 1000);
                },

                async saveProgress(silent = false) {
                    if (this.saving || this.submitting || this.timedOut) {
                        return;
                    }

                    this.saving = true;
                    this.saveState = 'Saving...';

                    try {
                        const response = await fetch(this.saveUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({ answers: this.answers }),
                        });

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            this.saveState = 'Save failed';
                            this.saveFlash = payload.message || 'Unable to save progress.';
                            if (payload.redirect_url) {
                                window.location.href = payload.redirect_url;
                            }
                            return;
                        }

                        this.saveState = 'Saved';
                        this.saveFlash = silent ? `Autosaved at ${payload.saved_at || ''}`.trim() : 'Progress saved';

                        if (typeof payload.remaining_seconds === 'number') {
                            this.remainingSeconds = payload.remaining_seconds;
                        }
                    } catch (error) {
                        this.saveState = 'Save failed';
                        this.saveFlash = 'Connection problem while saving';
                    } finally {
                        this.saving = false;
                    }
                },

                prepareManualSubmit() {
                    if (this.timerHandle) {
                        window.clearInterval(this.timerHandle);
                    }

                    if (this.autosaveHandle) {
                        window.clearInterval(this.autosaveHandle);
                    }

                    this.submitting = true;
                    this.saveState = 'Submitting...';
                    this.saveFlash = 'Finishing assessment...';
                },

                handleExpiry() {
                    if (this.timedOut || this.submitting) {
                        return;
                    }

                    this.timedOut = true;
                    this.submitting = true;
                    this.saveState = 'Time expired';
                    this.saveFlash = 'Submitting automatically...';

                    if (this.timerHandle) {
                        window.clearInterval(this.timerHandle);
                    }

                    if (this.autosaveHandle) {
                        window.clearInterval(this.autosaveHandle);
                    }

                    this.$refs.submitForm.submit();
                },
            };
        }
    </script>
</x-app-layout>
