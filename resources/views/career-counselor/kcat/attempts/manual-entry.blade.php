<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Manual KCAT Entry</h2></x-slot>

    <div class="mx-auto max-w-6xl py-8">
        <form method="POST" action="{{ route('career-counselor.kcat.attempts.manual-entry.store', $test) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label class="text-sm font-semibold text-slate-700">Student ID</label>
                <input type="number" name="student_id" value="{{ old('student_id') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
            </div>

            @foreach ($test->sections as $section)
                <section class="rounded-xl border border-blue-100 p-4">
                    <h3 class="font-semibold text-slate-900">{{ $section->name }}</h3>
                    <div class="mt-4 space-y-4">
                        @foreach ($section->questions as $question)
                            <div>
                                <p class="whitespace-pre-line text-sm font-semibold text-slate-800">{{ $question->question_text }}</p>
                                @include('kcat.partials.question-visual', ['question' => $question])
                                <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
                                    @foreach ($question->options as $option)
                                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                            <input type="radio" name="answers[{{ $question->id }}][selected_option_id]" value="{{ $option->id }}" class="text-blue-600">
                                            @include('kcat.partials.option-visual', ['option' => $option, 'questionType' => $question->question_type])
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="flex justify-end"><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save and Score</button></div>
        </form>
    </div>
</x-app-layout>
