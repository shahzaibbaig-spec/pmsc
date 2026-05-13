<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Observation Detail</h2>
                <p class="mt-1 text-sm text-slate-500">Sports hygiene and discipline observation record.</p>
            </div>
            <a href="{{ route('sports-teacher.observations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Student Information</h3>
            <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                <p><span class="font-semibold text-slate-900">Student:</span> {{ $observation->student?->name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Admission No:</span> {{ $observation->student?->student_id ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Father Name:</span> {{ $observation->student?->father_name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Class/Section:</span> {{ trim(($observation->classRoom?->name ?? '').' '.($observation->classRoom?->section ?? '')) ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Session:</span> {{ $observation->session }}</p>
                <p><span class="font-semibold text-slate-900">Observation Date:</span> {{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Observation Details</h3>
            @php
                $issueItems = $observation->resolvedIssueItems();
            @endphp
            <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                <p><span class="font-semibold text-slate-900">Issue(s):</span> {{ $observation->resolvedIssueLabelText() }}</p>
                <p><span class="font-semibold text-slate-900">Issue Type(s):</span> {{ collect($issueItems)->pluck('type')->implode(', ') ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Severity:</span> {{ ucfirst($observation->severity) }}</p>
                <p><span class="font-semibold text-slate-900">Status:</span> {{ ucfirst($observation->status) }}</p>
                <p><span class="font-semibold text-slate-900">Submitted By:</span> {{ $observation->sportsTeacher?->name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Last Updated:</span> {{ optional($observation->updated_at)->format('d M Y h:i A') ?: '-' }}</p>
            </div>
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-700">Issue-wise Messages</p>
                <ul class="mt-2 space-y-2 text-sm text-slate-700">
                    @forelse ($issueItems as $issue)
                        <li><span class="font-semibold">{{ $issue['label'] }}:</span> {{ $issue['message'] }}</li>
                    @empty
                        <li>-</li>
                    @endforelse
                </ul>
            </div>
            <div class="mt-5 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Combined Auto Message</p>
                <p class="mt-2 whitespace-pre-line text-sm text-indigo-900">{{ $observation->resolvedCombinedMessage() }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Resolution Tracking</h3>
            <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                <p><span class="font-semibold text-slate-900">Resolved At:</span> {{ optional($observation->resolved_at)->format('d M Y h:i A') ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Resolved By:</span> {{ $observation->resolvedBy?->name ?? '-' }}</p>
                <p class="md:col-span-2"><span class="font-semibold text-slate-900">Resolution Notes:</span> {{ $observation->resolution_notes ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Principal Notified:</span> {{ optional($observation->notified_principal_at)->format('d M Y h:i A') ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Warden Notified:</span> {{ optional($observation->notified_wardens_at)->format('d M Y h:i A') ?: '-' }}</p>
            </div>
        </section>
    </div>
</x-app-layout>
