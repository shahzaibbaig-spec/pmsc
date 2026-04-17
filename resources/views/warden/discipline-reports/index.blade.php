<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Discipline Reports</h2>
            <p class="mt-1 text-sm text-slate-500">Read-only list of student discipline and incident reports.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('warden.discipline-reports.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-6">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Student, description, action" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="student_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Student</label>
                        <select id="student_id" name="student_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All students</option>
                            @foreach ($students as $student)
                                <option value="{{ $student['id'] }}" @selected((int) ($filters['student_id'] ?? 0) === (int) $student['id'])>{{ $student['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>{{ $class['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="incident_type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Incident Type</label>
                        <input id="incident_type" type="text" name="incident_type" value="{{ $filters['incident_type'] ?? '' }}" placeholder="Keyword" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="date_from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date From</label>
                        <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="date_to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date To</label>
                        <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="per_page" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rows</label>
                        <select id="per_page" name="per_page" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ([10, 15, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 15) === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                        <a href="{{ route('warden.discipline-reports.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Incident / Report Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($reports as $report)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $report->student?->name ?? 'Student' }}</p>
                                        <p class="text-xs text-slate-500">{{ $report->student?->student_id }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ trim(($report->student?->classRoom?->name ?? '').' '.($report->student?->classRoom?->section ?? '')) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit((string) $report->description, 90) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ optional($report->complaint_date)->format('d M Y') ?: '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ ucfirst((string) ($report->status ?? 'pending')) }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        <a href="{{ route('warden.discipline-reports.show', $report) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No discipline reports found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($reports->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $reports->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
