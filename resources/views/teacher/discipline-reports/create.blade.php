<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">New Class Discipline Report</h2>
                <p class="mt-1 text-sm text-slate-500">Search assigned students, select one or more students from class, choose issue and severity, preview message, and submit.</p>
            </div>
            <a href="{{ route('teacher.discipline-reports.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
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

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('teacher.discipline-reports.store') }}" class="space-y-5" id="disciplineReportForm">
                @csrf
                @php
                    $oldStudentIds = collect((array) old('student_ids', []))
                        ->filter(fn ($id) => $id !== null && $id !== '')
                        ->map(fn ($id) => (int) $id)
                        ->filter(fn ($id) => $id > 0)
                        ->values()
                        ->all();

                    if ($oldStudentIds === [] && old('student_id')) {
                        $legacyStudentId = (int) old('student_id');
                        if ($legacyStudentId > 0) {
                            $oldStudentIds = [$legacyStudentId];
                        }
                    }
                @endphp

                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected(old('session', $filters['session'] ?? null) === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="report_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Report Date</label>
                        <input id="report_date" type="date" name="report_date" value="{{ old('report_date', now()->toDateString()) }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm" required>
                    </div>
                    <div>
                        <label for="issue_type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Issue Type</label>
                        <select id="issue_type" name="issue_type" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm" required>
                            <option value="">Select issue</option>
                            @foreach ($issue_options as $key => $label)
                                <option value="{{ $key }}" @selected(old('issue_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="severity" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</label>
                        <select id="severity" name="severity" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm" required>
                            @foreach ($severity_options as $severity)
                                <option value="{{ $severity }}" @selected(old('severity', 'normal') === $severity)>{{ ucfirst($severity) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <label for="student_search" class="block text-sm font-semibold text-slate-700">AJAX Student Search</label>
                    <input id="student_search" type="text" autocomplete="off" placeholder="Search by name, admission number, roll number, father name, class, section" class="mt-2 block w-full rounded-xl border-slate-300 text-sm">
                    <div id="student_id_inputs">
                        @foreach ($oldStudentIds as $oldStudentId)
                            <input type="hidden" name="student_ids[]" value="{{ (int) $oldStudentId }}">
                        @endforeach
                    </div>
                    <div id="selected_students_panel" class="mt-3 {{ $oldStudentIds !== [] ? '' : 'hidden' }} rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        <p id="selected_students_count">Selected students: {{ count($oldStudentIds) }}</p>
                        <div id="selected_students" class="mt-2 flex flex-wrap gap-2"></div>
                    </div>
                    <div id="student_results" class="mt-3 hidden divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white"></div>
                    <p class="mt-2 text-xs text-slate-500">Only students from your assigned classes are searchable for selected session. You can add multiple students before submit.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="subject_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                        <select id="subject_id" name="subject_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                            <option value="">Select after students</option>
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Required when multiple assigned subjects are available for this class/student.</p>
                    </div>
                    <div>
                        <label for="description" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Description / Note (Optional)</label>
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" placeholder="Additional context (optional)">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Auto-generated Message Preview</p>
                    <p id="auto_message_preview" class="mt-2 whitespace-pre-line text-sm text-indigo-900">Select students and issue type to preview auto message.</p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-semibold">Duplicate Handling</p>
                    <p class="mt-1">If the same issue for any selected student is already reported by you for this date/session, system will warn. You can still proceed for repeated/serious/urgent severity or confirm below.</p>
                    <label class="mt-3 inline-flex items-center gap-2">
                        <input type="checkbox" name="confirm_duplicate" value="1" @checked(old('confirm_duplicate')) class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        <span>Confirm duplicate entry if warning appears.</span>
                    </label>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Submit Report
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-base font-semibold text-slate-900">Recent Reports</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class / Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Issue</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($reports as $report)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ optional($report->report_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-900">{{ $report->student?->name }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    {{ trim(($report->classRoom?->name ?? '').' '.($report->classRoom?->section ?? '')) ?: '-' }}
                                    <p class="text-xs text-slate-500">{{ $report->subject?->name ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $report->issue_label }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ ucfirst($report->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No reports yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const endpoint = @json(route('teacher.discipline-reports.students.search'));
            const issueOptions = @json($issue_options);
            const teacherName = @json(auth()->user()?->name ?? 'Teacher');
            const oldStudentIds = @json($oldStudentIds);
            const oldSubjectId = @json(old('subject_id') !== null && old('subject_id') !== '' ? (int) old('subject_id') : null);
            const issueTemplates = {
                late_to_class: '{student_name} of {class_section} was reported by {teacher_name} for being late to class.',
                homework_not_completed: '{student_name} of {class_section} was reported by {teacher_name} for not completing homework in {subject_name}.',
                class_disturbance: '{student_name} of {class_section} was reported by {teacher_name} for disturbing the class.',
                disrespectful_behavior: '{student_name} of {class_section} was reported by {teacher_name} for disrespectful behavior during class.',
                fighting_aggression: '{student_name} of {class_section} was reported by {teacher_name} for fighting or aggressive behavior.',
                bullying: '{student_name} of {class_section} was reported by {teacher_name} for bullying behavior.',
                abusive_language: '{student_name} of {class_section} was reported by {teacher_name} for using abusive language.',
                uniform_issue: '{student_name} of {class_section} was reported by {teacher_name} for uniform-related discipline concern.',
                mobile_phone_misuse: '{student_name} of {class_section} was reported by {teacher_name} for mobile phone misuse during class.',
                cheating_dishonesty: '{student_name} of {class_section} was reported by {teacher_name} for cheating or dishonest conduct.',
                leaving_class_without_permission: '{student_name} of {class_section} was reported by {teacher_name} for leaving class without permission.',
                repeated_negligence: '{student_name} of {class_section} was reported by {teacher_name} for repeated negligence in class discipline.',
                other: '{student_name} of {class_section} was reported by {teacher_name} for a discipline concern.',
            };

            const searchInput = document.getElementById('student_search');
            const sessionInput = document.getElementById('session');
            const studentIdInputs = document.getElementById('student_id_inputs');
            const selectedPanel = document.getElementById('selected_students_panel');
            const selectedCount = document.getElementById('selected_students_count');
            const selectedList = document.getElementById('selected_students');
            const resultBox = document.getElementById('student_results');
            const issueTypeInput = document.getElementById('issue_type');
            const subjectInput = document.getElementById('subject_id');
            const descriptionInput = document.getElementById('description');
            const previewBox = document.getElementById('auto_message_preview');

            if (
                !searchInput ||
                !sessionInput ||
                !studentIdInputs ||
                !selectedPanel ||
                !selectedCount ||
                !selectedList ||
                !resultBox ||
                !issueTypeInput ||
                !subjectInput ||
                !descriptionInput ||
                !previewBox
            ) {
                return;
            }

            const selectedById = new Map();
            let shouldApplyOldSubject = oldSubjectId !== null;

            const escapeHtml = (value) => window.NSMS.escapeHtml(String(value ?? ''));

            const hydrateSubjects = (subjects = []) => {
                const currentSubjectValue = subjectInput.value;
                const options = ['<option value="">Select subject</option>'];

                for (const subject of subjects) {
                    options.push(`<option value="${subject.id}">${escapeHtml(subject.name)}</option>`);
                }

                if (subjects.length === 0 && shouldApplyOldSubject && oldSubjectId !== null) {
                    options.push(`<option value="${oldSubjectId}" selected>Previously selected subject</option>`);
                    subjectInput.innerHTML = options.join('');
                    shouldApplyOldSubject = false;
                    return;
                }

                subjectInput.innerHTML = options.join('');

                if (currentSubjectValue && subjects.some((subject) => String(subject.id) === String(currentSubjectValue))) {
                    subjectInput.value = currentSubjectValue;
                    return;
                }

                if (shouldApplyOldSubject && oldSubjectId !== null && subjects.some((subject) => Number(subject.id) === Number(oldSubjectId))) {
                    subjectInput.value = String(oldSubjectId);
                    shouldApplyOldSubject = false;
                    return;
                }

                if (subjects.length === 1) {
                    subjectInput.value = String(subjects[0].id);
                    shouldApplyOldSubject = false;
                }
            };

            const sharedSubjectOptions = () => {
                const selectedStudents = Array.from(selectedById.values());
                const withSubjects = selectedStudents.filter((student) => Array.isArray(student.subjects) && student.subjects.length > 0);
                if (withSubjects.length === 0) {
                    return [];
                }

                const first = withSubjects[0].subjects || [];
                const common = new Map();
                for (const subject of first) {
                    common.set(Number(subject.id), String(subject.name || 'Subject'));
                }

                for (let index = 1; index < withSubjects.length; index += 1) {
                    const ids = new Set((withSubjects[index].subjects || []).map((subject) => Number(subject.id)));
                    for (const subjectId of Array.from(common.keys())) {
                        if (!ids.has(subjectId)) {
                            common.delete(subjectId);
                        }
                    }
                }

                return Array.from(common.entries())
                    .map(([id, name]) => ({ id, name }))
                    .sort((a, b) => String(a.name).localeCompare(String(b.name)));
            };

            const syncStudentInputs = () => {
                studentIdInputs.innerHTML = '';
                for (const student of selectedById.values()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'student_ids[]';
                    input.value = String(student.id);
                    studentIdInputs.appendChild(input);
                }
            };

            const renderSelectedStudents = () => {
                const selectedStudents = Array.from(selectedById.values());
                if (selectedStudents.length === 0) {
                    selectedPanel.classList.add('hidden');
                    selectedList.innerHTML = '';
                    selectedCount.textContent = 'Selected students: 0';
                    return;
                }

                selectedPanel.classList.remove('hidden');
                selectedCount.textContent = `Selected students: ${selectedStudents.length}`;
                selectedList.innerHTML = selectedStudents.map((student) => {
                    const name = student.student_name || `Student #${student.id}`;
                    const classInfo = student.class_section || '';
                    return `
                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300 bg-white px-3 py-1 text-xs text-emerald-800">
                            <span>${escapeHtml(name)}${classInfo ? ` | ${escapeHtml(classInfo)}` : ''}</span>
                            <button type="button" class="text-emerald-700 hover:text-emerald-900" data-remove-student="${Number(student.id)}" aria-label="Remove student">&times;</button>
                        </span>
                    `;
                }).join('');
            };

            const previewMessage = () => {
                const issueType = issueTypeInput.value;
                if (!issueType || !issueOptions[issueType]) {
                    previewBox.textContent = 'Select students and issue type to preview auto message.';
                    return;
                }

                const selectedStudents = Array.from(selectedById.values());
                if (selectedStudents.length === 0) {
                    previewBox.textContent = 'Select students and issue type to preview auto message.';
                    return;
                }

                const previewStudent = selectedStudents[0];
                const studentName = selectedStudents.length === 1
                    ? (previewStudent.student_name || 'Student')
                    : `${selectedStudents.length} students`;
                const classSection = previewStudent.class_section || 'Unknown Class';
                const subjectName = subjectInput.selectedOptions[0]?.textContent?.trim() || 'the subject';
                const template = issueTemplates[issueType] || issueTemplates.other;
                let message = template
                    .replaceAll('{student_name}', studentName)
                    .replaceAll('{class_section}', classSection)
                    .replaceAll('{teacher_name}', teacherName)
                    .replaceAll('{subject_name}', subjectName);

                const note = descriptionInput.value.trim();
                if (note !== '') {
                    message += ` Note: ${note}`;
                }

                previewBox.textContent = message;
            };

            const addStudent = (student) => {
                const studentId = Number(student.id || 0);
                if (studentId <= 0) {
                    return;
                }

                selectedById.set(studentId, {
                    id: studentId,
                    student_name: String(student.student_name || ''),
                    admission_no: String(student.admission_no || ''),
                    class_section: String(student.class_section || ''),
                    father_name: String(student.father_name || ''),
                    subjects: Array.isArray(student.subjects) ? student.subjects : [],
                });

                syncStudentInputs();
                renderSelectedStudents();
                hydrateSubjects(sharedSubjectOptions());
                previewMessage();

                resultBox.classList.add('hidden');
                resultBox.innerHTML = '';
                searchInput.value = '';
            };

            const renderResults = (students) => {
                if (!Array.isArray(students) || students.length === 0) {
                    resultBox.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500">No students found.</div>';
                    resultBox.classList.remove('hidden');
                    return;
                }

                resultBox.innerHTML = students.map((student) => `
                    <button type="button" class="block w-full px-3 py-2 text-left hover:bg-slate-50" data-student='${JSON.stringify(student).replace(/'/g, '&#39;')}'>
                        <div class="text-sm font-semibold text-slate-900">${window.NSMS.escapeHtml(student.student_name)}</div>
                        <div class="text-xs text-slate-500">${window.NSMS.escapeHtml(student.admission_no)} | ${window.NSMS.escapeHtml(student.class_section)} | ${window.NSMS.escapeHtml(student.father_name || '-')}</div>
                        <div class="mt-1 text-[11px] font-semibold text-emerald-700">${selectedById.has(Number(student.id)) ? 'Added' : 'Add to selection'}</div>
                    </button>
                `).join('');
                resultBox.classList.remove('hidden');
            };

            const fetchStudents = window.NSMS.debounce(async () => {
                const term = searchInput.value.trim();
                if (term.length < 2) {
                    resultBox.classList.add('hidden');
                    resultBox.innerHTML = '';
                    return;
                }

                const response = await fetch(`${endpoint}?term=${encodeURIComponent(term)}&session=${encodeURIComponent(sessionInput.value || '')}`, {
                    headers: { Accept: 'application/json' },
                }).catch(() => null);

                if (!response || !response.ok) {
                    resultBox.classList.add('hidden');
                    return;
                }

                const payload = await response.json();
                renderResults(payload.data || []);
            }, 250);

            searchInput.addEventListener('input', () => {
                fetchStudents();
            });

            sessionInput.addEventListener('change', () => {
                selectedById.clear();
                syncStudentInputs();
                renderSelectedStudents();
                hydrateSubjects([]);
                previewMessage();
                resultBox.classList.add('hidden');
                resultBox.innerHTML = '';
            });

            issueTypeInput.addEventListener('change', previewMessage);
            subjectInput.addEventListener('change', previewMessage);
            descriptionInput.addEventListener('input', previewMessage);

            resultBox.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-student]');
                if (!button) {
                    return;
                }

                try {
                    const student = JSON.parse(button.getAttribute('data-student'));
                    addStudent(student);
                } catch (_) {
                    // ignore malformed payload
                }
            });

            selectedList.addEventListener('click', (event) => {
                const removeButton = event.target.closest('button[data-remove-student]');
                if (!removeButton) {
                    return;
                }

                const studentId = Number(removeButton.getAttribute('data-remove-student') || 0);
                if (studentId <= 0) {
                    return;
                }

                selectedById.delete(studentId);
                syncStudentInputs();
                renderSelectedStudents();
                hydrateSubjects(sharedSubjectOptions());
                previewMessage();
            });

            document.addEventListener('click', (event) => {
                if (!resultBox.contains(event.target) && event.target !== searchInput) {
                    resultBox.classList.add('hidden');
                }
            });

            for (const studentId of oldStudentIds) {
                const parsedId = Number(studentId || 0);
                if (parsedId <= 0) {
                    continue;
                }

                selectedById.set(parsedId, {
                    id: parsedId,
                    student_name: `Student #${parsedId}`,
                    class_section: '',
                    father_name: '',
                    admission_no: '',
                    subjects: [],
                });
            }

            syncStudentInputs();
            renderSelectedStudents();
            hydrateSubjects(sharedSubjectOptions());
            previewMessage();
        })();
    </script>
</x-app-layout>
