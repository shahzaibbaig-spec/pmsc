<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Warden Daily Report Detail</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $report->hostel?->name ?? 'Hostel' }} | {{ optional($report->report_date)->format('d M Y') }}
                </p>
            </div>
            <a href="{{ route('warden.daily-reports.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Reports
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created By</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $report->createdBy?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created At</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($report->created_at)->format('d M Y h:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">General Notes</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $report->notes ?: '-' }}</p>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900">Night Attendance</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($report->attendance as $row)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $row->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $row->student?->student_id ?? '-' }} | {{ $row->student?->classRoom?->name }} {{ $row->student?->classRoom?->section }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ ucfirst(str_replace('_', ' ', (string) $row->status)) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row->remarks ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">No attendance rows found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900">Discipline Logs</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Issue</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action Taken</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($report->disciplineLogs as $row)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $row->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $row->student?->student_id ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row->issue_type }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ ucfirst((string) $row->severity) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row->description }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row->action_taken ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No discipline logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900">Health Logs</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Condition</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Temperature</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Medication</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Doctor Visit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($report->healthLogs as $row)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $row->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $row->student?->student_id ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row->condition }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row->temperature !== null ? number_format((float) $row->temperature, 1) : '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row->medication ?: '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $row->doctor_visit ? 'Yes' : 'No' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No health logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

