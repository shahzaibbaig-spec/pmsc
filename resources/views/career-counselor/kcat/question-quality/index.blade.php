<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-slate-900">KCAT Question Quality</h2>
            <a href="{{ route('career-counselor.kcat.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Back to KCAT</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach (['pending', 'approved', 'needs_revision', 'retired'] as $status)
                <article class="rounded-2xl border border-blue-100 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ str_replace('_', ' ', $status) }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary[$status] ?? 0) }}</p>
                </article>
            @endforeach
        </section>

        <form method="GET" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-6">
            <select name="section_id" class="rounded-xl border-slate-300 text-sm">
                <option value="">All Sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(($filters['section_id'] ?? '') == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
            <select name="difficulty" class="rounded-xl border-slate-300 text-sm">
                <option value="">All Difficulty</option>
                @foreach (['easy', 'medium', 'hard'] as $difficulty)
                    <option value="{{ $difficulty }}" @selected(($filters['difficulty'] ?? '') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                @endforeach
            </select>
            <select name="review_status" class="rounded-xl border-slate-300 text-sm">
                <option value="">All Review Status</option>
                @foreach (['pending', 'approved', 'needs_revision', 'retired'] as $status)
                    <option value="{{ $status }}" @selected(($filters['review_status'] ?? '') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
            <select name="discrimination_flag" class="rounded-xl border-slate-300 text-sm">
                <option value="">All Flags</option>
                @foreach (['insufficient_data', 'too_easy', 'too_hard', 'confusing'] as $flag)
                    <option value="{{ $flag }}" @selected(($filters['discrimination_flag'] ?? '') === $flag)>{{ str_replace('_', ' ', ucfirst($flag)) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input type="checkbox" name="only_flagged" value="1" class="rounded border-slate-300 text-blue-600" @checked(($filters['only_flagged'] ?? '') === '1')>
                Flagged only
            </label>
            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-blue-50 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">
                    <tr>
                        <th class="px-4 py-3">Question</th>
                        <th class="px-4 py-3">Section</th>
                        <th class="px-4 py-3">Difficulty</th>
                        <th class="px-4 py-3">Correct Rate</th>
                        <th class="px-4 py-3">Attempts</th>
                        <th class="px-4 py-3">Avg Time</th>
                        <th class="px-4 py-3">Flag</th>
                        <th class="px-4 py-3">Review</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($questions as $question)
                        @php
                            $rate = $question->times_attempted > 0 ? round(($question->times_correct / $question->times_attempted) * 100, 2) : 0;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-slate-700">{{ \Illuminate\Support\Str::limit($question->question_text, 90) }}</td>
                            <td class="px-4 py-3">{{ $question->section?->name }}</td>
                            <td class="px-4 py-3">{{ ucfirst($question->difficulty) }}</td>
                            <td class="px-4 py-3">{{ $rate }}%</td>
                            <td class="px-4 py-3">{{ $question->times_attempted }}</td>
                            <td class="px-4 py-3">{{ $question->average_response_time ? $question->average_response_time.'s' : '-' }}</td>
                            <td class="px-4 py-3">{{ $question->discrimination_flag ? str_replace('_', ' ', $question->discrimination_flag) : '-' }}</td>
                            <td class="px-4 py-3">{{ str_replace('_', ' ', $question->review_status) }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('career-counselor.kcat.question-quality.show', $question) }}" class="font-semibold text-blue-700">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-6 text-center text-slate-500">No KCAT questions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $questions->links() }}</div>
    </div>
</x-app-layout>

