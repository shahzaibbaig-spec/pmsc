<x-app-layout>
    @php
        $context = ['student' => $student->id, 'class_id' => $class->id, 'session' => $session, 'exam_id' => $scope->id];
        $progressOptions = ['Working towards expectations', 'Meeting expectations', 'Working above expectations'];
        $effortOptions = ['Sometimes tries hard', 'Tries hard most of the time', 'Always tries hard'];
        $skills = ['Communicates ideas confidently and persuasively', 'Expresses curiosity and interest in exploring new ideas', 'Asks questions to understand concepts better', 'Demonstrates active listening skills during discussions', 'Uses creativity in various subjects, including arts and sciences', 'Takes initiative in completing tasks and assignments', 'Manages time effectively to balance academics and extracurricular activities'];
        $skillOptions = ['Needs Improvement', 'Satisfactory', 'Good', 'Excellent'];
    @endphp
    <div class="space-y-6">
        <div class="flex flex-wrap justify-between gap-4">
            <div><h1 class="text-2xl font-semibold">{{ $student->name }} · Senior Progress Report</h1><p class="mt-1 text-sm text-slate-600">Mirpur · {{ $report['class_name'] }} · {{ $session }} · {{ $scope->display_name }}</p></div>
            <a class="text-sm font-semibold text-indigo-700" href="{{ route('principal.senior-progress.index', array_diff_key($context, ['student' => true])) }}">Back to class</a>
        </div>
        @if (session('success'))<div class="rounded-lg bg-green-50 p-4 text-green-800">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg bg-red-50 p-4 text-red-800">{{ $errors->first() }}</div>@endif
        <div class="grid gap-4 rounded-xl bg-white p-5 text-sm shadow-sm md:grid-cols-3">
            <div><span class="text-slate-500">Student KORT ID:</span> {{ $report['student_number'] }}</div>
            <div><span class="text-slate-500">Age:</span> {{ $report['age'] === '' ? 'Not recorded' : $report['age'].' years' }}</div>
            <div><span class="text-slate-500">Class teacher:</span> {{ $report['teacher_name'] ?: 'Not recorded' }}</div>
        </div>
        <form method="POST" action="{{ route('principal.senior-progress.save', ['student' => $student->id]) }}" class="space-y-6">
            @csrf
            <input type="hidden" name="class_id" value="{{ $class->id }}"><input type="hidden" name="session" value="{{ $session }}"><input type="hidden" name="exam_id" value="{{ $scope->id }}">
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left"><tr><th class="p-4">Subject</th><th class="p-4">Recorded grade</th><th class="p-4">Progress &amp; attainment</th><th class="p-4">Effort</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($report['subjects'] as $index => $subject)
                            <tr><td class="p-4">{{ $subject['name'] }}</td><td class="p-4">{{ $subject['grade'] ?: 'Not recorded' }}</td>
                                @foreach (['progress' => $progressOptions, 'effort' => $effortOptions] as $field => $options)
                                    <td class="p-4"><select aria-label="{{ ucfirst($field) }} for {{ $subject['name'] }}" name="{{ $field }}[{{ $index }}]" class="w-full rounded-lg border-slate-300 text-sm">
                                        <option value="">Not assessed</option>
                                        @foreach ($options as $value => $label)<option value="{{ $value }}" @selected((string) old($field.'.'.$index, $subject[$field] ?? '') === (string) $value)>{{ $label }}</option>@endforeach
                                    </select></td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-5 text-amber-800">No subject marks are recorded for this student in the selected assessment. The Word subject rows will remain blank.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="space-y-4 rounded-xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Skills development</h2>
                @foreach ($skills as $index => $label)
                    <label class="grid items-center gap-3 text-sm md:grid-cols-2"><span>{{ $label }}</span>
                        <select name="skills[{{ $index }}]" class="rounded-lg border-slate-300 text-sm"><option value="">Not assessed</option>
                            @foreach ($skillOptions as $value => $option)<option value="{{ $value }}" @selected((string) old('skills.'.$index, $report['skills'][$index] ?? '') === (string) $value)>{{ $option }}</option>@endforeach
                        </select>
                    </label>
                @endforeach
            </div>
            <div class="grid gap-5 rounded-xl bg-white p-5 shadow-sm md:grid-cols-2">
                <label class="text-sm font-medium">Class teacher’s remarks<textarea name="teacher_remarks" rows="7" maxlength="600" class="mt-2 block w-full rounded-lg border-slate-300">{{ old('teacher_remarks', $report['teacher_remarks']) }}</textarea></label>
                <label class="text-sm font-medium">Head teacher / section head’s remarks<textarea name="head_remarks" rows="7" maxlength="600" class="mt-2 block w-full rounded-lg border-slate-300">{{ old('head_remarks', $report['head_remarks']) }}</textarea></label>
            </div>
            <p class="text-sm text-slate-600">Save your changes before downloading. Grades come from the selected assessment; unrecorded ratings stay blank. Signatures, signature dates, and the student’s end-of-year comments remain available for completion in Word.</p>
            <div class="flex flex-wrap gap-4"><button class="rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Save review</button><a href="{{ route('principal.senior-progress.download', $context) }}" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">Download saved report (.docx)</a></div>
        </form>
    </div>
</x-app-layout>
