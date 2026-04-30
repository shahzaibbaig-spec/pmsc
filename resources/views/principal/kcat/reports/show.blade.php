<x-app-layout>
    @php($attempt = $report['attempt'])
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-slate-900">KCAT Student Report</h2>
            <a href="{{ route('principal.kcat.reports.print', $attempt) }}" target="_blank" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Print</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">{{ $attempt->student?->name }}</h3>
            <p class="text-sm text-slate-500">{{ trim(($attempt->student?->classRoom?->name ?? '').' '.($attempt->student?->classRoom?->section ?? '')) }} | {{ $attempt->test?->title }}</p>
        </section>
        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Score</p><p class="mt-2 text-2xl font-semibold">{{ $attempt->percentage ?? 0 }}%</p></article>
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Band</p><p class="mt-2 text-2xl font-semibold">{{ str_replace('_', ' ', $attempt->band ?? '-') }}</p></article>
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Stream</p><p class="mt-2 text-lg font-semibold">{{ $attempt->recommended_stream ?? '-' }}</p></article>
            <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Session</p><p class="mt-2 text-lg font-semibold">{{ $attempt->session }}</p></article>
        </section>
        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            @foreach ($report['scores'] as $score)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="font-semibold">{{ $score->section?->name }}</p><p class="mt-2 text-xl font-semibold text-blue-700">{{ $score->percentage }}%</p><p class="text-xs text-slate-500">{{ $score->raw_score }} / {{ $score->total_marks }}</p></article>
            @endforeach
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-slate-900">Counselor Summary</h3>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $report['note']?->counselor_recommendation ?? $attempt->recommendation_summary }}</p>
        </section>
    </div>
</x-app-layout>
