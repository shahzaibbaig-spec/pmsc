<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Assign KCAT</h2></x-slot>

    <div class="mx-auto max-w-4xl py-8">
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('career-counselor.kcat.assignments.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label class="text-sm font-semibold text-slate-700">KCAT Test</label>
                <select name="kcat_test_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
                    @forelse ($tests as $test)
                        <option value="{{ $test->id }}" @selected((string) old('kcat_test_id') === (string) $test->id)>{{ $test->title }} ({{ $test->active_questions_count }} active questions)</option>
                    @empty
                        <option value="">No active KCAT test with questions available</option>
                    @endforelse
                </select>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Assign To</label>
                    <select id="assignToType" name="assigned_to_type" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
                        <option value="student" @selected(old('assigned_to_type', 'student') === 'student')>Student</option>
                        <option value="class" @selected(old('assigned_to_type') === 'class')>Class</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                </div>
            </div>

            <section id="studentAssignBlock" class="space-y-3 rounded-xl border border-blue-100 bg-blue-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <label for="studentSearchInput" class="text-sm font-semibold text-slate-700">Student Search (Grade 7 to 12)</label>
                    <button type="button" id="clearSelectedStudent" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">Clear</button>
                </div>
                <input id="studentSearchInput" type="text" autocomplete="off" placeholder="Search by student name, admission number, father name, class or section" class="block w-full rounded-xl border-slate-300 text-sm">
                <input type="hidden" id="selectedStudentId" name="student_id" value="{{ old('student_id') }}">
                <div id="selectedStudentDisplay" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700"></div>
                <div id="studentSearchResults" class="hidden divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white"></div>
                <p class="text-xs text-slate-500">Only Grade 7 to Grade 12 students appear in this global search.</p>
            </section>

            <div id="classAssignBlock" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Class</label>
                    <select id="classIdField" name="class_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                        <option value="">Select class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->name }} {{ $class->section }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Section</label>
                    <input id="sectionField" name="section" value="{{ old('section') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                </div>
            </div>
            <div class="flex justify-end"><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" @disabled($tests->isEmpty())>Assign</button></div>
        </form>
    </div>

    <script>
        (() => {
            const assignTo = document.getElementById('assignToType');
            const studentBlock = document.getElementById('studentAssignBlock');
            const classBlock = document.getElementById('classAssignBlock');
            const selectedStudentId = document.getElementById('selectedStudentId');
            const studentSearchInput = document.getElementById('studentSearchInput');
            const selectedStudentDisplay = document.getElementById('selectedStudentDisplay');
            const clearSelectedStudent = document.getElementById('clearSelectedStudent');
            const results = document.getElementById('studentSearchResults');
            const classIdField = document.getElementById('classIdField');
            const sectionField = document.getElementById('sectionField');
            const endpoint = @json(route('career-counselor.kcat.assignments.students.search'));

            if (!assignTo || !studentBlock || !classBlock || !selectedStudentId || !studentSearchInput || !selectedStudentDisplay || !clearSelectedStudent || !results || !classIdField || !sectionField) {
                return;
            }

            const setMode = () => {
                const isStudent = assignTo.value === 'student';
                studentBlock.classList.toggle('hidden', !isStudent);
                classBlock.classList.toggle('hidden', isStudent);
                classIdField.disabled = isStudent;
                sectionField.disabled = isStudent;
                selectedStudentId.disabled = !isStudent;
            };

            const clearSelection = () => {
                selectedStudentId.value = '';
                selectedStudentDisplay.classList.add('hidden');
                selectedStudentDisplay.textContent = '';
            };

            const setSelection = (student) => {
                selectedStudentId.value = String(student.id);
                selectedStudentDisplay.innerHTML = `
                    <span class="font-semibold">${window.NSMS.escapeHtml(student.name)}</span>
                    <span class="text-slate-600"> | ${window.NSMS.escapeHtml(student.admission_number || '-')}, ${window.NSMS.escapeHtml(student.class_section || '-')}</span>
                `;
                selectedStudentDisplay.classList.remove('hidden');
                results.classList.add('hidden');
                results.innerHTML = '';
            };

            const renderResults = (students) => {
                if (!Array.isArray(students) || students.length === 0) {
                    results.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">No matching Grade 7 to 12 students found.</div>';
                    results.classList.remove('hidden');
                    return;
                }

                results.innerHTML = students.map((student) => `
                    <button
                        type="button"
                        data-student-id="${student.id}"
                        data-student-name="${encodeURIComponent(student.name || '')}"
                        data-student-admission="${encodeURIComponent(student.admission_number || '')}"
                        data-student-class="${encodeURIComponent(student.class_section || '')}"
                        class="block w-full px-3 py-2 text-left hover:bg-slate-50"
                    >
                        <div class="text-sm font-semibold text-slate-900">${window.NSMS.escapeHtml(student.name)}</div>
                        <div class="text-xs text-slate-500">${window.NSMS.escapeHtml(student.admission_number || '-')} | ${window.NSMS.escapeHtml(student.class_section || '-')} | ${window.NSMS.escapeHtml(student.father_name || '-')}</div>
                    </button>
                `).join('');
                results.classList.remove('hidden');
            };

            const fetchStudents = window.NSMS.debounce(async () => {
                const term = studentSearchInput.value.trim();
                if (term.length < 2) {
                    results.classList.add('hidden');
                    results.innerHTML = '';
                    return;
                }

                const response = await fetch(`${endpoint}?term=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    results.classList.add('hidden');
                    return;
                }

                const payload = await response.json();
                renderResults(payload.data || []);
            }, 250);

            assignTo.addEventListener('change', setMode);
            clearSelectedStudent.addEventListener('click', clearSelection);
            studentSearchInput.addEventListener('input', fetchStudents);
            results.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-student-id]');
                if (!button) {
                    return;
                }

                const id = Number(button.getAttribute('data-student-id') || 0);
                if (!Number.isFinite(id) || id <= 0) {
                    return;
                }

                setSelection({
                    id,
                    name: decodeURIComponent(button.getAttribute('data-student-name') || ''),
                    admission_number: decodeURIComponent(button.getAttribute('data-student-admission') || ''),
                    class_section: decodeURIComponent(button.getAttribute('data-student-class') || ''),
                });
            });

            document.addEventListener('click', (event) => {
                if (!results.contains(event.target) && event.target !== studentSearchInput) {
                    results.classList.add('hidden');
                }
            });

            if (selectedStudentId.value !== '') {
                selectedStudentDisplay.textContent = `Selected student ID: ${selectedStudentId.value}`;
                selectedStudentDisplay.classList.remove('hidden');
            }

            setMode();
        })();
    </script>
</x-app-layout>
