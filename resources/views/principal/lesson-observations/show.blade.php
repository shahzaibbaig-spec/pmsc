<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Lesson Observation Detail</h2>
                <p class="mt-1 text-sm text-slate-500">Observation #{{ $observation->id }} submitted on {{ optional($observation->observation_date)->format('d M Y') ?: '-' }}.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('principal.lesson-observations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
                @can('print_observations')
                    <a href="{{ route('principal.lesson-observations.print', $observation->id) }}" target="_blank" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Print</a>
                @endcan
            </div>
        </div>
    </x-slot>

    @php
        $areaRows = $observation->items->groupBy('area');
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Observed Teacher</p><p class="mt-1 text-sm text-slate-900">{{ $observation->observedTeacher?->name ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Observer</p><p class="mt-1 text-sm text-slate-900">{{ $observation->observer?->name ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Session</p><p class="mt-1 text-sm text-slate-900">{{ $observation->session }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Date</p><p class="mt-1 text-sm text-slate-900">{{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Class</p><p class="mt-1 text-sm text-slate-900">{{ trim(($observation->classRoom?->name ?? '').' '.($observation->classRoom?->section ?? '')) ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Subject / Topic</p><p class="mt-1 text-sm text-slate-900">{{ $observation->subject_topic ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">No. of Students</p><p class="mt-1 text-sm text-slate-900">{{ $observation->no_of_students ?? '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Status</p><p class="mt-1 text-sm text-slate-900">{{ ucfirst($observation->status) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Total Marks</p><p class="mt-1 text-sm text-slate-900">{{ number_format((float) $observation->total_marks, 2) }} / {{ number_format((float) $observation->max_marks, 2) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Performance</p><p class="mt-1 text-sm text-slate-900">{{ number_format((float) ($observation->performance_score ?? 0), 2) }}%</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Overall Judgment</p><p class="mt-1 text-sm text-slate-900">{{ $observation->overall_judgment ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Progress</p><p class="mt-1 text-sm text-slate-900">{{ $observation->progress_percentage !== null ? number_format((float) $observation->progress_percentage, 2).'%' : '-' }}</p></div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Area Summary</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Area</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($areaRows as $area => $items)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $area }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $items->sum('mark') }} / {{ (int) $items->sum('max_mark') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Checklist Details</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Area</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Standard</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Mark</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Comments</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($observation->items as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $item->area }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $item->standard_text }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $item->mark }} / {{ $item->max_mark }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $item->comments ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No checklist items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Narrative Notes</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Learning Objectives</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->learning_objectives ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Previous Targets</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->previous_targets ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">What Went Well</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->what_went_well ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Even Better If</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->even_better_if ?: '-' }}</p></div>
            </div>
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Teacher Comments</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->teacher_comments ?: 'Pending teacher comment.' }}</p>
                @if ($observation->teacher_commented_at)
                    <p class="mt-2 text-xs text-slate-500">Commented at {{ $observation->teacher_commented_at->format('d M Y h:i A') }}</p>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
