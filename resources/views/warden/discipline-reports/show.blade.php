<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Discipline Report Detail</h2>
                <p class="mt-1 text-sm text-slate-500">Read-only incident details, remarks, and actions taken.</p>
            </div>
            <a href="{{ route('warden.discipline-reports.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Reports
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Student Information</h3>
                <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                    <p><span class="font-semibold text-slate-900">Student:</span> {{ $report->student?->name ?? 'Student' }}</p>
                    <p><span class="font-semibold text-slate-900">Admission No:</span> {{ $report->student?->student_id ?? '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Class:</span> {{ trim(($report->student?->classRoom?->name ?? '').' '.($report->student?->classRoom?->section ?? '')) ?: '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Status:</span> {{ ucfirst((string) ($report->status ?? 'pending')) }}</p>
                    <p><span class="font-semibold text-slate-900">Incident Date:</span> {{ optional($report->complaint_date)->format('d M Y') ?: '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Reporting Staff:</span> Not specified</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Incident Report</h4>
                <div class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $report->description ?: 'No details provided.' }}</div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Actions / Remarks</h4>
                <div class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $report->action_taken ?: 'No action has been recorded yet.' }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
