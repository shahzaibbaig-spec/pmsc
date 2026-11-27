<style>
    @media print {
        body * { visibility: hidden; }
        .sheet-print-area, .sheet-print-area * { visibility: visible; }
        .sheet-print-area { position: absolute; left: 0; top: 0; width: 100%; background: white; }
        .sheet-print-area button { display: none; }
        .sheet-print-area .overflow-x-auto { overflow: visible; }
        .sheet-print-area table { font-size: 10px; }
        .sheet-print-area tr { break-inside: avoid; }
        .sheet-print-area thead { display: table-header-group; }
        @page { size: landscape; margin: 10mm; }
    }
</style>
<section class="sheet-print-area space-y-6 rounded-xl bg-white p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-semibold">Consolidated {{ \App\Modules\Exams\Enums\ExamType::from($filters['exam_type'])->label() }} Results</h3>
            <p class="text-sm text-slate-500">{{ $sheet->sum(fn ($group) => $group['cards']->count()) }} student assessment rows · {{ $published ? 'Published snapshots' : 'Recorded marks' }} · All matching results shown</p>
        </div>
        <x-ui.button type="button" onclick="window.print()">Print / Save PDF</x-ui.button>
    </div>
    @forelse($sheet as $group)
        @php($first = $group['first'])
        <div>
            <h4 class="mb-2 font-semibold">{{ trim(($first['classRoom']?->name ?? '').' '.($first['classRoom']?->section ?? '')) ?: 'Class not recorded' }} · {{ $first['session'] }} · {{ $first['title'] }}</h4>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead><tr class="bg-slate-100">
                        <th class="border border-slate-200 p-2">Student</th>
                        @foreach($group['subjects'] as $subject)<th class="border border-slate-200 p-2">{{ $subject['name'] }}</th>@endforeach
                        <th class="border border-slate-200 p-2">Total</th><th class="border border-slate-200 p-2">Percentage</th><th class="border border-slate-200 p-2">Comments</th>
                    </tr></thead>
                    <tbody>@foreach($group['cards'] as $card)
                        <tr>
                            <td class="border border-slate-200 p-2 align-top"><strong>{{ $card['student']?->name ?? 'Unknown student' }}</strong><br>{{ $card['student']?->student_id }}</td>
                            @foreach($group['subjects'] as $subject)
                                <td class="border border-slate-200 p-2 align-top">
                                    @forelse($card['rows']->where('subject_id', $subject['id']) as $row)
                                        <div>{{ $row['obtained'] !== null ? $row['obtained'].' / '.$row['total'] : $row['grade'] }}
                                            @if($row['percentage'] !== null)<span class="block text-xs text-slate-500">{{ number_format($row['percentage'], 2) }}% · {{ $row['grade'] }}</span>@endif
                                        </div>
                                    @empty
                                        <span class="text-slate-400">Not recorded</span>
                                    @endforelse
                                </td>
                            @endforeach
                            <td class="border border-slate-200 p-2 align-top">{{ $card['numericCount'] ? $card['obtained'].' / '.$card['total'] : '—' }}</td>
                            <td class="border border-slate-200 p-2 align-top">{{ $card['overall'] !== null ? number_format($card['overall'], 2).'%' : '—' }}</td>
                            <td class="min-w-80 border border-slate-200 p-2 align-top">
                                <strong class="text-xs">{{ $card['commentLabel'] }}@if($card['commentAuthor']) · {{ $card['commentAuthor'] }}@endif</strong>
                                <div class="whitespace-pre-line">{{ $card['comment'] }}</div>
                            </td>
                        </tr>
                    @endforeach</tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">No results match these filters.</p>
    @endforelse
    <p class="text-xs text-slate-500">Totals include recorded numeric subjects only; missing or grade-only results are excluded. Saved comments are retained; automatic comments are suggestions for teacher review. Class comparisons use all recorded students in the assessment, even when filtering to one student. Separate sessions and assessment scopes appear in separate sections.</p>
</section>
