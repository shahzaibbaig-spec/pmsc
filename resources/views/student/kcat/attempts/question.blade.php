<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">{{ $attempt->test?->title }}</h2></x-slot>

    <div class="mx-auto max-w-4xl py-8">
        @if ($question)
            <form method="POST" action="{{ route('student.kcat.attempts.answer', [$attempt, $question]) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                <p class="text-sm text-slate-500">Question {{ $index + 1 }} of {{ $questions->count() }}</p>
                <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ $question->question_text }}</h3>
                <div class="mt-5 space-y-3">
                    @foreach ($question->options as $option)
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm">
                            <input type="radio" name="selected_option_id" value="{{ $option->id }}" class="text-blue-600">
                            <span>{{ $option->option_text }}</span>
                        </label>
                    @endforeach
                </div>
                <input type="hidden" name="next_index" value="{{ $index + 1 }}">
                <div class="mt-6 flex justify-between">
                    @if ($index > 0)
                        <a href="{{ route('student.kcat.attempts.question', [$attempt, $index - 1]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Previous</a>
                    @else
                        <span></span>
                    @endif
                    <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">{{ $index + 1 >= $questions->count() ? 'Save Answer' : 'Next' }}</button>
                </div>
            </form>
            @if ($index + 1 >= $questions->count())
                <form method="POST" action="{{ route('student.kcat.attempts.submit', $attempt) }}" class="mt-4 text-right">@csrf<button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Submit KCAT</button></form>
            @endif
        @else
            <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">No questions are available for this KCAT.</div>
        @endif
    </div>
</x-app-layout>
