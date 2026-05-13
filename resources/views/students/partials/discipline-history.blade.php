@php
    $disciplineReports = $disciplineReports ?? collect();
    $disciplineComplaints = $disciplineComplaints ?? collect();
    $sportsObservations = $sportsObservations ?? collect();
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Class Discipline Reports</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $disciplineReports->count() }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Open Class Reports</p>
            <p class="mt-2 text-2xl font-semibold text-amber-800">{{ (int) ($openReportCount ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-indigo-700">Sports Observations</p>
            <p class="mt-2 text-2xl font-semibold text-indigo-800">{{ $sportsObservations->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Legacy Complaints</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $disciplineComplaints->count() }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Class Discipline History</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Reported By</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Class / Subject</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Issue</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Severity</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Auto Message</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Principal Remarks</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Warden Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($disciplineReports as $report)
                        @php
                            $status = strtolower((string) ($report->status ?? 'open'));
                            $badgeClass = match ($status) {
                                'resolved' => 'bg-emerald-100 text-emerald-700',
                                'acknowledged' => 'bg-indigo-100 text-indigo-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($report->report_date)->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $report->teacher?->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">
                                {{ trim((string) ($report->classRoom?->name ?? '').' '.(string) ($report->classRoom?->section ?? '')) ?: '-' }}
                                <div class="text-xs text-slate-500">{{ $report->subject?->name ?? '-' }}</div>
                            </td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $report->issue_label ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ ucfirst((string) ($report->severity ?? 'normal')) }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $report->auto_message ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ ucfirst((string) ($report->status ?? 'open')) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $report->principal_remarks ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $report->warden_remarks ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-6 text-center text-sm text-slate-500">No class discipline reports found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Sports Hygiene & Discipline Observations</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Sports Teacher</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Issues</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Message</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($sportsObservations as $observation)
                        @php
                            $status = strtolower((string) ($observation->status ?? 'open'));
                            $badgeClass = match ($status) {
                                'resolved' => 'bg-emerald-100 text-emerald-700',
                                'acknowledged' => 'bg-indigo-100 text-indigo-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $observation->sportsTeacher?->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ method_exists($observation, 'resolvedIssueLabelText') ? $observation->resolvedIssueLabelText() : ($observation->issue_label ?? '-') }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ method_exists($observation, 'resolvedCombinedMessage') ? $observation->resolvedCombinedMessage() : ($observation->auto_message ?? '-') }}</td>
                            <td class="px-3 py-2 text-sm">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ ucfirst((string) ($observation->status ?? 'open')) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">No sports observations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Legacy Discipline Complaints</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Complaint Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Description</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Action Taken</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($disciplineComplaints as $item)
                        @php
                            $status = strtolower((string) ($item->status ?? 'open'));
                            $badgeClass = match ($status) {
                                'resolved', 'closed' => 'bg-emerald-100 text-emerald-700',
                                'pending', 'open' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($item->complaint_date)->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $item->description ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $item->action_taken ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ ucfirst((string) ($item->status ?? '-')) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">No legacy complaints found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

