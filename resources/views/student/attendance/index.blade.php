<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">My Attendance</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6">
                    @if ($message)
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">{{ $message }}</div>
                    @elseif (! $student)
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">No student profile found.</div>
                    @else
                        <form method="GET" action="{{ route('student.attendance.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div>
                                <label for="status" class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status</label>
                                <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">All</option>
                                    <option value="present" @selected(($filters['status'] ?? '') === 'present')>Present</option>
                                    <option value="absent" @selected(($filters['status'] ?? '') === 'absent')>Absent</option>
                                    <option value="leave" @selected(($filters['status'] ?? '') === 'leave')>Leave</option>
                                    <option value="p" @selected(($filters['status'] ?? '') === 'p')>P</option>
                                    <option value="a" @selected(($filters['status'] ?? '') === 'a')>A</option>
                                    <option value="l" @selected(($filters['status'] ?? '') === 'l')>L</option>
                                </select>
                            </div>
                            <div>
                                <label for="date_from" class="text-xs font-semibold uppercase tracking-wide text-gray-500">Date From</label>
                                <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm">
                            </div>
                            <div>
                                <label for="date_to" class="text-xs font-semibold uppercase tracking-wide text-gray-500">Date To</label>
                                <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm">
                            </div>
                            <div class="flex items-end gap-2">
                                <button class="min-h-11 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Apply</button>
                                <a href="{{ route('student.attendance.index') }}" class="min-h-11 rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reset</a>
                            </div>
                        </form>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-5">
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Student ID</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $student->student_id }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Present</p>
                                <p class="mt-1 text-sm font-semibold text-emerald-700">{{ $summary['present'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Absent</p>
                                <p class="mt-1 text-sm font-semibold text-rose-700">{{ $summary['absent'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Leave</p>
                                <p class="mt-1 text-sm font-semibold text-amber-700">{{ $summary['leave'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Attendance %</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ number_format((float) ($summary['attendance_percentage'] ?? 0), 2) }}%</p>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-gray-500">Data source: {{ $source === 'attendance' ? 'Modern attendance records' : 'Legacy student attendance records' }}</p>
                    @endif
                </div>
            </div>

            @if ($student)
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($records as $record)
                                    @php
                                        $status = mb_strtolower((string) $record->status);
                                        $badgeClass = match ($status) {
                                            'present', 'p' => 'bg-emerald-100 text-emerald-700',
                                            'absent', 'a' => 'bg-rose-100 text-rose-700',
                                            'leave', 'l' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ optional($record->date)->format('Y-m-d') ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-700">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">{{ strtoupper((string) $record->status) }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-700">{{ $record->remarks ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-8 text-center text-sm text-gray-500">No attendance records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if (method_exists($records, 'links'))
                        <div class="border-t border-gray-100 px-4 py-3">
                            {{ $records->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
