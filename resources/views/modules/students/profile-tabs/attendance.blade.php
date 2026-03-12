<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Attendance %</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($attendanceStats['attendance_percentage'] ?? 0), 2) }}%</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Days</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($attendanceStats['total'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Present</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ (int) ($attendanceStats['present'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Absent</p>
            <p class="mt-2 text-2xl font-semibold text-rose-700">{{ (int) ($attendanceStats['absent'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Leave</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">{{ (int) ($attendanceStats['leave'] ?? 0) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-slate-900">Monthly Attendance Summary</h4>
            <span class="text-xs text-slate-500">Data Source: {{ $attendanceSource === 'attendance' ? 'Modern Attendance' : 'Legacy Attendance' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Month</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Present</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Absent</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Leave</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">%</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($monthlySummary as $month)
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $month['month'] ?: '-' }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">{{ (int) $month['present'] }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">{{ (int) $month['absent'] }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">{{ (int) $month['leave'] }}</td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">{{ number_format((float) $month['percentage'], 2) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">No monthly attendance summary available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Attendance Log</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($attendanceRecords as $row)
                        @php
                            $status = strtolower((string) ($row->status ?? ''));
                            $badgeClass = match ($status) {
                                'present', 'p' => 'bg-emerald-100 text-emerald-700',
                                'absent', 'a' => 'bg-rose-100 text-rose-700',
                                'leave', 'l' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($row->date)->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ ucfirst((string) $row->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $row->remarks ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500">No attendance logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

