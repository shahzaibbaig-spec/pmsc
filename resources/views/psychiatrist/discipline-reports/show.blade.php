<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Discipline Case Review</h2>
                <p class="mt-1 text-sm text-slate-500">Read-only case details with psychiatrist feedback note.</p>
            </div>
            <a href="{{ route('psychiatrist.discipline-reports.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Reports
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Student Information</h3>
            <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                <p><span class="font-semibold text-slate-900">Student:</span> {{ $report->student?->name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Admission No:</span> {{ $report->student?->student_id ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Class:</span> {{ trim(($report->classRoom?->name ?? '').' '.($report->classRoom?->section ?? '')) ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Subject:</span> {{ $report->subject?->name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Teacher:</span> {{ $report->teacher?->name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Date:</span> {{ optional($report->report_date)->format('d M Y') ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Session:</span> {{ $report->session }}</p>
                <p><span class="font-semibold text-slate-900">Issue:</span> {{ $report->issue_label }}</p>
                <p><span class="font-semibold text-slate-900">Severity:</span> {{ ucfirst((string) $report->severity) }}</p>
                <p><span class="font-semibold text-slate-900">Status:</span> {{ ucfirst((string) $report->status) }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Auto Message</h4>
            <div class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $report->auto_message ?: '-' }}</div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Teacher Description</h4>
            <div class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $report->description ?: 'No additional note provided.' }}</div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Follow-up</h4>
            <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                <p><span class="font-semibold text-slate-900">Acknowledged By:</span> {{ $report->acknowledgedBy?->name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Acknowledged At:</span> {{ optional($report->acknowledged_at)->format('d M Y h:i A') ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Resolved By:</span> {{ $report->resolvedBy?->name ?? '-' }}</p>
                <p><span class="font-semibold text-slate-900">Resolved At:</span> {{ optional($report->resolved_at)->format('d M Y h:i A') ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Principal Remarks:</span> {{ $report->principal_remarks ?: '-' }}</p>
                <p><span class="font-semibold text-slate-900">Warden Remarks:</span> {{ $report->warden_remarks ?: '-' }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6 shadow-sm">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-cyan-800">Psychiatrist Feedback</h4>
            <p class="mt-1 text-xs text-cyan-700">This note is visible to principal on report screens.</p>

            <div class="mt-4 rounded-xl border border-cyan-200 bg-white p-4 text-sm text-slate-700">
                <p class="font-semibold text-slate-900">Latest Feedback</p>
                <p class="mt-2 whitespace-pre-line">{{ $report->psychiatrist_feedback ?: 'No feedback added yet.' }}</p>
                <p class="mt-2 text-xs text-slate-500">
                    Reviewed by: {{ $report->psychiatristReviewedBy?->name ?? '-' }}
                    • {{ optional($report->psychiatrist_reviewed_at)->format('d M Y h:i A') ?: '-' }}
                </p>
            </div>

            <form method="POST" action="{{ route('psychiatrist.discipline-reports.feedback', $report) }}" class="mt-4 space-y-3">
                @csrf
                <label for="psychiatrist_feedback" class="block text-xs font-semibold uppercase tracking-wide text-cyan-800">Add / Update Feedback</label>
                <textarea id="psychiatrist_feedback" name="psychiatrist_feedback" rows="5" class="block w-full rounded-lg border-cyan-200 text-sm" placeholder="Write your clinical/behavioral feedback for follow-up...">{{ old('psychiatrist_feedback', (string) $report->psychiatrist_feedback) }}</textarea>
                <button type="submit" class="inline-flex min-h-10 items-center rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Save Feedback</button>
            </form>
        </section>
    </div>
</x-app-layout>

