<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Medical History Entries</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $medicalHistory->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Medical Visits</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $medicalReferrals->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Attached CBC Reports</p>
            <p class="mt-2 text-2xl font-semibold text-indigo-700">
                {{ $medicalReferrals->sum(fn($r) => $r->cbcReports->count()) }}
            </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Standalone CBC Reports</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $standaloneCbcReports->count() }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Medical History</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Visit Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Details</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Treatment</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($medicalHistory as $item)
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($item->visit_date)->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $item->details ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $item->treatment ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ ucfirst((string) ($item->status ?? '-')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">No medical history entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Medical Visits with CBC Reports</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Visit Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Source</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Problem</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Diagnosis</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">CBC Reports</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($medicalReferrals as $referral)
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($referral->visit_date ?? $referral->referred_at)->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $referral->source_label }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $referral->problem ?: ($referral->illness_label ?? '-') }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $referral->diagnosis ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">
                                @if($referral->cbcReports->isEmpty())
                                    <span class="text-slate-400">No CBC</span>
                                @else
                                    <div class="space-y-1">
                                        @foreach($referral->cbcReports as $cbc)
                                            <div class="flex items-center gap-2">
                                                <span>#{{ $cbc->id }} | {{ optional($cbc->report_date)->format('d M Y') ?: '-' }}{{ $cbc->machine_report_no ? ' | '.$cbc->machine_report_no : '' }}</span>
                                                <a href="{{ route('principal.cbc-reports.show', $cbc) }}" class="text-xs text-blue-700 hover:underline">View</a>
                                                <a href="{{ route('principal.cbc-reports.print', $cbc) }}" target="_blank" class="text-xs text-emerald-700 hover:underline">Print</a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ ucfirst((string) ($referral->status ?? '-')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-sm text-slate-500">No medical visits found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Standalone CBC Reports</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Machine #</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Doctor</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($standaloneCbcReports as $cbc)
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($cbc->report_date)->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $cbc->machine_report_no ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $cbc->doctor?->name ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">
                                <a href="{{ route('principal.cbc-reports.show', $cbc) }}" class="text-blue-700 hover:underline">View</a>
                                <span class="mx-1">|</span>
                                <a href="{{ route('principal.cbc-reports.print', $cbc) }}" target="_blank" class="text-emerald-700 hover:underline">Print</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">No standalone CBC reports found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
