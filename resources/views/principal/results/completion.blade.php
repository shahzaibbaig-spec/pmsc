<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Result Completion</h2></x-slot>
    <div class="space-y-6">
        @if($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
        <form method="GET" action="{{ route('principal.results.completion') }}" class="grid gap-4 rounded-xl bg-white p-5 md:grid-cols-3">
            <label class="text-sm">Session<select name="session" class="mt-1 block w-full rounded-lg border-slate-300">@foreach($sessions as $session)<option @selected($filters['session'] === $session)>{{ $session }}</option>@endforeach</select></label>
            <label class="text-sm">Assessment<select name="exam_type" class="mt-1 block w-full rounded-lg border-slate-300">@foreach($examTypes as $type)<option value="{{ $type['value'] }}" @selected($filters['exam_type'] === $type['value'])>{{ $type['label'] }}</option>@endforeach</select></label>
            <label class="text-sm">Class details<select name="class_id" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">All classes</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(($filters['class_id'] ?? '') == $class->id)>{{ trim($class->name.' '.$class->section) }}</option>@endforeach</select></label>
            <label class="text-sm">Bimonthly number<select name="sequence_number" class="mt-1 block w-full rounded-lg border-slate-300">@foreach([1, 2, 3, 4] as $number)<option value="{{ $number }}" @selected($filters['sequence_number'] == $number)>{{ $number }}</option>@endforeach</select><span class="text-xs text-slate-500">Used only for Bimonthly.</span></label>
            <x-ui.input name="topic" label="Class test topic" :value="$filters['topic']" />
            <x-ui.input name="exam_date" type="date" label="Class test date" :value="$filters['exam_date'] ?? ''" />
            <x-ui.button type="submit">Check Completion</x-ui.button>
        </form>
        @if($needsTestContext)
            <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Enter the exact class test topic and date, then check completion. A specific test must be selected to identify teachers who have not entered it.</p>
        @else
            <p class="text-sm text-slate-600">Completion for {{ $filters['session'] }} · {{ \App\Modules\Exams\Enums\ExamType::from($filters['exam_type'])->label() }}. Numeric marks (including zero) and valid grades count as entries. Completion measures entry coverage, not student pass rates.</p>
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="rounded-xl bg-white p-5"><p class="text-sm text-slate-500">Overall completion — all classes</p><strong class="text-2xl">{{ $report['overall']['percentage'] !== null ? number_format($report['overall']['percentage'], 2).'%' : 'N/A' }}</strong></div>
                <div class="rounded-xl bg-white p-5"><p class="text-sm text-slate-500">Expected entries</p><strong class="text-2xl">{{ $report['overall']['expected'] }}</strong></div>
                <div class="rounded-xl bg-white p-5"><p class="text-sm text-slate-500">Entered</p><strong class="text-2xl">{{ $report['overall']['entered'] }}</strong></div>
                <div class="rounded-xl bg-white p-5"><p class="text-sm text-slate-500">Missing</p><strong class="text-2xl">{{ $report['overall']['missing'] }}</strong></div>
            </div>
            <section class="overflow-x-auto rounded-xl bg-white p-5">
                <h3 class="mb-4 font-semibold">Class-wise completion</h3>
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Class</th><th class="p-2">Expected</th><th class="p-2">Entered</th><th class="p-2">Missing</th><th class="p-2">Completion</th><th class="p-2">Pending teacher/subject assignments</th></tr></thead>
                    <tbody>@forelse($report['classes']->when(!empty($filters['class_id']), fn ($rows) => $rows->where('id', $filters['class_id'])) as $class)
                        <tr class="border-b border-slate-100"><td class="p-2 font-medium">{{ $class['name'] }}</td><td class="p-2">{{ $class['expected'] }}</td><td class="p-2">{{ $class['entered'] }}</td><td class="p-2">{{ $class['missing'] }}</td><td class="p-2">
                            @if($class['percentage'] !== null){{ number_format($class['percentage'], 2) }}%<progress class="block h-2 w-28" max="100" value="{{ $class['percentage'] }}"></progress>
                            @else N/A — {{ $class['assignment_count'] ? 'No eligible students' : 'No subject-teacher assignments' }}@endif
                        </td><td class="p-2">{{ $class['pending'] }}</td></tr>
                    @empty<tr><td colspan="6" class="p-4 text-slate-500">No classes found.</td></tr>@endforelse</tbody>
                </table>
            </section>
            <section class="overflow-x-auto rounded-xl bg-white p-5">
                <h3 class="mb-4 font-semibold">Teacher and subject entry status</h3>
                <p class="mb-3 text-xs text-slate-500">Not started means no valid entries; Incomplete means some students are still missing entries.</p>
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Teacher</th><th class="p-2">Class / Subject</th><th class="p-2">Entered / Expected</th><th class="p-2">Missing</th><th class="p-2">Completion</th><th class="p-2">Status</th><th class="p-2">Last valid entry</th></tr></thead>
                    <tbody>@forelse($report['rows']->when(!empty($filters['class_id']), fn ($rows) => $rows->where('class_id', $filters['class_id'])) as $row)
                        <tr class="border-b border-slate-100"><td class="p-2">{{ $row['teacher'] }}</td><td class="p-2">{{ $row['class'] }}<br>{{ $row['subject'] }}</td><td class="p-2">{{ $row['entered'] }} / {{ $row['expected'] }}</td><td class="p-2">{{ $row['missing'] }}</td><td class="p-2">{{ $row['percentage'] !== null ? number_format($row['percentage'], 2).'%' : 'N/A' }}</td><td class="p-2 {{ $row['missing'] ? 'font-semibold text-amber-700' : 'text-slate-600' }}">{{ $row['status'] }}</td><td class="p-2">{{ $row['latest']?->timezone('Asia/Karachi')->format('d M Y, h:i A') ?? '—' }}</td></tr>
                    @empty<tr><td colspan="7" class="p-4 text-slate-500">No subject-teacher assignments found for this session.</td></tr>@endforelse</tbody>
                </table>
            </section>
            <p class="text-xs text-slate-500">Expected entries follow current active student rosters and subject enrolments, matching the teacher entry sheets. One entry is expected per student for each assigned teacher and subject; duplicate assignment rows and repeated uploads count once. Shared teaching assignments are tracked separately per teacher. Missing assignments or subject enrolments need configuration and cannot be included in the percentage. Overall completion is entered ÷ expected across all classes; the class filter changes the detail tables only.</p>
        @endif
    </div>
</x-app-layout>
