<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Assessment Entries
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($profileError)
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $profileError }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                    <form method="GET" action="{{ route('teacher.marks.entries.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sessions</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Classes</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                        {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="subject_id" value="Subject" />
                            <select id="subject_id" name="subject_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Subjects</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" @selected((string) $filters['subject_id'] === (string) $subject->id)>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="exam_type" value="Exam Type" />
                            <select id="exam_type" name="exam_type" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Exam Types</option>
                                @foreach($examTypes as $examType)
                                    <option value="{{ $examType['value'] }}" @selected($filters['exam_type'] === $examType['value'])>
                                        {{ $examType['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="student_name" value="Student Name" />
                            <x-text-input id="student_name" name="student_name" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['student_name'] }}" placeholder="Search student" />
                        </div>

                        <div class="md:col-span-5 flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Apply Filters
                            </button>
                            <a href="{{ route('teacher.marks.entries.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[1120px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Exam</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Recorded Result</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Entry Mode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Entered At</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($entries as $mark)
                                @php
                                    $examTypeRaw = $mark->exam?->exam_type;
                                    $examTypeValue = $examTypeRaw instanceof \BackedEnum ? $examTypeRaw->value : (string) $examTypeRaw;
                                    $examLabel = $examTypeLabels[$examTypeValue] ?? str_replace('_', ' ', ucfirst($examTypeValue));
                                    $classLabel = trim(($mark->exam?->classRoom?->name ?? 'Class').' '.($mark->exam?->classRoom?->section ?? ''));
                                    $canEdit = (bool) $mark->getAttribute('can_edit');
                                    $usesGradeSystem = (bool) $mark->getAttribute('uses_grade_system');
                                    $gradeLabel = $mark->getAttribute('grade_label');
                                @endphp
                                <tr>
                                    <td class="sticky left-0 z-10 bg-white px-4 py-3 text-sm text-gray-800">
                                        <div class="font-medium">{{ $mark->student?->name ?? 'Student' }}</div>
                                        <div class="text-xs text-gray-500">{{ $mark->student?->student_id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $classLabel }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $mark->exam?->subject?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <div class="font-medium">{{ $examLabel }}</div>
                                        <div class="text-xs text-gray-500">{{ $mark->session }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @if ($usesGradeSystem)
                                            <div class="font-semibold text-gray-900">{{ $mark->grade ?? '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $gradeLabel ?? 'Grade-based entry' }}</div>
                                        @else
                                            <div class="font-semibold text-gray-900">{{ $mark->obtained_marks }}</div>
                                            <div class="text-xs text-gray-500">out of {{ $mark->total_marks }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @if ($usesGradeSystem)
                                            <span class="inline-flex rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">Grade</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">Marks</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ optional($mark->created_at)->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($canEdit)
                                                <a href="{{ route('teacher.marks.entries.edit', $mark) }}" class="inline-flex min-h-10 items-center rounded-md border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                                    Edit
                                                </a>
                                            @else
                                                <span class="inline-flex min-h-10 items-center rounded-md border border-gray-200 px-3 text-xs font-medium text-gray-400">
                                                    Edit Locked
                                                </span>
                                            @endif

                                            <form method="POST" action="{{ route('teacher.marks.entries.destroy', $mark) }}" class="inline-flex" data-delete-form>
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="edit_reason" value="">
                                                <button type="submit" class="inline-flex min-h-10 items-center rounded-md border border-red-300 px-3 text-xs font-medium text-red-700 hover:bg-red-50">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                                        No assessment entries found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4">
                    {{ $entries->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-delete-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const reason = window.prompt('Enter reason for deleting this mark entry:');
                if (reason === null) {
                    event.preventDefault();
                    return;
                }

                const trimmedReason = reason.trim();
                if (!trimmedReason) {
                    event.preventDefault();
                    window.alert('Delete reason is required.');
                    return;
                }

                const hiddenReason = form.querySelector('input[name="edit_reason"]');
                if (hiddenReason) {
                    hiddenReason.value = trimmedReason;
                }
            });
        });
    </script>
</x-app-layout>
