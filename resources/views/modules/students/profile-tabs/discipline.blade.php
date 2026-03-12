<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Complaints</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $disciplineComplaints->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Open Cases</p>
            <p class="mt-2 text-2xl font-semibold text-rose-700">{{ (int) $openCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Resolved Cases</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ max($disciplineComplaints->count() - (int) $openCount, 0) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Discipline Records</h4>
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
                    @forelse($disciplineComplaints as $item)
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
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">No discipline complaints found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

