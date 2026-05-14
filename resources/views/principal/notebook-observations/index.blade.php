<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Notebook Observations</h2>
                <p class="mt-1 text-sm text-slate-500">Principal/Admin notebook quality and checklist monitoring.</p>
            </div>
            @can('conduct_notebook_observation')
                <a href="{{ route('principal.notebook-observations.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    New Observation
                </a>
            @endcan
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
            <form method="GET" action="{{ route('principal.notebook-observations.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                    <input id="date" name="date" type="date" value="{{ $filters['date'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div>
                    <label for="date_from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date From</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div>
                    <label for="date_to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date To</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Sessions</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected(($filters['session'] ?? null) === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Classes</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>{{ $class['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="observed_teacher_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Observed Teacher</label>
                    <select id="observed_teacher_id" name="observed_teacher_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Teachers</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher['id'] }}" @selected((int) ($filters['observed_teacher_id'] ?? 0) === (int) $teacher['id'])>{{ $teacher['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="observer_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Observer</label>
                    <select id="observer_id" name="observer_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Observers</option>
                        @foreach ($observers as $observer)
                            <option value="{{ $observer['id'] }}" @selected((int) ($filters['observer_id'] ?? 0) === (int) $observer['id'])>{{ $observer['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All</option>
                        <option value="submitted" @selected(($filters['status'] ?? null) === 'submitted')>Submitted</option>
                        <option value="commented" @selected(($filters['status'] ?? null) === 'commented')>Commented</option>
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
                    <a href="{{ route('principal.notebook-observations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Sr #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Observed Teacher</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Observer</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Yes / No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Score %</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @php
                            $pageOffset = ($observations->currentPage() - 1) * $observations->perPage();
                        @endphp
                        @forelse ($observations as $index => $observation)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $pageOffset + $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $observation->observedTeacher?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $observation->observer?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ trim(($observation->classRoom?->name ?? '').' '.($observation->classRoom?->section ?? '')) ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $observation->total_yes }} / {{ (int) $observation->total_no }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) ($observation->performance_score ?? 0), 2) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $observation->status === 'commented' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($observation->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('principal.notebook-observations.show', $observation->id) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">View</a>
                                        @can('print_observations')
                                            <a target="_blank" href="{{ route('principal.notebook-observations.print', $observation->id) }}" class="inline-flex min-h-9 items-center rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">Print</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">No notebook observations found for selected filters.</td>
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
