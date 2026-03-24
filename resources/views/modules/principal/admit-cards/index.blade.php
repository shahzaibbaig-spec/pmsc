<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Admit Card Generator</h2>
                <p class="mt-1 text-sm text-slate-500">Generate single and class admit cards with fee-defaulter enforcement.</p>
            </div>
            <a
                href="{{ route('principal.admit-cards.overrides.index', ['exam_session_id' => $selectedExamSessionId]) }}"
                class="inline-flex min-h-10 items-center rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100"
            >
                Manage Overrides
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Create Exam Session</h3>
            <p class="mt-1 text-xs text-slate-500">Define the exam period that will appear on admit cards.</p>

            <form method="POST" action="{{ route('principal.admit-cards.exam-sessions.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                @csrf
                <div>
                    <label for="name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Name</label>
                    <input id="name" name="name" type="text" required placeholder="Final Term 2026" class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Academic Session</label>
                    <input id="session" name="session" type="text" required placeholder="2025-2026" class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="start_date" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Start Date</label>
                    <input id="start_date" name="start_date" type="date" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="end_date" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">End Date</label>
                    <input id="end_date" name="end_date" type="date" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-4">
                    <x-ui.button type="submit">Create Exam Session</x-ui.button>
                </div>
            </form>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Generate Single Admit Card</h3>
                <p class="mt-1 text-xs text-slate-500">Select class and student. Blocked students require override.</p>

                <form id="single-admit-card-form" method="GET" action="{{ route('principal.admit-cards.single-pdf') }}" target="_blank" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="single_exam_session_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</label>
                        <select id="single_exam_session_id" name="exam_session_id" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select exam session</option>
                            @foreach($examSessions as $examSession)
                                <option value="{{ $examSession->id }}" @selected((string) $selectedExamSessionId === (string) $examSession->id)>
                                    {{ $examSession->name }} ({{ $examSession->session }}) - {{ optional($examSession->start_date)->format('d M Y') }} to {{ optional($examSession->end_date)->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="single_class_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select id="single_class_id" class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select class</option>
                            @foreach($classes as $classRoom)
                                <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="single_student_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Student</label>
                        <select id="single_student_id" name="student_id" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select student</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.button type="submit">Generate Single Admit Card PDF</x-ui.button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Generate Class Admit Cards</h3>
                <p class="mt-1 text-xs text-slate-500">PDF generates for all eligible students in selected class.</p>

                <form method="GET" action="{{ route('principal.admit-cards.class-pdf') }}" target="_blank" class="mt-4 grid grid-cols-1 gap-4">
                    <div>
                        <label for="class_exam_session_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</label>
                        <select id="class_exam_session_id" name="exam_session_id" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select exam session</option>
                            @foreach($examSessions as $examSession)
                                <option value="{{ $examSession->id }}" @selected((string) $selectedExamSessionId === (string) $examSession->id)>
                                    {{ $examSession->name }} ({{ $examSession->session }}) - {{ optional($examSession->start_date)->format('d M Y') }} to {{ optional($examSession->end_date)->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="class_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select id="class_id" name="class_id" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select class</option>
                            @foreach($classes as $classRoom)
                                <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-ui.button type="submit">Generate Class Admit Cards PDF</x-ui.button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        const singleClassSelect = document.getElementById('single_class_id');
        const singleStudentSelect = document.getElementById('single_student_id');

        async function loadStudentsForClass(classId) {
            if (!classId) {
                singleStudentSelect.innerHTML = '<option value="">Select student</option>';
                return;
            }

            const params = new URLSearchParams({ class_id: classId });
            const response = await fetch(`{{ route('principal.results.students') }}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                singleStudentSelect.innerHTML = '<option value="">No students found</option>';
                return;
            }

            const result = await response.json();
            const students = result.students || [];

            if (!students.length) {
                singleStudentSelect.innerHTML = '<option value="">No students found</option>';
                return;
            }

            singleStudentSelect.innerHTML = students
                .map((student) => `<option value="${student.id}">${student.name} (${student.student_id || '-'})</option>`)
                .join('');
        }

        singleClassSelect.addEventListener('change', (event) => {
            loadStudentsForClass(Number(event.target.value || 0));
        });
    </script>
</x-app-layout>
