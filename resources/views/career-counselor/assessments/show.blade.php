<x-app-layout>
    <x-slot name="header"><div class="flex justify-between"><h2 class="text-xl font-semibold text-slate-900">Career Assessment Report</h2><a target="_blank" href="{{ request()->routeIs('principal.*') ? route('principal.career-assessments.print', $assessment) : route('career-counselor.assessments.print', $assessment) }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Print</a></div></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">{{ $assessment->student?->name }}</h3><p class="text-sm text-slate-500">{{ $assessment->recommended_stream ?: '-' }} | Alternative: {{ $assessment->alternative_stream ?: '-' }}</p><p class="mt-3 whitespace-pre-line text-sm">{{ $assessment->overall_summary ?: '-' }}</p></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">Scores</h3><div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">@foreach($assessment->scores as $score)<div class="rounded-xl bg-blue-50 p-3 text-sm"><b>{{ str_replace('_', ' ', ucfirst($score->category)) }}</b>: {{ $score->score }}<p class="text-slate-600">{{ $score->remarks }}</p></div>@endforeach</div></section>
    </div>
</x-app-layout>
