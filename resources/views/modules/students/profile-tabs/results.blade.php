<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Result Entries</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($resultStats['results_count'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Average %</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($resultStats['average_percentage'] ?? 0), 2) }}%</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current Grade</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $resultStats['grade'] ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-sm font-semibold text-slate-900">Results History</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Result Date</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Exam</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Subject</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Marks</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">%</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Grade</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($results as $result)
                        @php
                            $percentage = (float) $result->percentage;
                            $grade = match (true) {
                                $percentage >= 90 => 'A+',
                                $percentage >= 80 => 'A',
                                $percentage >= 70 => 'B',
                                $percentage >= 60 => 'C',
                                $percentage >= 50 => 'D',
                                default => 'F',
                            };
                            $gradeClass = match ($grade) {
                                'A+', 'A' => 'bg-emerald-100 text-emerald-700',
                                'B', 'C' => 'bg-indigo-100 text-indigo-700',
                                'D' => 'bg-amber-100 text-amber-700',
                                default => 'bg-rose-100 text-rose-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($result->result_date)->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $result->exam_name ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-slate-700">{{ $result->subject?->name ?: '-' }}</td>
                            <td class="px-3 py-2 text-right text-sm text-slate-700">{{ (float) $result->obtained_marks }}/{{ (float) $result->total_marks }}</td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">{{ number_format($percentage, 2) }}%</td>
                            <td class="px-3 py-2 text-sm">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $gradeClass }}">{{ $grade }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-sm text-slate-500">No result entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

