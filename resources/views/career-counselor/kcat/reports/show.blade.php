<x-app-layout>
    @php($attempt = $report['attempt'])
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">KCAT Report</h2>
                <p class="text-sm text-slate-500">{{ $attempt->student?->name }} | {{ $attempt->test?->title }}</p>
            </div>
            <a href="{{ route('career-counselor.kcat.reports.print', $attempt) }}" target="_blank" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Print</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Overall Score</p><p class="mt-2 text-2xl font-semibold">{{ $attempt->percentage ?? 0 }}%</p></article>
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Band</p><p class="mt-2 text-2xl font-semibold">{{ str_replace('_', ' ', $attempt->band ?? '-') }}</p></article>
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Stream</p><p class="mt-2 text-lg font-semibold">{{ $attempt->recommended_stream ?? '-' }}</p></article>
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Date</p><p class="mt-2 text-lg font-semibold">{{ optional($attempt->submitted_at)->format('d M Y') ?? '-' }}</p></article>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            @foreach ($report['scores'] as $score)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-900">{{ $score->section?->name ?? str_replace('_', ' ', $score->section_code) }}</p>
                    <p class="mt-2 text-2xl font-semibold text-blue-700">{{ $score->percentage }}%</p>
                    <p class="text-xs text-slate-500">{{ $score->raw_score }} / {{ $score->total_marks }} marks</p>
                </article>
            @endforeach
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">Counselor Notes</h3>
            <form method="POST" action="{{ route('career-counselor.kcat.reports.notes.store', $attempt) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                @csrf
                @foreach (['strengths' => 'Strengths', 'development_areas' => 'Development Areas', 'counselor_recommendation' => 'Counselor Recommendation', 'parent_summary' => 'Parent Summary', 'private_notes' => 'Private Notes'] as $field => $label)
                    <div>
                        <label class="text-sm font-semibold text-slate-700">{{ $label }}</label>
                        <textarea name="{{ $field }}" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">{{ old($field, $report['note']?->{$field}) }}</textarea>
                    </div>
                @endforeach
                <div>
                    <label class="text-sm font-semibold text-slate-700">Visibility</label>
                    <select name="visibility" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                        @foreach (['private','student','parent','student_parent'] as $visibility)
                            <option value="{{ $visibility }}" @selected(old('visibility', $report['note']?->visibility ?? 'private') === $visibility)>{{ str_replace('_', ' ', ucfirst($visibility)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 flex justify-end"><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save Notes</button></div>
            </form>
        </section>

        @php($careerProfiles = $attempt->student?->careerProfiles()->latest()->get() ?? collect())
        @if ($careerProfiles->isNotEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Attach to Career Profile</h3>
                <div class="mt-4 flex flex-col gap-3">
                    @foreach ($careerProfiles as $profile)
                        <form method="POST" action="{{ route('career-counselor.kcat.reports.attach-profile', [$attempt, $profile]) }}" class="flex flex-col gap-2 rounded-xl border border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                            @csrf
                            <div>
                                <p class="font-semibold text-slate-800">{{ $profile->session }}</p>
                                <p class="text-xs text-slate-500">{{ optional($profile->created_at)->format('d M Y') }}</p>
                            </div>
                            <button class="rounded-xl border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700">Attach Summary</button>
                        </form>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
