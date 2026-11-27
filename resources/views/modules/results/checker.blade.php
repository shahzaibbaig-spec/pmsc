<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Student Results</h2></x-slot>
    <div class="space-y-5">
        <p class="text-sm text-slate-600">Review recorded marks and published results for all students. Select Class Test Records in the sidebar for class test sheets.</p>
        <form method="GET" action="{{ route('result-checker.results.index') }}" class="grid gap-4 rounded-xl bg-white p-5 md:grid-cols-3">
            <label class="text-sm">Records
                <select name="source" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="marks" @selected(!$published)>Recorded marks</option>
                    <option value="published" @selected($published)>Published results</option>
                </select>
            </label>
            <label class="text-sm">Class
                <select name="class_id" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">All classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(($filters['class_id'] ?? '') == $class->id)>{{ trim($class->name.' '.$class->section) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">Session
                <select name="session" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">All sessions</option>
                    @foreach($sessions as $session)
                        <option @selected(($filters['session'] ?? '') === $session)>{{ $session }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">Exam type
                <select name="exam_type" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">All exam types</option>
                    @foreach($examTypes as $type)
                        <option value="{{ $type['value'] }}" @selected(($filters['exam_type'] ?? '') === $type['value'])>{{ $type['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <x-ui.input name="student" label="Student name or ID" :value="$filters['student'] ?? ''" />
            <div class="flex items-end gap-3"><x-ui.button type="submit">Apply Filters</x-ui.button><a href="{{ route('result-checker.results.index') }}" class="text-sm text-indigo-600">Reset</a></div>
        </form>
        <div class="overflow-x-auto rounded-xl bg-white p-4">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-2">Student</th><th class="p-2">Class</th><th class="p-2">Session</th><th class="p-2">Exam</th><th class="p-2">Subject</th><th class="p-2">Marks</th><th class="p-2">Grade</th></tr></thead>
                <tbody>
                    @forelse($records as $record)
                        @php($classRoom = $published ? $record->classRoom : $record->exam?->classRoom)
                        <tr class="border-b border-slate-100">
                            <td class="p-2">{{ $record->student?->name ?? 'Unknown student' }}<span class="block text-xs text-slate-500">{{ $record->student?->student_id }}</span></td>
                            <td class="p-2">{{ trim(($classRoom?->name ?? '').' '.($classRoom?->section ?? '')) }}</td>
                            <td class="p-2">{{ $record->session }}</td>
                            <td class="p-2">{{ $record->exam?->exam_label ?: ($published ? $record->exam_name : $record->exam?->exam_type?->label()) }}</td>
                            <td class="p-2">{{ $published ? $record->subject?->name : $record->exam?->subject?->name }}</td>
                            <td class="p-2">{{ $record->obtained_marks !== null ? $record->obtained_marks.' / '.($record->total_marks ?? $record->exam?->total_marks) : '—' }}</td>
                            <td class="p-2">{{ $record->grade ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-5 text-center text-slate-500">No results match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $records->links() }}</div>
        </div>
    </div>
</x-app-layout>
