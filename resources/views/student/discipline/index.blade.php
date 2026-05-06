<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">My Discipline Report</h2>
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
                        <form method="GET" action="{{ route('student.discipline.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div>
                                <label for="status" class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status</label>
                                <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">All</option>
                                    <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                                    <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Resolved</option>
                                    <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
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
                                <a href="{{ route('student.discipline.index') }}" class="min-h-11 rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reset</a>
                            </div>
                        </form>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Student ID</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $student->student_id }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Total Reports</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $summary['total'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Open/Pending</p>
                                <p class="mt-1 text-sm font-semibold text-rose-700">{{ $summary['open'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Resolved</p>
                                <p class="mt-1 text-sm font-semibold text-emerald-700">{{ $summary['resolved'] ?? 0 }}</p>
                            </div>
                        </div>
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
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Description</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Action Taken</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($reports as $report)
                                    @php
                                        $status = mb_strtolower((string) $report->status);
                                        $badgeClass = in_array($status, ['open', 'pending'], true)
                                            ? 'bg-rose-100 text-rose-700'
                                            : 'bg-emerald-100 text-emerald-700';
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ optional($report->complaint_date)->format('Y-m-d') ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-700">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">{{ ucfirst((string) $report->status) }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-700">{{ $report->description }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-700">{{ $report->action_taken ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-8 text-center text-sm text-gray-500">No discipline reports found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if (method_exists($reports, 'links'))
                        <div class="border-t border-gray-100 px-4 py-3">
                            {{ $reports->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
