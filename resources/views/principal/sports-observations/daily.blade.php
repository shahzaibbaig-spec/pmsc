<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Daily Sports Hygiene &amp; Discipline Report</h2>
                <p class="mt-1 text-sm text-slate-500">Daily monitoring table for sports hygiene and discipline observations.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('principal.sports-observations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    All Reports
                </a>
                <a href="{{ route('principal.sports-observations.print', array_merge(request()->query(), ['date' => $filters['date'] ?? now()->toDateString()])) }}" target="_blank" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Print Daily Table
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('principal.sports-observations.daily') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                    <input id="date" type="date" name="date" value="{{ $filters['date'] ?? now()->toDateString() }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                </div>

                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected(($filters['session'] ?? null) === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class/Section</label>
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Classes</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>{{ $class['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="student_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Student</label>
                    <select id="student_id" name="student_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Students</option>
                        @foreach ($students as $student)
                            <option value="{{ $student['id'] }}" @selected((int) ($filters['student_id'] ?? 0) === (int) $student['id'])>{{ $student['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="issue_type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Issue Type</label>
                    <select id="issue_type" name="issue_type" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Issues</option>
                        @foreach ($issue_options as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['issue_type'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sports_teacher_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Sports Teacher</label>
                    <select id="sports_teacher_id" name="sports_teacher_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All</option>
                        @foreach ($sports_teachers as $teacher)
                            <option value="{{ $teacher['id'] }}" @selected((int) ($filters['sports_teacher_id'] ?? 0) === (int) $teacher['id'])>{{ $teacher['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All</option>
                        @foreach ($status_options as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="per_page" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rows</label>
                    <select id="per_page" name="per_page" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-4 flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                    <a href="{{ route('principal.sports-observations.daily', ['date' => now()->toDateString()]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Total</p><p class="mt-2 text-2xl font-semibold text-blue-800">{{ number_format((int) ($cards['total'] ?? 0)) }}</p></article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-amber-700">Open</p><p class="mt-2 text-2xl font-semibold text-amber-800">{{ number_format((int) ($cards['open'] ?? 0)) }}</p></article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-indigo-700">Acknowledged</p><p class="mt-2 text-2xl font-semibold text-indigo-800">{{ number_format((int) ($cards['acknowledged'] ?? 0)) }}</p></article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-emerald-700">Resolved</p><p class="mt-2 text-2xl font-semibold text-emerald-800">{{ number_format((int) ($cards['resolved'] ?? 0)) }}</p></article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-base font-semibold text-slate-900">Daily Observations Table</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Sr #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Student Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Admission No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Class/Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Issues</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Sports Teacher</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @php
                            $pageOffset = ($observations->currentPage() - 1) * $observations->perPage();
                        @endphp
                        @forelse ($observations as $index => $observation)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $pageOffset + $index + 1 }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-900">{{ $observation->student?->name ?? '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $observation->student?->student_id ?? '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ trim(($observation->classRoom?->name ?? '').' '.($observation->classRoom?->section ?? '')) ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $observation->resolvedIssueLabelText() }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit($observation->resolvedCombinedMessage(), 120) }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $observation->sportsTeacher?->name ?? '-' }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $observation->status === 'resolved' ? 'bg-emerald-100 text-emerald-700' : ($observation->status === 'acknowledged' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ucfirst($observation->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <div class="flex flex-col gap-2">
                                        @if ($observation->status === 'open')
                                            <form method="POST" action="{{ route('principal.sports-observations.acknowledge', $observation) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex min-h-9 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                                    Acknowledge
                                                </button>
                                            </form>
                                        @endif
                                        @if ($observation->status !== 'resolved')
                                            <form method="POST" action="{{ route('principal.sports-observations.resolve', $observation) }}" class="flex flex-col gap-2">
                                                @csrf
                                                <input type="text" name="resolution_notes" placeholder="Resolution note (optional)" class="min-h-9 rounded-lg border-slate-300 text-xs">
                                                <button type="submit" class="inline-flex min-h-9 items-center rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                                    Resolve
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500">No observations found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($observations->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">{{ $observations->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
