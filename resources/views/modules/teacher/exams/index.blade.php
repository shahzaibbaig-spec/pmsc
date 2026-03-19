<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Examination Marks Entry
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Exam Setup</h3>
                    <p class="mt-1 text-sm text-gray-600">Select class, subject, exam type and session to load marks. Tables are compact and mobile-friendly.</p>
                    @if (! $hasAssignments)
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            No subject assignment found for your account. Ask Principal to assign your class + subject for the current session.
                        </div>
                    @endif

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}">{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                        </div>

                        <div>
                            <x-input-label for="subject_id" value="Subject" />
                            <select id="subject_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                        </div>

                        <div>
                            <x-input-label for="exam_type" value="Exam Type" />
                            <select id="exam_type" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($examTypes as $examType)
                                    <option value="{{ $examType['value'] }}">{{ $examType['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="total_marks" value="Total Marks" />
                            <x-text-input id="total_marks" type="number" min="1" class="mt-1 block min-h-11 w-full" placeholder="100" />
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button id="loadSheetBtn" type="button" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Load Students
                        </button>
                        <button id="saveMarksBtn" type="button" class="inline-flex min-h-11 items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Save Marks
                        </button>
                    </div>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <label for="perPage" class="text-xs font-medium uppercase tracking-wider text-gray-500">Per Page</label>
                            <select id="perPage" class="mt-1 block min-h-10 w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <p id="paginationInfo" class="text-sm text-gray-600">-</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[760px] divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="sticky left-0 z-20 bg-gray-50 px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Father Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Obtained Marks</th>
                                </tr>
                            </thead>
                            <tbody id="marksBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Select options and load students.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button id="prevPageBtn" type="button" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            Previous
                        </button>
                        <button id="nextPageBtn" type="button" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const assignments = @json($assignments);
        const sessionInput = document.getElementById('session');
        const classInput = document.getElementById('class_id');
        const subjectInput = document.getElementById('subject_id');
        const examTypeInput = document.getElementById('exam_type');
        const totalMarksInput = document.getElementById('total_marks');
        const loadSheetBtn = document.getElementById('loadSheetBtn');
        const saveMarksBtn = document.getElementById('saveMarksBtn');
        const marksBody = document.getElementById('marksBody');
        const messageBox = document.getElementById('messageBox');
        const perPageInput = document.getElementById('perPage');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');

        let state = {
            students: [],
            locked: false,
            page: 1,
            per_page: 10,
        };

        function showMessage(message, type = 'success') {
            messageBox.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            messageBox.textContent = message;

            if (type === 'error') {
                messageBox.classList.add('bg-red-50', 'text-red-700');
            } else {
                messageBox.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function clearMessage() {
            messageBox.classList.add('hidden');
            messageBox.textContent = '';
        }

        function totalPages() {
            return Math.max(1, Math.ceil(state.students.length / state.per_page));
        }

        function pagedStudents() {
            const start = (state.page - 1) * state.per_page;
            return state.students.slice(start, start + state.per_page);
        }

        function filteredAssignments() {
            const session = sessionInput.value;
            return assignments.filter(item => item.session === session);
        }

        function buildClassOptions() {
            const rows = filteredAssignments();
            const classMap = new Map();

            rows.forEach(row => {
                if (!classMap.has(row.class_id)) {
                    classMap.set(row.class_id, {
                        name: row.class_name,
                        class_students: Number(row.class_students || 0),
                    });
                }
            });

            if (classMap.size === 0) {
                classInput.innerHTML = '<option value="">No class assigned</option>';
                subjectInput.innerHTML = '<option value="">No subject assigned</option>';
                loadSheetBtn.disabled = true;
                saveMarksBtn.disabled = true;
                return;
            }

            const sortedClasses = Array.from(classMap.entries())
                .sort((a, b) => {
                    const aCount = Number(a[1].class_students || 0);
                    const bCount = Number(b[1].class_students || 0);
                    if (aCount !== bCount) {
                        return bCount - aCount;
                    }

                    return String(a[1].name).localeCompare(String(b[1].name));
                });

            classInput.innerHTML = sortedClasses
                .map(([classId, classInfo]) => `<option value="${classId}">${window.NSMS.escapeHtml(classInfo.name)} (${classInfo.class_students} students)</option>`)
                .join('');

            buildSubjectOptions();
        }

        function buildSubjectOptions() {
            const session = sessionInput.value;
            const classId = Number(classInput.value);

            const rows = assignments.filter(item => item.session === session && Number(item.class_id) === classId);
            if (rows.length === 0) {
                subjectInput.innerHTML = '<option value="">No subject assigned</option>';
                loadSheetBtn.disabled = true;
                saveMarksBtn.disabled = true;
                return;
            }

            const subjectMap = new Map();
            rows.forEach(row => {
                if (!subjectMap.has(row.subject_id)) {
                    subjectMap.set(row.subject_id, {
                        name: row.subject_name,
                        subject_students: Number(row.subject_students || 0),
                    });
                }
            });

            const sortedSubjects = Array.from(subjectMap.entries())
                .sort((a, b) => {
                    const aCount = Number(a[1].subject_students || 0);
                    const bCount = Number(b[1].subject_students || 0);
                    if (aCount !== bCount) {
                        return bCount - aCount;
                    }

                    return String(a[1].name).localeCompare(String(b[1].name));
                });

            subjectInput.innerHTML = sortedSubjects
                .map(([subjectId, subjectInfo]) => `<option value="${subjectId}">${window.NSMS.escapeHtml(subjectInfo.name)} (${subjectInfo.subject_students} students)</option>`)
                .join('');
            loadSheetBtn.disabled = false;
        }

        function renderStudents() {
            if (!state.students.length) {
                marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No students found for selected exam setup.</td></tr>';
                paginationInfo.textContent = 'No records';
                prevPageBtn.disabled = true;
                nextPageBtn.disabled = true;
                return;
            }

            const totalMarks = Number(totalMarksInput.value || 0);
            const disabledAttr = state.locked ? 'disabled' : '';
            const rows = pagedStudents();

            marksBody.innerHTML = rows.map(student => `
                <tr>
                    <td class="sticky left-0 z-10 bg-white px-4 py-2 text-sm text-gray-800">
                        <div class="font-medium">${window.NSMS.escapeHtml(student.name)}</div>
                        <div class="text-xs text-gray-500">${window.NSMS.escapeHtml(student.student_id)}</div>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(student.father_name ?? '-')}</td>
                    <td class="px-4 py-2 text-sm">
                        <input
                            type="number"
                            min="0"
                            max="${totalMarks > 0 ? totalMarks : ''}"
                            value="${student.obtained_marks ?? ''}"
                            data-student-id="${student.id}"
                            class="marks-input min-h-11 w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                            ${disabledAttr}
                        />
                    </td>
                </tr>
            `).join('');

            const pages = totalPages();
            paginationInfo.textContent = `Page ${state.page} of ${pages} | Total: ${state.students.length}`;
            prevPageBtn.disabled = state.page <= 1;
            nextPageBtn.disabled = state.page >= pages;
            saveMarksBtn.disabled = state.locked;
        }

        async function parseApiPayload(response) {
            const raw = await response.text();
            if (!raw) {
                return {};
            }

            try {
                return JSON.parse(raw);
            } catch (error) {
                return { raw };
            }
        }

        function isLoginRedirect(response) {
            return Boolean(
                response?.redirected &&
                typeof response.url === 'string' &&
                response.url.includes('/login')
            );
        }

        async function loadSheet() {
            clearMessage();

            if (!assignments.length) {
                showMessage('No subject assignment found for your account. Contact Principal.', 'error');
                return;
            }

            const payload = {
                session: sessionInput.value,
                class_id: Number(classInput.value),
                subject_id: Number(subjectInput.value),
                exam_type: examTypeInput.value
            };

            if (!payload.session || !payload.class_id || !payload.subject_id || !payload.exam_type) {
                showMessage('Session, class, subject and exam type are required.', 'error');
                return;
            }

            loadSheetBtn.disabled = true;
            loadSheetBtn.textContent = 'Loading...';
            marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Loading students...</td></tr>';

            const params = new URLSearchParams(payload);
            try {
                const response = await fetch(`{{ route('teacher.exams.sheet') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (isLoginRedirect(response)) {
                    showMessage('Your session has expired. Please login again.', 'error');
                    window.location.href = response.url;
                    return;
                }

                const result = await parseApiPayload(response);
                if (!response.ok) {
                    if (response.status === 419) {
                        showMessage('Your session has expired. Please refresh and login again.', 'error');
                    } else if (result.errors) {
                        const message = Object.values(result.errors).flat().join(' ');
                        showMessage(message || result.message || 'Failed to load marks sheet.', 'error');
                    } else if (result.message) {
                        showMessage(result.message, 'error');
                    } else {
                        showMessage('Failed to load marks sheet.', 'error');
                    }

                    marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
                    return;
                }

                if (!Array.isArray(result.students)) {
                    showMessage('Invalid response while loading marks sheet. Please refresh page.', 'error');
                    marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
                    return;
                }

                state.students = result.students || [];
                state.locked = Boolean(result.exam?.locked);
                state.page = 1;

                if (result.exam?.total_marks) {
                    totalMarksInput.value = result.exam.total_marks;
                    totalMarksInput.readOnly = true;
                    totalMarksInput.classList.add('bg-gray-100');
                } else {
                    totalMarksInput.readOnly = false;
                    totalMarksInput.classList.remove('bg-gray-100');
                }

                renderStudents();

                if (state.locked && result.exam?.locked_message) {
                    showMessage(result.exam.locked_message, 'error');
                }
            } catch (error) {
                showMessage('Unexpected error while loading marks sheet.', 'error');
                marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
            } finally {
                loadSheetBtn.disabled = false;
                loadSheetBtn.textContent = 'Load Students';
            }
        }

        async function saveMarks() {
            clearMessage();

            if (state.locked) {
                showMessage('Exam is locked after 7 days. You cannot edit marks.', 'error');
                return;
            }

            const totalMarks = Number(totalMarksInput.value || 0);
            if (!totalMarks || totalMarks <= 0) {
                showMessage('Total marks are required and must be greater than 0.', 'error');
                return;
            }

            const records = state.students.map(student => ({
                student_id: student.id,
                obtained_marks: student.obtained_marks === '' || student.obtained_marks === null || student.obtained_marks === undefined
                    ? null
                    : Number(student.obtained_marks)
            }));

            if (records.some(row => row.obtained_marks !== null && (Number.isNaN(row.obtained_marks) || row.obtained_marks < 0 || row.obtained_marks > totalMarks))) {
                showMessage('Each obtained mark must be between 0 and total marks.', 'error');
                return;
            }

            const payload = {
                session: sessionInput.value,
                class_id: Number(classInput.value),
                subject_id: Number(subjectInput.value),
                exam_type: examTypeInput.value,
                total_marks: totalMarks,
                records
            };

            saveMarksBtn.disabled = true;
            saveMarksBtn.textContent = 'Saving...';

            try {
                const response = await fetch(`{{ route('teacher.exams.save') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                if (isLoginRedirect(response)) {
                    showMessage('Your session has expired. Please login again.', 'error');
                    window.location.href = response.url;
                    return;
                }

                const result = await parseApiPayload(response);
                if (!response.ok) {
                    if (response.status === 419) {
                        showMessage('Your session has expired. Please refresh and login again.', 'error');
                    } else if (result.errors) {
                        const message = Object.values(result.errors).flat().join(' ');
                        showMessage(message || 'Failed to save marks.', 'error');
                    } else if (result.message) {
                        showMessage(result.message, 'error');
                    } else {
                        showMessage('Failed to save marks.', 'error');
                    }
                    return;
                }

                showMessage(result.message || 'Marks saved successfully.');
                await loadSheet();
            } catch (error) {
                showMessage('Unexpected error while saving marks.', 'error');
            } finally {
                saveMarksBtn.disabled = false;
                saveMarksBtn.textContent = 'Save Marks';
            }
        }

        marksBody.addEventListener('input', (event) => {
            const target = event.target;
            if (!target.classList.contains('marks-input')) {
                return;
            }

            const studentId = Number(target.dataset.studentId);
            const student = state.students.find(row => Number(row.id) === studentId);
            if (!student) {
                return;
            }

            student.obtained_marks = target.value === '' ? null : Number(target.value);
        });

        sessionInput.addEventListener('change', () => {
            buildClassOptions();
            state.students = [];
            state.page = 1;
            renderStudents();
        });

        classInput.addEventListener('change', () => {
            buildSubjectOptions();
            state.students = [];
            state.page = 1;
            renderStudents();
        });

        perPageInput.addEventListener('change', () => {
            state.per_page = Number(perPageInput.value || 10);
            state.page = 1;
            renderStudents();
        });

        prevPageBtn.addEventListener('click', () => {
            if (state.page > 1) {
                state.page -= 1;
                renderStudents();
            }
        });

        nextPageBtn.addEventListener('click', () => {
            const pages = totalPages();
            if (state.page < pages) {
                state.page += 1;
                renderStudents();
            }
        });

        loadSheetBtn.addEventListener('click', loadSheet);
        saveMarksBtn.addEventListener('click', saveMarks);

        buildClassOptions();
        renderStudents();

        if (!assignments.length) {
            showMessage('No subject assignment found for your account. Contact Principal.', 'error');
        }
    </script>
</x-app-layout>
