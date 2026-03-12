<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Subjects</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $subjects->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Subject Groups Applied</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $groupedMatrix->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Class</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">{{ trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')) ?: '-' }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Assigned Subjects</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Subject</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Code</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($subjects as $subject)
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $subject->name ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $subject->code ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-3 py-6 text-center text-sm text-slate-500">No subjects assigned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Subject Assignment Matrix Breakdown</h4>
        <div class="space-y-3">
            @forelse($groupedMatrix as $group)
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">{{ $group['group_name'] }}</p>
                        <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                            {{ $group['subjects']->count() }} subjects
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        @foreach($group['subjects'] as $subject)
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700">
                                {{ $subject['name'] }}{{ !empty($subject['code']) ? ' ('.$subject['code'].')' : '' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                    No matrix-based subject assignments found.
                </div>
            @endforelse
        </div>
    </div>
</div>

