<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Assigned Subjects</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $subjectsCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Attendance Health</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($attendanceStats['attendance_percentage'] ?? 0), 2) }}%</p>
            <p class="mt-1 text-xs text-slate-500">Present: {{ (int) ($attendanceStats['present'] ?? 0) }} / {{ (int) ($attendanceStats['total'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Result Profile</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $resultStats['grade'] ?? 'N/A' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format((float) ($resultStats['average_percentage'] ?? 0), 2) }}% average</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Open Discipline</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $openDisciplineCount }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Recent Attendance</h4>
                <span class="text-xs text-slate-500">Latest 8</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($recentAttendance as $row)
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-3 py-6 text-center text-sm text-slate-500">No attendance records available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Recent Results</h4>
                <span class="text-xs text-slate-500">Latest 6</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Exam</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Subject</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($recentResults as $result)
                            <tr>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $result->exam_name ?: '-' }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $result->subject?->name ?: '-' }}</td>
                                <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">{{ number_format((float) $result->percentage, 2) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500">No result history available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Fee Snapshot</h4>
                <span class="text-xs text-slate-500">Latest 6 challans</span>
            </div>
            <div class="mb-3 grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <p class="text-slate-500">Pending Amount</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">Rs. {{ number_format((float) ($feeStats['pending_amount'] ?? 0), 2) }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <p class="text-slate-500">Pending Challans</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ (int) ($feeStats['pending_challans'] ?? 0) }}</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Challan #</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Due Date</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($recentChallans as $challan)
                            @php
                                $totalAmount = (float) $challan->total_amount;
                                $paidAmount = min((float) ($challan->paid_total ?? 0), $totalAmount);
                                $dueAmount = max($totalAmount - $paidAmount, 0);
                            @endphp
                            <tr>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ $challan->challan_number }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ optional($challan->due_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">Rs. {{ number_format($dueAmount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500">No fee challans available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-900">Medical Snapshot</h4>
                <span class="text-xs text-slate-500">Latest 5 visits</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Visit Date</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Details</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($recentMedical as $visit)
                            <tr>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ optional($visit->visit_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit((string) $visit->details, 60) ?: '-' }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ ucfirst((string) ($visit->status ?? '-')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500">No medical history available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

