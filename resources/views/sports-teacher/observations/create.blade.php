<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">New Sports Observation</h2>
                <p class="mt-1 text-sm text-slate-500">Search student globally, select issue, preview message, and submit.</p>
            </div>
            <a href="{{ route('sports-teacher.observations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
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
            <form method="POST" action="{{ route('sports-teacher.observations.store') }}" class="space-y-5" id="sportsObservationForm">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected(old('session', $filters['session'] ?? null) === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="observation_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Observation Date</label>
                        <input id="observation_date" type="date" name="observation_date" value="{{ old('observation_date', now()->toDateString()) }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm" required>
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
                    <label for="student_search" class="block text-sm font-semibold text-slate-700">Global Student Search</label>
                    <input id="student_search" type="text" autocomplete="off" placeholder="Search by name, admission number, father name, class, section" class="mt-2 block w-full rounded-xl border-slate-300 text-sm" value="{{ old('student_name') }}">
                    <input type="hidden" id="student_id" name="student_id" value="{{ old('student_id') }}">
                    <div id="selected_student" class="mt-3 {{ old('student_id') ? '' : 'hidden' }} rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700"></div>
                    <div id="student_results" class="mt-3 hidden divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white"></div>
                    <p class="mt-2 text-xs text-slate-500">Active students are searchable by admission number, name, father name, class, and section.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="issue_type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Issue Type</label>
                        <select id="issue_type" name="issue_type" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm" required>
                            <option value="">Select Issue</option>
                            @foreach ($issue_options as $key => $label)
                                <option value="{{ $key }}" @selected(old('issue_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="custom_note" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Custom Note (Optional)</label>
                        <textarea id="custom_note" name="custom_note" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" placeholder="Add extra context (optional)">{{ old('custom_note') }}</textarea>
                    </div>
                </div>

                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Auto-generated Message Preview</p>
                    <p id="auto_message_preview" class="mt-2 whitespace-pre-line text-sm text-indigo-900">Select student and issue type to preview the message.</p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-semibold">Duplicate Handling</p>
                    <p class="mt-1">If the same student already has the same issue on the same date/session, the system warns before saving. You can still submit by choosing repeated severity or confirming below.</p>
                    <label class="mt-3 inline-flex items-center gap-2">
                        <input type="checkbox" name="confirm_duplicate" value="1" @checked(old('confirm_duplicate')) class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        <span>Confirm duplicate entry if warning appears.</span>
                    </label>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Submit Observation
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-base font-semibold text-slate-900">Recent Observations</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Issue</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($observations as $observation)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-900">{{ $observation->student?->name }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ trim(($observation->classRoom?->name ?? '').' '.($observation->classRoom?->section ?? '')) ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $observation->issue_label }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ ucfirst($observation->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No observations yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const endpoint = @json(route('sports-teacher.students.search'));
            const issueTemplates = @json($issue_options);
            const previewTemplates = {
                nails_not_cut: 'Student {student_name} of {class_section} came to sports class with nails not properly cut. Kindly ensure personal hygiene is maintained.',
                hair_not_cut: 'Student {student_name} of {class_section} needs a proper haircut as per school discipline policy.',
                uniform_not_neat: 'Student {student_name} of {class_section} was observed with an untidy uniform during sports class.',
                shoes_not_polished: 'Student {student_name} of {class_section} came with shoes not properly polished.',
                not_clean: 'Student {student_name} of {class_section} was not properly clean and needs attention regarding personal hygiene.',
                poor_sports_discipline: 'Student {student_name} of {class_section} showed poor discipline during sports class and needs guidance.',
            };

            const searchInput = document.getElementById('student_search');
            const studentIdInput = document.getElementById('student_id');
            const selectedStudent = document.getElementById('selected_student');
            const resultBox = document.getElementById('student_results');
            const issueTypeInput = document.getElementById('issue_type');
            const customNoteInput = document.getElementById('custom_note');
            const previewBox = document.getElementById('auto_message_preview');
            const sessionInput = document.getElementById('session');

            if (!searchInput || !studentIdInput || !selectedStudent || !resultBox || !issueTypeInput || !customNoteInput || !previewBox || !sessionInput) {
                return;
            }

            let selected = null;

            const setPreview = () => {
                const issueType = issueTypeInput.value;
                const template = previewTemplates[issueType] || '';

                if (!template) {
                    previewBox.textContent = 'Select student and issue type to preview the message.';
                    return;
                }

                const studentName = selected?.student_name || 'Student';
                const classSection = selected?.class_section || 'Unknown Class';
                let message = template
                    .replace('{student_name}', studentName)
                    .replace('{class_section}', classSection);

                const customNote = customNoteInput.value.trim();
                if (customNote !== '') {
                    message += ` Note: ${customNote}`;
                }

                previewBox.textContent = message;
            };

            const renderSelected = (student) => {
                selected = student;
                studentIdInput.value = String(student.id);
                selectedStudent.innerHTML = `<strong>${window.NSMS.escapeHtml(student.student_name)}</strong> | ${window.NSMS.escapeHtml(student.admission_no)} | ${window.NSMS.escapeHtml(student.class_section)} | ${window.NSMS.escapeHtml(student.father_name || '-')}`;
                selectedStudent.classList.remove('hidden');
                resultBox.classList.add('hidden');
                resultBox.innerHTML = '';
                setPreview();
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
                if (searchInput.value.trim() === '') {
                    selected = null;
                    studentIdInput.value = '';
                    selectedStudent.classList.add('hidden');
                    selectedStudent.textContent = '';
                }
                fetchStudents();
            });

            issueTypeInput.addEventListener('change', setPreview);
            customNoteInput.addEventListener('input', setPreview);

            resultBox.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-student]');
                if (!button) {
                    return;
                }

                try {
                    const student = JSON.parse(button.getAttribute('data-student'));
                    renderSelected(student);
                } catch (_) {
                    // ignore malformed payload
                }
            });

            document.addEventListener('click', (event) => {
                if (!resultBox.contains(event.target) && event.target !== searchInput) {
                    resultBox.classList.add('hidden');
                }
            });

            if (studentIdInput.value !== '') {
                selectedStudent.classList.remove('hidden');
                selectedStudent.textContent = 'Student selected from previous submission. Search again if you need to change.';
            }

            setPreview();
        })();
    </script>
</x-app-layout>
