<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">{{ $attempt->test?->title }} - Adaptive KCAT</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-4 py-8">
        <section class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Section</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $question->section?->name }}</p>
                    <p class="text-xs text-slate-500">{{ $sectionAnswered }} / {{ $requiredPerSection }} answered</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Overall Progress</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $totalAnswered }} / {{ $totalRequired }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Current Difficulty</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ ucfirst($attempt->current_difficulty ?? $question->difficulty) }}</p>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('student.kcat.attempts.adaptive.answer', $attempt) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <h3 class="text-lg font-semibold text-slate-900">{{ $question->question_text }}</h3>
            <div class="mt-5 space-y-3">
                @foreach ($question->options as $option)
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        <input type="radio" name="selected_option_id" value="{{ $option->id }}" class="text-blue-600">
                        <span>{{ $option->option_text }}</span>
                    </label>
                @endforeach
            </div>

            <input type="hidden" name="response_time_seconds" id="response_time_seconds" value="0">
            <div class="mt-6 flex justify-end">
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save & Next</button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const startedAt = Date.now();
            const field = document.getElementById('response_time_seconds');
            if (!field) return;
            document.querySelector('form')?.addEventListener('submit', () => {
                const seconds = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
                field.value = String(seconds);
            });
        })();
    </script>
</x-app-layout>

