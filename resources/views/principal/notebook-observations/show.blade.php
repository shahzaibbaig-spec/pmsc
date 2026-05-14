<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Notebook Observation Detail</h2>
                <p class="mt-1 text-sm text-slate-500">Observation #{{ $observation->id }} submitted on {{ optional($observation->observation_date)->format('d M Y') ?: '-' }}.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('principal.notebook-observations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
                @can('print_observations')
                    <a href="{{ route('principal.notebook-observations.print', $observation->id) }}" target="_blank" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Print</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Observed Teacher</p><p class="mt-1 text-sm text-slate-900">{{ $observation->observedTeacher?->name ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Observer</p><p class="mt-1 text-sm text-slate-900">{{ $observation->observer?->name ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Session</p><p class="mt-1 text-sm text-slate-900">{{ $observation->session }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Date</p><p class="mt-1 text-sm text-slate-900">{{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Class</p><p class="mt-1 text-sm text-slate-900">{{ trim(($observation->classRoom?->name ?? '').' '.($observation->classRoom?->section ?? '')) ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Subject</p><p class="mt-1 text-sm text-slate-900">{{ $observation->subject?->name ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Status</p><p class="mt-1 text-sm text-slate-900">{{ ucfirst($observation->status) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Performance</p><p class="mt-1 text-sm text-slate-900">{{ number_format((float) ($observation->performance_score ?? 0), 2) }}%</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Total Students</p><p class="mt-1 text-sm text-slate-900">{{ $observation->total_students ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Notebooks Provided</p><p class="mt-1 text-sm text-slate-900">{{ $observation->notebooks_provided ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Covered</p><p class="mt-1 text-sm text-slate-900">{{ $observation->covered_notebooks ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Uncovered</p><p class="mt-1 text-sm text-slate-900">{{ $observation->uncovered_notebooks ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Well Maintained</p><p class="mt-1 text-sm text-slate-900">{{ $observation->well_maintained ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Total Yes</p><p class="mt-1 text-sm text-slate-900">{{ (int) $observation->total_yes }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Total No</p><p class="mt-1 text-sm text-slate-900">{{ (int) $observation->total_no }}</p></div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Checklist Details</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Checklist Item</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Response</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Comments</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($observation->items as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $item->checklist_text }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ strtoupper((string) $item->response) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $item->comments ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No checklist items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">General Comments</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->general_comments ?: '-' }}</p>
            </div>
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Teacher Comments</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->teacher_comments ?: 'Pending teacher comment.' }}</p>
                @if ($observation->teacher_commented_at)
                    <p class="mt-2 text-xs text-slate-500">Commented at {{ $observation->teacher_commented_at->format('d M Y h:i A') }}</p>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
