<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Examination Assessment Entry
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Exam Setup</h3>
                    <p class="mt-1 text-sm text-gray-600">Select class, subject, exam type, and session to load the assessment sheet. For Early Years, the Principal can configure each assessment to use grades or numeric marks.</p>
                    <p class="mt-1 text-sm text-indigo-700">Only students enrolled in the selected subject are shown.</p>
                    @if (! $hasAssignments)
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            No subject assignment found for your account. Ask Principal to assign your class and subject for the current session.
                        </div>
                    @endif

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-6">
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

                        <div id="totalMarksWrapper">
                            <x-input-label for="total_marks" value="Total Marks" />
                            <x-text-input id="total_marks" type="number" min="1" class="mt-1 block min-h-11 w-full" placeholder="100" />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <x-input-label for="exam_id" value="Existing Exam" />
                            <select id="exam_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Create New / Load by Topic-Number</option>
                            </select>
                        </div>

                        <div id="classTestTopicWrapper" class="hidden">
                            <x-input-label for="class_test_topic" value="Class Test Topic" />
                            <x-text-input
                                id="class_test_topic"
                                type="text"
                                class="mt-1 block min-h-11 w-full"
                                placeholder="e.g. Fractions, Chapter 2, Grammar Test"
                            />
                        </div>

                        <div id="bimonthlySequenceWrapper" class="hidden">
                            <x-input-label for="sequence_number" value="Bimonthly Number" />
                            <select id="sequence_number" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select bimonthly</option>
                                <option value="1">1st Bimonthly</option>
                                <option value="2">2nd Bimonthly</option>
                                <option value="3">3rd Bimonthly</option>
                                <option value="4">4th Bimonthly</option>
                            </select>
                        </div>
                    </div>

                    <div id="assessmentModeBadge" class="mt-4 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        Numeric marks mode
                    </div>

                    <div id="gradeHelpBox" class="mt-3 hidden rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-800">
                        This assessment is in grade mode. Choose one of: A*, A, B, C, D, E, F, G, U.
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button id="loadSheetBtn" type="button" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Load Students
                        </button>
                        <button id="saveMarksBtn" type="button" class="inline-flex min-h-11 items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Save Entries
                        </button>
                    </div>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>
                    <div id="lockBanner" class="mt-4 hidden rounded-md border px-4 py-3 text-sm"></div>
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
                                    <th id="assessmentColumnHeader" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Obtained Marks</th>
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
        const examIdInput = document.getElementById('exam_id');
        const classTestTopicWrapper = document.getElementById('classTestTopicWrapper');
        const classTestTopicInput = document.getElementById('class_test_topic');
        const bimonthlySequenceWrapper = document.getElementById('bimonthlySequenceWrapper');
        const sequenceNumberInput = document.getElementById('sequence_number');
        const totalMarksInput = document.getElementById('total_marks');
        const totalMarksWrapper = document.getElementById('totalMarksWrapper');
        const assessmentModeBadge = document.getElementById('assessmentModeBadge');
        const gradeHelpBox = document.getElementById('gradeHelpBox');
        const assessmentColumnHeader = document.getElementById('assessmentColumnHeader');
        const loadSheetBtn = document.getElementById('loadSheetBtn');
        const saveMarksBtn = document.getElementById('saveMarksBtn');
        const marksBody = document.getElementById('marksBody');
        const messageBox = document.getElementById('messageBox');
        const perPageInput = document.getElementById('perPage');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const lockBanner = document.getElementById('lockBanner');
        const examContextOptionsUrl = @json(route('teacher.exams.bimonthly-options'));

        let state = {
            students: [],
            locked: false,
            lock_type: null,
            page: 1,
            per_page: 10,
            emptyMessage: 'No students found for selected exam setup.',
            markingMode: 'numeric',
            usesGradeSystem: false,
            supportsGradeMode: false,
            gradeOptions: [],
            contextExams: [],
            bimonthlyOptions: [],
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

        function updateLockBanner(message = '', lockType = null) {
            if (!message) {
                lockBanner.classList.add('hidden');
                lockBanner.textContent = '';
                lockBanner.className = 'mt-4 hidden rounded-md border px-4 py-3 text-sm';
                return;
            }

            lockBanner.classList.remove('hidden');
            lockBanner.textContent = message;
            lockBanner.className = lockType === 'final'
                ? 'mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700'
                : 'mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800';
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

        function currentAssignment() {
            const session = sessionInput.value;
            const classId = Number(classInput.value);
            const subjectId = Number(subjectInput.value);

            return assignments.find(item =>
                item.session === session &&
                Number(item.class_id) === classId &&
                Number(item.subject_id) === subjectId
            ) || null;
        }

        function selectedExamType() {
            return String(examTypeInput.value || '');
        }

        function isClassTestType() {
            return selectedExamType() === 'class_test';
        }

        function isBimonthlyType() {
            return selectedExamType() === 'bimonthly_test';
        }

        function selectedExamContext() {
            return state.contextExams.find((exam) => String(exam.id) === String(examIdInput.value || '')) || null;
        }

        function updateExamTypeScopedFields() {
            classTestTopicWrapper.classList.toggle('hidden', !isClassTestType());
            bimonthlySequenceWrapper.classList.toggle('hidden', !isBimonthlyType());

            if (!isClassTestType()) {
                classTestTopicInput.value = '';
            }

            if (!isBimonthlyType()) {
                sequenceNumberInput.value = '';
            }
        }

        function setExamSelectOptions(exams) {
            const options = ['<option value="">Create New / Load by Topic-Number</option>'];

            (exams || []).forEach((exam) => {
                options.push(
                    `<option value="${exam.id}">${window.NSMS.escapeHtml(exam.display_name || 'Exam')}</option>`
                );
            });

            examIdInput.innerHTML = options.join('');
        }

        function setBimonthlyOptions(options) {
            const currentValue = String(sequenceNumberInput.value || '');
            const rows = Array.isArray(options) ? options : [];
            const nextOptions = ['<option value="">Select bimonthly</option>'];

            rows.forEach((row) => {
                const value = Number(row.value || 0);
                if (!value) {
                    return;
                }

                const isAvailable = Boolean(row.available);
                const isSelected = currentValue !== '' && Number(currentValue) === value;
                const shouldDisable = !isAvailable && !isSelected;
                const label = `${row.label}${isAvailable ? '' : ' (already created)'}`;

                nextOptions.push(
                    `<option value="${value}" ${shouldDisable ? 'disabled' : ''}>${window.NSMS.escapeHtml(label)}</option>`
                );
            });

            sequenceNumberInput.innerHTML = nextOptions.join('');
            if (currentValue !== '' && sequenceNumberInput.querySelector(`option[value="${currentValue}"]`)) {
                sequenceNumberInput.value = currentValue;
            }
        }

        function syncVariantFieldsFromSelectedExam() {
            const selectedExam = selectedExamContext();
            if (!selectedExam) {
                return;
            }

            if (isClassTestType()) {
                classTestTopicInput.value = selectedExam.topic || '';
            }

            if (isBimonthlyType()) {
                sequenceNumberInput.value = selectedExam.sequence_number ? String(selectedExam.sequence_number) : '';
            }
        }

        function updateAssessmentModeUi() {
            const isGradeMode = state.markingMode === 'grade';
            state.usesGradeSystem = isGradeMode;

            assessmentColumnHeader.textContent = isGradeMode ? 'Grade' : 'Obtained Marks';
            totalMarksWrapper.classList.toggle('hidden', isGradeMode);
            gradeHelpBox.classList.toggle('hidden', !isGradeMode);
            assessmentModeBadge.textContent = isGradeMode
                ? 'Grade mode selected for this assessment'
                : (state.supportsGradeMode ? 'Numeric marks mode selected for this assessment' : 'Numeric marks mode');
            assessmentModeBadge.className = isGradeMode
                ? 'mt-4 inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700'
                : 'mt-4 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700';

            if (isGradeMode) {
                totalMarksInput.value = '';
                totalMarksInput.readOnly = true;
                totalMarksInput.classList.add('bg-gray-100');
            }
        }

        function applyAssessmentModeFromAssignment() {
            const assignment = currentAssignment();
            state.supportsGradeMode = Boolean(assignment?.supports_grade_mode);
            state.markingMode = 'numeric';
            state.usesGradeSystem = false;
            if (!state.gradeOptions.length) {
                state.gradeOptions = [
                    { code: 'A*', label: 'Excellent' },
                    { code: 'A', label: 'Very Good' },
                    { code: 'B', label: 'Good' },
                    { code: 'C', label: 'Satisfactory' },
                    { code: 'D', label: 'Basic' },
                    { code: 'E', label: 'Needs Improvement' },
                    { code: 'F', label: 'Weak' },
                    { code: 'G', label: 'Very Weak' },
                    { code: 'U', label: 'Ungraded / Not Assessed' },
                ];
            }

            totalMarksInput.readOnly = false;
            totalMarksInput.classList.remove('bg-gray-100');

            updateAssessmentModeUi();
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
                setExamSelectOptions([]);
                setBimonthlyOptions([]);
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
                setExamSelectOptions([]);
                setBimonthlyOptions([]);
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
            saveMarksBtn.disabled = false;
            applyAssessmentModeFromAssignment();
            loadExamContextOptions();
        }

        async function loadExamContextOptions() {
            state.contextExams = [];
            state.bimonthlyOptions = [];
            setExamSelectOptions([]);
            setBimonthlyOptions([]);

            const payload = {
                session: sessionInput.value,
                class_id: Number(classInput.value),
                subject_id: Number(subjectInput.value),
                exam_type: selectedExamType(),
            };

            if (!payload.session || !payload.class_id || !payload.subject_id || !payload.exam_type) {
                return;
            }

            try {
                const params = new URLSearchParams(payload);
                const response = await fetch(`${examContextOptionsUrl}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const result = await response.json();
                state.contextExams = Array.isArray(result.exams) ? result.exams : [];
                state.bimonthlyOptions = Array.isArray(result.available_bimonthly_options)
                    ? result.available_bimonthly_options
                    : [];

                setExamSelectOptions(state.contextExams);
                if (isBimonthlyType()) {
                    setBimonthlyOptions(state.bimonthlyOptions);
                }
            } catch (error) {
                // Best-effort helper endpoint; ignore network errors and continue.
            }

            syncVariantFieldsFromSelectedExam();
        }

        function gradeOptionsHtml(selectedGrade) {
            return [
                '<option value="">Select Grade</option>',
                ...state.gradeOptions.map(option => {
                    const selected = option.code === selectedGrade ? 'selected' : '';
                    return `<option value="${window.NSMS.escapeHtml(option.code)}" ${selected}>${window.NSMS.escapeHtml(option.code)} - ${window.NSMS.escapeHtml(option.label)}</option>`;
                })
            ].join('');
        }

        function renderStudents() {
            if (!state.students.length) {
                marksBody.innerHTML = `<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">${window.NSMS.escapeHtml(state.emptyMessage)}</td></tr>`;
                paginationInfo.textContent = 'No records';
                prevPageBtn.disabled = true;
                nextPageBtn.disabled = true;
                return;
            }

            const totalMarks = Number(totalMarksInput.value || 0);
            const disabledAttr = state.locked ? 'disabled' : '';
            const rows = pagedStudents();

            marksBody.innerHTML = rows.map(student => {
                const assessmentInput = state.usesGradeSystem
                    ? `
                        <select
                            data-student-id="${student.id}"
                            class="grade-input min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                            ${disabledAttr}
                        >
                            ${gradeOptionsHtml(student.grade ?? '')}
                        </select>
                    `
                    : `
                        <input
                            type="number"
                            min="0"
                            max="${totalMarks > 0 ? totalMarks : ''}"
                            value="${student.obtained_marks ?? ''}"
                            data-student-id="${student.id}"
                            class="marks-input min-h-11 w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                            ${disabledAttr}
                        />
                    `;

                return `
                    <tr>
                        <td class="sticky left-0 z-10 bg-white px-4 py-2 text-sm text-gray-800">
                            <div class="font-medium">${window.NSMS.escapeHtml(student.name)}</div>
                            <div class="text-xs text-gray-500">${window.NSMS.escapeHtml(student.student_id)}</div>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-800">${window.NSMS.escapeHtml(student.father_name ?? '-')}</td>
                        <td class="px-4 py-2 text-sm">${assessmentInput}</td>
                    </tr>
                `;
            }).join('');

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
                exam_type: examTypeInput.value,
                exam_id: examIdInput.value ? Number(examIdInput.value) : null,
                topic: classTestTopicInput.value.trim(),
                sequence_number: sequenceNumberInput.value ? Number(sequenceNumberInput.value) : null,
            };

            if (!payload.session || !payload.class_id || !payload.subject_id || !payload.exam_type) {
                showMessage('Session, class, subject and exam type are required.', 'error');
                return;
            }

            if (isClassTestType() && !payload.exam_id && !payload.topic) {
                showMessage('Class Test Topic is required to load class test sheet.', 'error');
                return;
            }

            if (isBimonthlyType() && !payload.exam_id && !payload.sequence_number) {
                showMessage('Select bimonthly number to load sheet.', 'error');
                return;
            }

            loadSheetBtn.disabled = true;
            loadSheetBtn.textContent = 'Loading...';
            marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Loading students...</td></tr>';

            const params = new URLSearchParams({
                session: String(payload.session),
                class_id: String(payload.class_id),
                subject_id: String(payload.subject_id),
                exam_type: String(payload.exam_type),
            });
            if (payload.exam_id) {
                params.set('exam_id', String(payload.exam_id));
            }
            if (payload.topic) {
                params.set('topic', String(payload.topic));
            }
            if (payload.sequence_number) {
                params.set('sequence_number', String(payload.sequence_number));
            }
            try {
                const response = await fetch(`{{ route('teacher.exams.sheet') }}?${params.toString()}`, {
                    headers: { Accept: 'application/json' }
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
                        showMessage(message || result.message || 'Failed to load assessment sheet.', 'error');
                    } else if (result.message) {
                        showMessage(result.message, 'error');
                    } else {
                        showMessage('Failed to load assessment sheet.', 'error');
                    }

                    marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
                    return;
                }

                if (!Array.isArray(result.students)) {
                    showMessage('Invalid response while loading assessment sheet. Please refresh page.', 'error');
                    marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
                    return;
                }

                state.students = result.students || [];
                state.emptyMessage = (result.message && typeof result.message === 'string')
                    ? result.message
                    : 'No students found for selected exam setup.';
                state.locked = Boolean(result.exam?.locked);
                state.lock_type = result.exam?.lock_type || null;
                state.page = 1;
                state.markingMode = result.marking_mode === 'grade' ? 'grade' : 'numeric';
                state.usesGradeSystem = state.markingMode === 'grade';
                state.supportsGradeMode = Boolean(result.supports_grade_mode);
                state.gradeOptions = Array.isArray(result.grade_options) && result.grade_options.length
                    ? result.grade_options
                    : state.gradeOptions;

                if (result.exam?.id) {
                    examIdInput.value = String(result.exam.id);
                }
                if (isClassTestType()) {
                    classTestTopicInput.value = result.exam?.topic || classTestTopicInput.value;
                }
                if (isBimonthlyType()) {
                    sequenceNumberInput.value = result.exam?.sequence_number ? String(result.exam.sequence_number) : sequenceNumberInput.value;
                }

                if (!state.usesGradeSystem && result.exam?.total_marks) {
                    totalMarksInput.value = result.exam.total_marks;
                    totalMarksInput.readOnly = true;
                    totalMarksInput.classList.add('bg-gray-100');
                } else if (!state.usesGradeSystem) {
                    totalMarksInput.readOnly = false;
                    totalMarksInput.classList.remove('bg-gray-100');
                } else {
                    totalMarksInput.value = '';
                    totalMarksInput.readOnly = true;
                    totalMarksInput.classList.add('bg-gray-100');
                }

                updateAssessmentModeUi();
                renderStudents();

                if (!state.students.length && result.message) {
                    showMessage(result.message, 'success');
                }

                if (state.locked && result.exam?.locked_message) {
                    updateLockBanner(result.exam.locked_message, state.lock_type);
                    showMessage(result.exam.locked_message, 'error');
                } else {
                    updateLockBanner();
                }
            } catch (error) {
                showMessage('Unexpected error while loading assessment sheet.', 'error');
                marksBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-red-600">Failed to load students.</td></tr>';
                updateLockBanner();
            } finally {
                loadSheetBtn.disabled = false;
                loadSheetBtn.textContent = 'Load Students';
            }
        }

        async function saveMarks() {
            clearMessage();

            if (state.locked) {
                showMessage('Results are locked and cannot be modified.', 'error');
                return;
            }

            let records;
            let payload;

            if (state.usesGradeSystem) {
                records = state.students.map(student => ({
                    student_id: student.id,
                    grade: student.grade ? String(student.grade).trim().toUpperCase() : null,
                    obtained_marks: null,
                }));

                payload = {
                    session: sessionInput.value,
                    class_id: Number(classInput.value),
                    subject_id: Number(subjectInput.value),
                    exam_type: examTypeInput.value,
                    exam_id: examIdInput.value ? Number(examIdInput.value) : null,
                    topic: classTestTopicInput.value.trim() || null,
                    sequence_number: sequenceNumberInput.value ? Number(sequenceNumberInput.value) : null,
                    total_marks: null,
                    records,
                };
            } else {
                const totalMarks = Number(totalMarksInput.value || 0);
                if (!totalMarks || totalMarks <= 0) {
                    showMessage('Total marks are required and must be greater than 0.', 'error');
                    return;
                }

                records = state.students.map(student => ({
                    student_id: student.id,
                    obtained_marks: student.obtained_marks === '' || student.obtained_marks === null || student.obtained_marks === undefined
                        ? null
                        : Number(student.obtained_marks),
                    grade: null,
                }));

                if (records.some(row => row.obtained_marks !== null && (Number.isNaN(row.obtained_marks) || row.obtained_marks < 0 || row.obtained_marks > totalMarks))) {
                    showMessage('Each obtained mark must be between 0 and total marks.', 'error');
                    return;
                }

                payload = {
                    session: sessionInput.value,
                    class_id: Number(classInput.value),
                    subject_id: Number(subjectInput.value),
                    exam_type: examTypeInput.value,
                    exam_id: examIdInput.value ? Number(examIdInput.value) : null,
                    topic: classTestTopicInput.value.trim() || null,
                    sequence_number: sequenceNumberInput.value ? Number(sequenceNumberInput.value) : null,
                    total_marks: totalMarks,
                    records,
                };
            }

            if (isClassTestType() && !payload.exam_id && !payload.topic) {
                showMessage('Class Test Topic is required before saving.', 'error');
                return;
            }

            if (isBimonthlyType() && !payload.exam_id && !payload.sequence_number) {
                showMessage('Select bimonthly number before saving.', 'error');
                return;
            }

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
                        showMessage(message || 'Failed to save entries.', 'error');
                    } else if (result.message) {
                        showMessage(result.message, 'error');
                    } else {
                        showMessage('Failed to save entries.', 'error');
                    }
                    return;
                }

                showMessage(result.message || 'Marks saved successfully. Teacher CGPA and ACR metrics have been updated.');
                await loadSheet();
            } catch (error) {
                showMessage('Unexpected error while saving assessment entries.', 'error');
            } finally {
                saveMarksBtn.disabled = false;
                saveMarksBtn.textContent = 'Save Entries';
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

        marksBody.addEventListener('change', (event) => {
            const target = event.target;
            if (!target.classList.contains('grade-input')) {
                return;
            }

            const studentId = Number(target.dataset.studentId);
            const student = state.students.find(row => Number(row.id) === studentId);
            if (!student) {
                return;
            }

            student.grade = target.value === '' ? null : target.value;
        });

        function resetSheet() {
            state.students = [];
            state.emptyMessage = 'No students found for selected exam setup.';
            state.page = 1;
            state.locked = false;
            state.lock_type = null;
            updateLockBanner();
            renderStudents();
        }

        sessionInput.addEventListener('change', () => {
            buildClassOptions();
            resetSheet();
        });

        classInput.addEventListener('change', () => {
            buildSubjectOptions();
            resetSheet();
        });

        subjectInput.addEventListener('change', () => {
            applyAssessmentModeFromAssignment();
            loadExamContextOptions();
            resetSheet();
        });

        examTypeInput.addEventListener('change', () => {
            updateExamTypeScopedFields();
            applyAssessmentModeFromAssignment();
            loadExamContextOptions();
            resetSheet();
        });

        examIdInput.addEventListener('change', () => {
            syncVariantFieldsFromSelectedExam();
            resetSheet();
        });

        classTestTopicInput.addEventListener('input', () => {
            if (examIdInput.value) {
                examIdInput.value = '';
            }
        });

        sequenceNumberInput.addEventListener('change', () => {
            if (examIdInput.value) {
                examIdInput.value = '';
            }
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
        updateExamTypeScopedFields();
        applyAssessmentModeFromAssignment();
        loadExamContextOptions();
        renderStudents();
    </script>
</x-app-layout>
