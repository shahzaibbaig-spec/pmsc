<x-app-layout>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Senior Progress Reports</h1>
            <p class="mt-1 text-sm text-slate-600">Mirpur · Grades 7–12 · Word reports in the supplied senior school format.</p>
        </div>
        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-4 text-red-800">{{ $errors->first() }}</div>
        @endif
        <form method="GET" class="grid gap-4 rounded-xl bg-white p-5 shadow-sm md:grid-cols-4">
            <label class="text-sm font-medium">Session
                <input name="session" value="{{ $session }}" required maxlength="20" onchange="this.form.elements.exam_id.value = ''" class="mt-1 block w-full rounded-lg border-slate-300">
            </label>
            <label class="text-sm font-medium">Class
                <select name="class_id" required onchange="this.form.elements.exam_id.value = ''" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">Select class</option>
                    @foreach ($classes as $option)
                        <option value="{{ $option->id }}" @selected($class?->id === $option->id)>{{ $option->name }} {{ $option->section }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium">Assessment
                <select name="exam_id" class="mt-1 block w-full rounded-lg border-slate-300">
                    <option value="">Select after loading class</option>
                    @foreach ($scopes as $scope)
                        <option value="{{ $scope['id'] }}" @selected($examId === $scope['id'])>{{ $scope['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <button class="self-end rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Load students</button>
        </form>
        <p class="text-sm text-slate-600">Select a session and class, load it, then select the assessment. Recorded grades and student details are filled automatically. Review each report to add progress, effort, skills, and remarks. Saved reviews are included in class downloads. Missing information stays blank.</p>
        @if ($class && $examId)
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">{{ $class->name }} {{ $class->section }} · {{ $students->count() }} students</h2>
                <form method="POST" action="{{ route('principal.senior-progress.class-download') }}">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $class->id }}">
                    <input type="hidden" name="session" value="{{ $session }}">
                    <input type="hidden" name="exam_id" value="{{ $examId }}">
                    <button @disabled($students->isEmpty()) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Download whole class (.zip)</button>
                </form>
            </div>
            <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left"><tr><th class="p-4">KORT ID</th><th class="p-4">Student</th><th class="p-4">Review</th><th class="p-4">Report</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $student)
                            @php($context = ['student' => $student->id, 'class_id' => $class->id, 'session' => $session, 'exam_id' => $examId])
                            <tr>
                                <td class="p-4">{{ $student->student_id }}</td><td class="p-4">{{ $student->name }}</td>
                                <td class="p-4">{{ in_array($student->id, $draftStudentIds) ? 'Saved review' : 'Not reviewed' }}</td>
                                <td class="flex gap-4 p-4"><a class="font-semibold text-indigo-700" href="{{ route('principal.senior-progress.preview', $context) }}">Review / complete</a><a class="text-indigo-700" href="{{ route('principal.senior-progress.download', $context) }}">Word</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-6 text-center text-slate-500">No students found for this class and session.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif ($class && $scopes->isEmpty())
            <p class="rounded-lg bg-amber-50 p-4 text-amber-900">No assessments are recorded for this class and session.</p>
        @endif
    </div>
</x-app-layout>
