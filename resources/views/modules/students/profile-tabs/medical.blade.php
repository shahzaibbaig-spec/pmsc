<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Medical History Entries</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $medicalHistory->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Medical Referrals</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $medicalReferrals->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pending Referrals</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">
                {{ $medicalReferrals->whereIn('status', ['pending', 'referred', 'in_progress'])->count() }}
            </p>
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
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Medical Referrals</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Referred At</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Illness Type</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Diagnosis</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($medicalReferrals as $referral)
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($referral->referred_at)->format('d M Y h:i A') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $referral->illness_label }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $referral->diagnosis ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ ucfirst((string) ($referral->status ?? '-')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">No referrals found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

