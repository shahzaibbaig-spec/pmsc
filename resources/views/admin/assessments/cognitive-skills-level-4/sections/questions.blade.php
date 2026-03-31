<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $section->title }} Question Assignment
        </h2>
    </x-slot>

    @php
        $selectedQuestionIds = collect(old('bank_question_ids', $selectedIds ?? []))
            ->map(fn ($id): int => (int) $id)
            ->all();
        $currentSortOrders = old('sort_orders', $sortOrders ?? []);
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
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
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Assessment Section Setup</p>
                        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $section->title }}</h1>
                        <p class="mt-2 text-sm text-slate-600">{{ $skillOptions[$section->skill] ?? ucfirst(str_replace('_', ' ', $section->skill)) }} questions only.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.index') }}"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Back to Setup
                        </a>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.assessments.cognitive-skills-level-4.sections.questions.update', $section) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <h3 class="text-lg font-semibold text-slate-900">Available Bank Questions</h3>
                        <p class="mt-1 text-sm text-slate-600">Select active questions matching this section skill. Lower sort order values appear first.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($availableQuestions as $question)
                            @php
                                $isSelected = in_array((int) $question->id, $selectedQuestionIds, true);
                                $resolvedSort = $currentSortOrders[(string) $question->id] ?? ($question->pivot->sort_order ?? ($loop->iteration));
                            @endphp
                            <div class="px-6 py-5">
                                <div class="grid gap-4 lg:grid-cols-[auto_1fr_auto]">
                                    <div class="pt-1">
                                        <input
                                            id="bank-question-{{ $question->id }}"
                                            type="checkbox"
                                            name="bank_question_ids[]"
                                            value="{{ $question->id }}"
                                            @checked($isSelected)
                                            class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                        >
                                    </div>

                                    <label for="bank-question-{{ $question->id }}" class="block space-y-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ strtoupper($question->question_type) }}</span>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $question->questionBank?->title }}</span>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $question->marks }} mark{{ $question->marks > 1 ? 's' : '' }}</span>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $question->question_text ?: 'Image-based question' }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach (($question->options ?? []) as $option)
                                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700">{{ $option }}</span>
                                            @endforeach
                                        </div>
                                        @if ($question->question_image_url)
                                            <img
                                                src="{{ $question->question_image_url }}"
                                                alt="Question illustration"
                                                class="max-h-40 rounded-2xl border border-slate-200 object-contain"
                                            >
                                        @endif
                                    </label>

                                    <div class="space-y-3">
                                        <div>
                                            <label for="sort-order-{{ $question->id }}" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Sort Order</label>
                                            <input
                                                id="sort-order-{{ $question->id }}"
                                                type="number"
                                                name="sort_orders[{{ $question->id }}]"
                                                value="{{ $resolvedSort }}"
                                                class="mt-1 block w-28 rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                            >
                                        </div>

                                        @if ($isSelected)
                                            <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                                Assigned now. Uncheck it and save the section to remove it.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-10 text-center text-sm text-slate-500">
                                No active bank questions match this section skill yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                    >
                        Save Section Questions
                    </button>
                    <a
                        href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.index') }}"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Back to Setup
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
