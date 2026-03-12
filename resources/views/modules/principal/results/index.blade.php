<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Result Generation
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Generate Student Result</h3>
                    <p class="text-sm text-gray-600 mt-1">Select session, class, student, and exam type to generate an automatic result card.</p>
                    @if (! $hasMarks)
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            No marks have been entered yet. Ask teachers to submit marks from the Marks Entry module first, then generate results here.
                        </div>
                    @endif

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
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
                                    <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="student_id" value="Student" />
                            <select id="student_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                        </div>
                        <div>
                            <x-input-label for="exam_type" value="Exam Type" />
                            <select id="exam_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($examTypes as $examType)
                                    <option value="{{ $examType['value'] }}">{{ $examType['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-1">
                            <button id="loadResultBtn" type="button" class="inline-flex min-h-10 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Load Result
                            </button>
                            <button id="publishResultsBtn" type="button" class="inline-flex min-h-10 items-center rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                                Publish Results
                            </button>
                            <a id="cardLink" href="#" target="_blank" class="inline-flex min-h-10 items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 opacity-50 pointer-events-none">
                                Result Card
                            </a>
                        </div>
                    </div>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">School</p>
                            <p id="schoolName" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Student</p>
                            <p id="studentName" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Class</p>
                            <p id="className" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Exam</p>
                            <p id="examTypeLabel" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-[720px] w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Subject</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Total Marks</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Obtained</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Percentage</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Grade</th>
                                </tr>
                            </thead>
                            <tbody id="resultBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Load result to view subject grades.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Total Marks</p>
                            <p id="summaryTotal" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Obtained Marks</p>
                            <p id="summaryObtained" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Overall %</p>
                            <p id="summaryPercentage" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Overall Grade</p>
                            <p id="summaryGrade" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const classInput = document.getElementById('class_id');
        const studentInput = document.getElementById('student_id');
        const sessionInput = document.getElementById('session');
        const examTypeInput = document.getElementById('exam_type');
        const loadResultBtn = document.getElementById('loadResultBtn');
        const publishResultsBtn = document.getElementById('publishResultsBtn');
        const cardLink = document.getElementById('cardLink');
        const messageBox = document.getElementById('messageBox');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const schoolName = document.getElementById('schoolName');
        const studentName = document.getElementById('studentName');
        const className = document.getElementById('className');
        const examTypeLabel = document.getElementById('examTypeLabel');
        const resultBody = document.getElementById('resultBody');
        const summaryTotal = document.getElementById('summaryTotal');
        const summaryObtained = document.getElementById('summaryObtained');
        const summaryPercentage = document.getElementById('summaryPercentage');
        const summaryGrade = document.getElementById('summaryGrade');

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

        function showMessage(message, type = 'success') {
            messageBox.classList.remove('hidden');
            messageBox.classList.remove('bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
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

        function resetPreview() {
            schoolName.textContent = '-';
            studentName.textContent = '-';
            className.textContent = '-';
            examTypeLabel.textContent = '-';
            summaryTotal.textContent = '-';
            summaryObtained.textContent = '-';
            summaryPercentage.textContent = '-';
            summaryGrade.textContent = '-';
            resultBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Load result to view subject grades.</td></tr>';
            cardLink.classList.add('opacity-50', 'pointer-events-none');
            cardLink.setAttribute('href', '#');
        }

        async function loadStudents() {
            const classId = Number(classInput.value);
            if (!classId) {
                studentInput.innerHTML = '<option value="">No students</option>';
                return;
            }

            const params = new URLSearchParams({ class_id: classId });
            const response = await fetch(`{{ route('principal.results.students') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                studentInput.innerHTML = '<option value="">No students</option>';
                return;
            }

            const result = await response.json();
            const students = result.students || [];

            if (students.length === 0) {
                studentInput.innerHTML = '<option value="">No students</option>';
                return;
            }

            studentInput.innerHTML = students
                .map(student => `<option value="${student.id}">${escapeHtml(student.name)} (${escapeHtml(student.student_id)})</option>`)
                .join('');
        }

        async function loadResult() {
            clearMessage();

            const payload = {
                student_id: Number(studentInput.value),
                session: sessionInput.value,
                exam_type: examTypeInput.value
            };

            if (!payload.student_id || !payload.session || !payload.exam_type) {
                showMessage('Student, session and exam type are required.', 'error');
                return;
            }

            loadResultBtn.disabled = true;
            loadResultBtn.textContent = 'Loading...';

            const params = new URLSearchParams(payload);
            try {
                const response = await fetch(`{{ route('principal.results.preview') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();
                if (!response.ok) {
                    showMessage(result.message || 'Failed to generate result.', 'error');
                    resetPreview();
                    return;
                }

                schoolName.textContent = result.school?.name ?? '-';
                studentName.textContent = `${result.student?.name ?? '-'} (Age: ${result.student?.age ?? '-'})`;
                className.textContent = result.student?.class ?? '-';
                examTypeLabel.textContent = `${result.exam?.exam_type_label ?? '-'} | ${result.exam?.session ?? '-'}`;

                const subjects = result.subjects || [];
                if (!subjects.length) {
                    resultBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No subject marks found.</td></tr>';
                } else {
                    resultBody.innerHTML = subjects.map(row => `
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.subject)}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${row.total_marks}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${row.obtained_marks}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">${row.percentage}%</td>
                            <td class="px-4 py-2 text-sm font-semibold text-gray-800">${escapeHtml(row.grade)}</td>
                        </tr>
                    `).join('');
                }

                summaryTotal.textContent = result.summary?.total_marks ?? '-';
                summaryObtained.textContent = result.summary?.obtained_marks ?? '-';
                summaryPercentage.textContent = result.summary ? `${result.summary.percentage}%` : '-';
                summaryGrade.textContent = result.summary?.grade ?? '-';

                const cardParams = new URLSearchParams(payload);
                cardLink.setAttribute('href', `{{ route('reports.pdf.student-result-card') }}?${cardParams.toString()}`);
                cardLink.classList.remove('opacity-50', 'pointer-events-none');
            } catch (error) {
                showMessage('Unexpected error while generating result.', 'error');
                resetPreview();
            } finally {
                loadResultBtn.disabled = false;
                loadResultBtn.textContent = 'Load Result';
            }
        }

        async function publishResults() {
            clearMessage();

            const payload = {
                class_id: Number(classInput.value),
                session: sessionInput.value,
                exam_type: examTypeInput.value
            };

            if (!payload.class_id || !payload.session || !payload.exam_type) {
                showMessage('Class, session and exam type are required to publish.', 'error');
                return;
            }

            publishResultsBtn.disabled = true;
            publishResultsBtn.textContent = 'Publishing...';

            try {
                const response = await fetch(`{{ route('principal.results.publish') }}`, {
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
                    showMessage(result.message || 'Failed to publish results.', 'error');
                    return;
                }

                const notifiedUsers = Number(result.summary?.notified_users ?? 0);
                showMessage(`Results published. Notifications sent to ${notifiedUsers} user(s).`);
            } catch (error) {
                showMessage('Unexpected error while publishing results.', 'error');
            } finally {
                publishResultsBtn.disabled = false;
                publishResultsBtn.textContent = 'Publish Results';
            }
        }

        classInput.addEventListener('change', async () => {
            await loadStudents();
            resetPreview();
        });

        loadResultBtn.addEventListener('click', loadResult);
        publishResultsBtn.addEventListener('click', publishResults);

        loadStudents();
    </script>
</x-app-layout>
