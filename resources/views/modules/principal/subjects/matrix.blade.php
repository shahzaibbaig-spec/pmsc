<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Subject Assignment Matrix
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Filter Matrix</h3>
                    <p class="text-sm text-gray-600 mt-1">Select session and class to load students and subjects.</p>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((int) ($defaultClassId ?? 0) === (int) $classRoom->id)>
                                        {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }} ({{ (int) ($classRoom->students_count ?? 0) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2 flex items-end gap-2">
                            <button id="loadMatrix" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Load Matrix
                            </button>
                            <button id="saveMatrixChanges" type="button" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" disabled>
                                Save Changes
                            </button>
                            <span id="pendingChangesMeta" class="text-xs text-gray-600">0 pending</span>
                        </div>
                    </div>

                    <div id="statusMessage" class="mt-4 hidden rounded-md p-3 text-sm"></div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Matrix</h3>
                        <p id="matrixMeta" class="text-sm text-gray-600">-</p>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead id="matrixHead" class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                </tr>
                            </thead>
                            <tbody id="matrixBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td class="px-4 py-8 text-center text-sm text-gray-500">Select filters and load the matrix.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sessionInput = document.getElementById('session');
        const classInput = document.getElementById('class_id');
        const loadButton = document.getElementById('loadMatrix');
        const saveChangesButton = document.getElementById('saveMatrixChanges');
        const pendingChangesMeta = document.getElementById('pendingChangesMeta');
        const matrixHead = document.getElementById('matrixHead');
        const matrixBody = document.getElementById('matrixBody');
        const matrixMeta = document.getElementById('matrixMeta');
        const statusMessage = document.getElementById('statusMessage');

        let matrixState = {
            class: null,
            subjects: [],
            students: [],
            session: null,
            initialAssigned: new Set()
        };
        const pendingChanges = new Map();

        function cellKey(studentId, subjectId) {
            return `${studentId}:${subjectId}`;
        }

        function updatePendingMeta() {
            const count = pendingChanges.size;
            pendingChangesMeta.textContent = `${count} pending`;
            saveChangesButton.disabled = count === 0;
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showStatus(message, type = 'success') {
            statusMessage.classList.remove('hidden');
            statusMessage.classList.remove('bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            statusMessage.textContent = message;

            if (type === 'error') {
                statusMessage.classList.add('bg-red-50', 'text-red-700');
            } else {
                statusMessage.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function clearStatus() {
            statusMessage.classList.add('hidden');
            statusMessage.textContent = '';
        }

        function renderMatrix() {
            const subjects = matrixState.subjects;
            const students = matrixState.students;

            if (!subjects.length) {
                matrixHead.innerHTML = `
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                    </tr>
                `;

                matrixBody.innerHTML = `
                    <tr>
                        <td class="px-4 py-8 text-center text-sm text-gray-500">No subjects available. Add subjects or assign subjects to class first.</td>
                    </tr>
                `;

                matrixMeta.textContent = `${students.length} students | 0 subjects`;
                return;
            }

            matrixHead.innerHTML = `
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600 min-w-56">Student</th>
                    ${subjects.map(subject => `
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-600 min-w-40">
                            <div>${escapeHtml(subject.name)}</div>
                            <div class="mt-1 flex items-center justify-center gap-1">
                                <button type="button" class="bulk-btn rounded bg-blue-600 px-2 py-1 text-[10px] text-white hover:bg-blue-700" data-subject-id="${subject.id}" data-assigned="1">All</button>
                                <button type="button" class="bulk-btn rounded bg-gray-500 px-2 py-1 text-[10px] text-white hover:bg-gray-600" data-subject-id="${subject.id}" data-assigned="0">None</button>
                            </div>
                        </th>
                    `).join('')}
                </tr>
            `;

            if (!students.length) {
                matrixBody.innerHTML = `
                    <tr>
                        <td colspan="${subjects.length + 1}" class="px-4 py-8 text-center text-sm text-gray-500">No students found in this class.</td>
                    </tr>
                `;
                matrixMeta.textContent = `0 students | ${subjects.length} subjects`;
                return;
            }

            matrixBody.innerHTML = students.map(student => {
                const assignedSet = new Set((student.assigned_subject_ids || []).map(Number));

                return `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">
                            <div class="font-medium">${escapeHtml(student.name)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(student.student_id)}</div>
                        </td>
                        ${subjects.map(subject => `
                            <td class="px-3 py-2 text-center">
                                <input
                                    type="checkbox"
                                    class="matrix-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    data-student-id="${student.id}"
                                    data-subject-id="${subject.id}"
                                    ${assignedSet.has(Number(subject.id)) ? 'checked' : ''}
                                >
                            </td>
                        `).join('')}
                    </tr>
                `;
            }).join('');

            matrixMeta.textContent = `${students.length} students | ${subjects.length} subjects`;
        }

        async function loadMatrix() {
            clearStatus();

            const classId = Number(classInput.value);
            const session = sessionInput.value;
            if (!classId || !session) {
                showStatus('Session and class are required.', 'error');
                return;
            }

            loadButton.disabled = true;
            loadButton.textContent = 'Loading...';
            matrixBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-gray-500">Loading matrix...</td></tr>';

            const params = new URLSearchParams({
                class_id: classId,
                session: session
            });

            try {
                const response = await fetch(`{{ route('principal.subjects.matrix.data') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();
                if (!response.ok) {
                    showStatus(result.message || 'Failed to load subject matrix.', 'error');
                    matrixBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-red-600">Unable to load matrix.</td></tr>';
                    return;
                }

                matrixState = {
                    class: result.class,
                    subjects: result.subjects || [],
                    students: result.students || [],
                    session: result.session,
                    initialAssigned: new Set()
                };

                matrixState.students.forEach((student) => {
                    (student.assigned_subject_ids || []).forEach((subjectId) => {
                        matrixState.initialAssigned.add(cellKey(Number(student.id), Number(subjectId)));
                    });
                });

                pendingChanges.clear();
                updatePendingMeta();

                renderMatrix();
            } catch (error) {
                showStatus('Unexpected error while loading matrix.', 'error');
                matrixBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-sm text-red-600">Unable to load matrix.</td></tr>';
            } finally {
                loadButton.disabled = false;
                loadButton.textContent = 'Load Matrix';
            }
        }

        function stageToggle(input) {
            const studentId = Number(input.dataset.studentId);
            const subjectId = Number(input.dataset.subjectId);
            const assigned = input.checked;
            const key = cellKey(studentId, subjectId);
            const initialAssigned = matrixState.initialAssigned.has(key);

            if (assigned === initialAssigned) {
                pendingChanges.delete(key);
            } else {
                pendingChanges.set(key, {
                    student_id: studentId,
                    subject_id: subjectId,
                    assigned: assigned
                });
            }

            updatePendingMeta();
            clearStatus();
        }

        async function applyBulk(subjectId, assigned) {
            const payload = {
                session: sessionInput.value,
                class_id: Number(classInput.value),
                subject_id: Number(subjectId),
                assigned: Boolean(assigned)
            };

            try {
                const response = await fetch(`{{ route('principal.subjects.matrix.bulk-assign') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!response.ok) {
                    showStatus(result.message || 'Bulk assignment failed.', 'error');
                    return;
                }

                showStatus(`Bulk assignment updated (${result.affected} records).`);
                await loadMatrix();
            } catch (error) {
                showStatus('Unexpected error while applying bulk assignment.', 'error');
            }
        }

        async function savePendingChanges() {
            if (pendingChanges.size === 0) {
                showStatus('No pending changes to save.');
                return;
            }

            const changes = Array.from(pendingChanges.values());
            let successCount = 0;
            const failed = [];

            saveChangesButton.disabled = true;
            loadButton.disabled = true;

            for (const change of changes) {
                try {
                    const response = await fetch(`{{ route('principal.subjects.matrix.toggle') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            session: sessionInput.value,
                            class_id: Number(classInput.value),
                            student_id: change.student_id,
                            subject_id: change.subject_id,
                            assigned: change.assigned
                        })
                    });

                    if (!response.ok) {
                        const result = await response.json().catch(() => ({}));
                        failed.push(result.message || `Failed at student ${change.student_id}, subject ${change.subject_id}.`);
                        continue;
                    }

                    successCount++;
                } catch (error) {
                    failed.push(`Network error at student ${change.student_id}, subject ${change.subject_id}.`);
                }
            }

            await loadMatrix();

            if (failed.length === 0) {
                showStatus(`Saved ${successCount} changes successfully.`);
                return;
            }

            showStatus(`Saved ${successCount} of ${changes.length}. ${failed[0]}`, 'error');
        }

        loadButton.addEventListener('click', loadMatrix);
        saveChangesButton.addEventListener('click', savePendingChanges);

        matrixBody.addEventListener('change', (event) => {
            const target = event.target;
            if (!target.classList.contains('matrix-checkbox')) {
                return;
            }

            stageToggle(target);
        });

        matrixHead.addEventListener('click', async (event) => {
            const target = event.target;
            if (!target.classList.contains('bulk-btn')) {
                return;
            }

            const subjectId = Number(target.dataset.subjectId);
            const assigned = target.dataset.assigned === '1';
            await applyBulk(subjectId, assigned);
        });

        if (classInput.value && sessionInput.value) {
            loadMatrix();
        }
    </script>
</x-app-layout>
