<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $bank->title }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Question Bank</p>
                        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $bank->title }}</h1>
                        <p class="mt-2 text-sm text-slate-600">{{ $bank->description ?: 'Reusable objective questions for Cognitive Skills Assessment Test Level 4.' }}</p>
                        <p class="mt-3 text-xs text-slate-500">Slug: {{ $bank->slug }} • Created by {{ $bank->creator?->name ?? 'System' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.index') }}"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Back to Banks
                        </a>
                        <a
                            href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.questions.create', $bank) }}"
                            class="inline-flex items-center rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700"
                        >
                            Add Question
                        </a>
                    </div>
                </div>
            </div>

            @if ($assessmentSections->isNotEmpty())
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Assign Questions to Assessment Sections</h3>
                            <p class="mt-1 text-sm text-slate-600">Use the section setup pages to attach matching skill questions from this bank.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($assessmentSections as $section)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $skillOptions[$section->skill] ?? ucfirst(str_replace('_', ' ', $section->skill)) }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $section->title }}</p>
                                <a
                                    href="{{ route('admin.assessments.cognitive-skills-level-4.sections.questions.edit', $section) }}"
                                    class="mt-4 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Manage Section
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Bank Questions</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $bank->bankQuestions->count() }} questions available in this bank.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $bank->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                            {{ $bank->is_active ? 'Active Bank' : 'Inactive Bank' }}
                        </span>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($bank->bankQuestions as $question)
                        <div class="px-6 py-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ strtoupper($question->skill) }}</span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ strtoupper($question->question_type) }}</span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $question->marks }} mark{{ $question->marks > 1 ? 's' : '' }}</span>
                                    </div>
                                    <p class="text-base font-semibold text-slate-900">{{ $question->question_text ?: 'Image-based question' }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach (($question->options ?? []) as $option)
                                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700">{{ $option }}</span>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-slate-500">Correct answer: {{ $question->correct_answer }}</p>
                                    @if ($question->question_image_url)
                                        <img
                                            src="{{ $question->question_image_url }}"
                                            alt="Question illustration"
                                            class="max-h-44 rounded-2xl border border-slate-200 object-contain"
                                        >
                                    @elseif (in_array($question->question_type, $imageRecommendedTypes, true))
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                                            Image recommended for this question type. Upload one from the edit screen for matrix, pattern, shape rotation, or mirror image reasoning.
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a
                                        href="{{ route('admin.assessments.cognitive-skills-level-4.questions.edit', $question) }}"
                                        class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.assessments.cognitive-skills-level-4.questions.destroy', $question) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                            onclick="return confirm('Delete this bank question?')"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            No questions have been added to this bank yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
