<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">KCAT Result</h2></x-slot>

    <div class="mx-auto max-w-5xl space-y-5 py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">{{ $attempt->test?->title }}</h3>
            <p class="mt-2 text-sm text-slate-600">Your KCAT has been submitted. Detailed interpretation will be reviewed by the Career Counselor.</p>
        </section>
        @if (in_array($attempt->latestReportNote?->visibility, ['student', 'student_parent'], true))
            <section class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase text-blue-700">Visible Summary</p>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $attempt->latestReportNote?->parent_summary ?? $attempt->recommendation_summary }}</p>
            </section>
        @endif
    </div>
</x-app-layout>
