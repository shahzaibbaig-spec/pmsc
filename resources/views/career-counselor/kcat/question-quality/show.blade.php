<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Question Quality Review</h2>
            <a href="{{ route('career-counselor.kcat.question-quality.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Back</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-5 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $question->section?->name }} | {{ ucfirst($question->difficulty) }}</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ $question->question_text }}</h3>
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 p-3"><p class="text-xs uppercase text-slate-500">Correct Rate</p><p class="mt-1 font-semibold">{{ $analysis['correct_rate'] }}%</p></div>
                <div class="rounded-xl border border-slate-200 p-3"><p class="text-xs uppercase text-slate-500">Attempts</p><p class="mt-1 font-semibold">{{ $analysis['times_attempted'] }}</p></div>
                <div class="rounded-xl border border-slate-200 p-3"><p class="text-xs uppercase text-slate-500">Avg Response</p><p class="mt-1 font-semibold">{{ $analysis['average_response_time'] ? $analysis['average_response_time'].'s' : '-' }}</p></div>
                <div class="rounded-xl border border-slate-200 p-3"><p class="text-xs uppercase text-slate-500">Flag</p><p class="mt-1 font-semibold">{{ $analysis['discrimination_flag'] ? str_replace('_', ' ', $analysis['discrimination_flag']) : '-' }}</p></div>
            </div>

            <div class="mt-4 space-y-2">
                @foreach ($question->options as $option)
                    <div class="rounded-xl border px-3 py-2 text-sm {{ $option->is_correct ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200' }}">
                        {{ $option->option_text }}
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">Review Action</h3>
            <form method="POST" action="{{ route('career-counselor.kcat.question-quality.review', $question) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                @csrf
                <div>
                    <label class="text-sm font-semibold text-slate-700">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                        @foreach (['pending', 'approved', 'needs_revision', 'retired'] as $status)
                            <option value="{{ $status }}">{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Difficulty Review</label>
                    <select name="difficulty_review" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                        <option value="">No change</option>
                        @foreach (['easy', 'medium', 'hard'] as $difficulty)
                            <option value="{{ $difficulty }}">{{ ucfirst($difficulty) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Clarity Score (1-5)</label>
                    <input type="number" min="1" max="5" name="clarity_score" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Quality Score (1-5)</label>
                    <input type="number" min="1" max="5" name="quality_score" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Issue Notes</label>
                    <textarea name="issue_notes" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Action Taken</label>
                    <input name="action_taken" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" placeholder="Example: revise distractors and simplify wording">
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save Review</button>
                </div>
            </form>

            <div class="mt-4 flex gap-2">
                <form method="POST" action="{{ route('career-counselor.kcat.question-quality.approve', $question) }}">@csrf<button class="rounded-xl border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700">Approve</button></form>
                <form method="POST" action="{{ route('career-counselor.kcat.question-quality.retire', $question) }}">@csrf<button class="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700">Retire</button></form>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">Review History</h3>
            <div class="mt-4 space-y-3">
                @forelse ($question->reviews as $review)
                    <article class="rounded-xl border border-slate-200 p-3 text-sm">
                        <p class="font-semibold text-slate-800">{{ str_replace('_', ' ', ucfirst($review->status)) }} by {{ $review->reviewer?->name ?? 'System' }}</p>
                        <p class="text-xs text-slate-500">{{ optional($review->reviewed_at)->format('d M Y H:i') }}</p>
                        <p class="mt-2 text-slate-700">{{ $review->issue_notes ?: '-' }}</p>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">No reviews yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>

