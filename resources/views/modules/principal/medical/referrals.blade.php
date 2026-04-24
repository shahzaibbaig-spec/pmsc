<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Medical Referrals & Visits
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(auth()->user()?->hasRole('Principal') && auth()->user()?->can('create_medical_requests'))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Create Principal Referral</h3>
                        <p class="text-sm text-gray-600 mt-1">Search student, choose doctor, select illness, and submit referral.</p>

                        <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                        <form id="referralForm" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-4">
                            <input type="hidden" id="student_id" name="student_id">

                            <div class="md:col-span-2 relative">
                                <x-input-label for="student_search" value="Search Student" />
                                <x-text-input id="student_search" type="text" class="mt-1 block w-full" placeholder="Name, student ID, father name" autocomplete="off" />
                                <div id="searchResults" class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg hidden max-h-56 overflow-y-auto"></div>
                                <p id="selectedStudentText" class="mt-2 text-xs text-gray-600"></p>
                            </div>

                            <div>
                                <x-input-label for="doctor_id" value="Doctor" />
                                <select id="doctor_id" name="doctor_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @disabled(empty($availableDoctors))>
                                    @forelse($availableDoctors as $doctor)
                                        <option value="{{ $doctor['id'] }}">{{ $doctor['name'] }}{{ $doctor['email'] !== '' ? ' ('.$doctor['email'].')' : '' }}</option>
                                    @empty
                                        <option value="">No doctor account available</option>
                                    @endforelse
                                </select>
                            </div>

                            <div>
                                <x-input-label for="illness_type" value="Illness" />
                                <select id="illness_type" name="illness_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="fever">Fever</option>
                                    <option value="headache">Headache</option>
                                    <option value="stomach_ache">Stomach Ache</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <x-input-label for="session" value="Session" />
                                <select id="session" name="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($sessionOptions as $session)
                                        <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="otherIllnessWrapper" class="hidden md:col-span-2">
                                <x-input-label for="illness_other_text" value="Other Illness Detail" />
                                <x-text-input id="illness_other_text" name="illness_other_text" type="text" class="mt-1 block w-full" />
                            </div>

                            <div class="md:col-span-6">
                                <x-primary-button id="submitReferralBtn" @disabled(empty($availableDoctors))>Submit Referral</x-primary-button>
                                @if (empty($availableDoctors))
                                    <p class="mt-2 text-xs text-red-600">No active doctor account found. Ask admin to activate a doctor user first.</p>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">All Medical Cases</h3>
                        <a href="{{ route('medical.reports.index') }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Monthly / Yearly Reports
                        </a>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">Includes Principal referrals and Doctor direct visits with source tracking.</p>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
                        <div>
                            <x-input-label for="search" value="Search" />
                            <x-text-input id="search" type="text" class="mt-1 block w-full" placeholder="Student / problem / doctor" />
                        </div>
                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="source_type" value="Source" />
                            <select id="source_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sources</option>
                                <option value="principal_referral">Principal Referral</option>
                                <option value="doctor_direct">Doctor Direct Visit</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="doctor_filter_id" value="Doctor" />
                            <select id="doctor_filter_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Doctors</option>
                                @foreach($availableDoctors as $doctor)
                                    <option value="{{ $doctor['id'] }}">{{ $doctor['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="relative">
                            <x-input-label for="student_filter_search" value="Student" />
                            <x-text-input id="student_filter_search" type="text" class="mt-1 block w-full" placeholder="Search student" autocomplete="off" />
                            <input type="hidden" id="student_filter_id">
                            <div id="studentFilterResults" class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg hidden max-h-56 overflow-y-auto"></div>
                        </div>
                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Classes</option>
                                @foreach($classOptions as $classOption)
                                    <option value="{{ $classOption['id'] }}">{{ $classOption['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="date_from" value="Date From" />
                            <x-text-input id="date_from" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="date_to" value="Date To" />
                            <x-text-input id="date_to" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="session_filter" value="Session" />
                            <select id="session_filter" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sessions</option>
                                @foreach($sessionOptions as $session)
                                    <option value="{{ $session }}">{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="month" value="Month" />
                            <select id="month" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <x-input-label for="year" value="Year" />
                            <x-text-input id="year" type="number" class="mt-1 block w-full" value="{{ now()->year }}" />
                        </div>
                        <div>
                            <x-input-label for="per_page" value="Per Page" />
                            <select id="per_page" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Source</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Class</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Problem</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Doctor</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Diagnosis</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Session</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                </tr>
                            </thead>
                            <tbody id="historyBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">Loading medical history...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <p id="paginationInfo" class="text-sm text-gray-600">-</p>
                        <div class="flex gap-2">
                            <button id="prevPage" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
                            <button id="nextPage" type="button" class="rounded-md border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const studentIdInput = document.getElementById('student_id');
        const studentSearchInput = document.getElementById('student_search');
        const searchResults = document.getElementById('searchResults');
        const selectedStudentText = document.getElementById('selectedStudentText');
        const illnessTypeInput = document.getElementById('illness_type');
        const otherIllnessWrapper = document.getElementById('otherIllnessWrapper');
        const otherIllnessInput = document.getElementById('illness_other_text');
        const doctorIdInput = document.getElementById('doctor_id');
        const sessionInput = document.getElementById('session');
        const submitReferralBtn = document.getElementById('submitReferralBtn');
        const referralForm = document.getElementById('referralForm');
        const messageBox = document.getElementById('messageBox');

        const historyBody = document.getElementById('historyBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');
        const searchFilter = document.getElementById('search');
        const statusFilter = document.getElementById('status');
        const sourceTypeFilter = document.getElementById('source_type');
        const doctorFilterId = document.getElementById('doctor_filter_id');
        const studentFilterSearch = document.getElementById('student_filter_search');
        const studentFilterId = document.getElementById('student_filter_id');
        const studentFilterResults = document.getElementById('studentFilterResults');
        const classFilter = document.getElementById('class_id');
        const dateFromFilter = document.getElementById('date_from');
        const dateToFilter = document.getElementById('date_to');
        const sessionFilter = document.getElementById('session_filter');
        const monthFilter = document.getElementById('month');
        const yearFilter = document.getElementById('year');
        const perPageFilter = document.getElementById('per_page');

        let state = {
            page: 1,
            per_page: 10,
            search: '',
            status: '',
            source_type: '',
            doctor_id: '',
            student_id: '',
            class_id: '',
            date_from: '',
            date_to: '',
            session: '',
            month: '',
            year: new Date().getFullYear(),
        };

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showMessage(text, type = 'success') {
            if (!messageBox) return;
            messageBox.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            messageBox.textContent = text;
            if (type === 'error') {
                messageBox.classList.add('bg-red-50', 'text-red-700');
            } else {
                messageBox.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function clearMessage() {
            if (!messageBox) return;
            messageBox.classList.add('hidden');
            messageBox.textContent = '';
        }

        function sourceBadge(sourceType) {
            if (sourceType === 'doctor_direct') {
                return '<span class="inline-flex rounded-full bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-800">Doctor Direct</span>';
            }
            return '<span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800">Principal Referral</span>';
        }

        function statusBadge(status) {
            if (status === 'completed') {
                return '<span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Completed</span>';
            }
            return '<span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Pending</span>';
        }

        function renderStudentResults(container, rows, onSelect) {
            if (!rows.length) {
                container.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No students found</div>';
                container.classList.remove('hidden');
                return;
            }

            container.innerHTML = rows.map(student => `
                <button type="button" class="student-result w-full text-left px-3 py-2 text-sm hover:bg-gray-50" data-id="${student.id}" data-name="${escapeHtml(student.name)}" data-student-id="${escapeHtml(student.student_id)}" data-class="${escapeHtml(student.class_name)}">
                    <div class="font-medium text-gray-900">${escapeHtml(student.name)} (${escapeHtml(student.student_id)})</div>
                    <div class="text-xs text-gray-500">${escapeHtml(student.class_name)}</div>
                </button>
            `).join('');
            container.classList.remove('hidden');

            container.querySelectorAll('.student-result').forEach((button) => {
                button.addEventListener('click', () => onSelect(button.dataset));
            });
        }

        async function fetchStudents(q) {
            const params = new URLSearchParams({ q });
            const response = await fetch(`{{ route('medical.students.search') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                return [];
            }

            const result = await response.json();
            return result.data || [];
        }

        async function searchCreateReferralStudents() {
            if (!studentSearchInput || !searchResults) return;
            const q = studentSearchInput.value.trim();
            if (q.length < 2) {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
                return;
            }

            const rows = await fetchStudents(q);
            renderStudentResults(searchResults, rows, (student) => {
                studentIdInput.value = student.id;
                studentSearchInput.value = `${student.name} (${student.studentId})`;
                selectedStudentText.textContent = `Selected: ${student.name} | Class: ${student.class}`;
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
            });
        }

        async function searchFilterStudents() {
            const q = studentFilterSearch.value.trim();
            if (q.length < 2) {
                studentFilterResults.classList.add('hidden');
                studentFilterResults.innerHTML = '';
                studentFilterId.value = '';
                state.student_id = '';
                state.page = 1;
                await loadHistory();
                return;
            }

            const rows = await fetchStudents(q);
            renderStudentResults(studentFilterResults, rows, async (student) => {
                studentFilterId.value = student.id;
                studentFilterSearch.value = `${student.name} (${student.studentId})`;
                studentFilterResults.classList.add('hidden');
                studentFilterResults.innerHTML = '';
                state.student_id = student.id;
                state.page = 1;
                await loadHistory();
            });
        }

        async function loadHistory() {
            historyBody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">Loading medical history...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.per_page,
                search: state.search,
                status: state.status,
                source_type: state.source_type,
                doctor_id: state.doctor_id,
                student_id: state.student_id,
                class_id: state.class_id,
                date_from: state.date_from,
                date_to: state.date_to,
                session: state.session,
                month: state.month,
                year: state.year,
            });

            const response = await fetch(`{{ route('principal.medical.referrals.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                historyBody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-sm text-red-600">Failed to load medical history.</td></tr>';
                return;
            }

            const result = await response.json();
            const rows = result.data || [];

            if (!rows.length) {
                historyBody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">No records found.</td></tr>';
            } else {
                historyBody.innerHTML = rows.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.visit_date || row.created_at)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${sourceBadge(row.source_type)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.student_name)} (${escapeHtml(row.student_id)})</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.class_name)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.problem || row.illness_label || '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.doctor_name || '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.diagnosis || '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.session || '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${statusBadge(row.status)}</td>
                    </tr>
                `).join('');
            }

            paginationInfo.textContent = `Page ${result.meta.current_page} of ${result.meta.last_page} | Total: ${result.meta.total}`;
            prevPageBtn.disabled = result.meta.current_page <= 1;
            nextPageBtn.disabled = result.meta.current_page >= result.meta.last_page;
        }

        if (referralForm) {
            referralForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearMessage();

                if (!studentIdInput.value) {
                    showMessage('Please select a student from search results.', 'error');
                    return;
                }
                if (!doctorIdInput || !doctorIdInput.value) {
                    showMessage('Please select a doctor for this referral.', 'error');
                    return;
                }

                const payload = {
                    student_id: Number(studentIdInput.value),
                    doctor_id: Number(doctorIdInput.value),
                    illness_type: illnessTypeInput.value,
                    illness_other_text: illnessTypeInput.value === 'other' ? otherIllnessInput.value.trim() : null,
                    session: sessionInput.value,
                };

                submitReferralBtn.disabled = true;
                submitReferralBtn.textContent = 'Submitting...';

                try {
                    const response = await fetch(`{{ route('principal.medical.referrals.store') }}`, {
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
                        if (result.errors) {
                            const msg = Object.values(result.errors).flat().join(' ');
                            showMessage(msg || 'Failed to submit referral.', 'error');
                        } else {
                            showMessage(result.message || 'Failed to submit referral.', 'error');
                        }
                        return;
                    }

                    showMessage('Referral submitted and doctor notified.');
                    const selectedDoctorId = doctorIdInput.value;
                    const selectedSession = sessionInput.value;
                    referralForm.reset();
                    studentIdInput.value = '';
                    selectedStudentText.textContent = '';
                    otherIllnessWrapper.classList.add('hidden');
                    doctorIdInput.value = selectedDoctorId;
                    sessionInput.value = selectedSession;
                    await loadHistory();
                } catch (error) {
                    showMessage('Unexpected error while submitting referral.', 'error');
                } finally {
                    submitReferralBtn.disabled = false;
                    submitReferralBtn.textContent = 'Submit Referral';
                }
            });

            illnessTypeInput.addEventListener('change', () => {
                if (illnessTypeInput.value === 'other') {
                    otherIllnessWrapper.classList.remove('hidden');
                } else {
                    otherIllnessWrapper.classList.add('hidden');
                    otherIllnessInput.value = '';
                }
            });

            const onStudentSearchInput = window.NSMS.debounce(searchCreateReferralStudents, 300);
            studentSearchInput.addEventListener('input', onStudentSearchInput);
        }

        const onFilterStudentInput = window.NSMS.debounce(searchFilterStudents, 300);
        studentFilterSearch.addEventListener('input', onFilterStudentInput);

        document.addEventListener('click', (event) => {
            if (searchResults && !searchResults.contains(event.target) && event.target !== studentSearchInput) {
                searchResults.classList.add('hidden');
            }
            if (!studentFilterResults.contains(event.target) && event.target !== studentFilterSearch) {
                studentFilterResults.classList.add('hidden');
            }
        });

        prevPageBtn.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadHistory();
            }
        });

        nextPageBtn.addEventListener('click', async () => {
            state.page += 1;
            await loadHistory();
        });

        const onHistorySearchInput = window.NSMS.debounce(async () => {
            state.search = searchFilter.value.trim();
            state.page = 1;
            await loadHistory();
        }, 300);
        searchFilter.addEventListener('input', onHistorySearchInput);

        statusFilter.addEventListener('change', async () => { state.status = statusFilter.value; state.page = 1; await loadHistory(); });
        sourceTypeFilter.addEventListener('change', async () => { state.source_type = sourceTypeFilter.value; state.page = 1; await loadHistory(); });
        doctorFilterId.addEventListener('change', async () => { state.doctor_id = doctorFilterId.value; state.page = 1; await loadHistory(); });
        classFilter.addEventListener('change', async () => { state.class_id = classFilter.value; state.page = 1; await loadHistory(); });
        dateFromFilter.addEventListener('change', async () => { state.date_from = dateFromFilter.value; state.page = 1; await loadHistory(); });
        dateToFilter.addEventListener('change', async () => { state.date_to = dateToFilter.value; state.page = 1; await loadHistory(); });
        sessionFilter.addEventListener('change', async () => { state.session = sessionFilter.value; state.page = 1; await loadHistory(); });
        monthFilter.addEventListener('change', async () => { state.month = monthFilter.value; state.page = 1; await loadHistory(); });
        yearFilter.addEventListener('change', async () => { state.year = yearFilter.value; state.page = 1; await loadHistory(); });
        perPageFilter.addEventListener('change', async () => { state.per_page = perPageFilter.value; state.page = 1; await loadHistory(); });

        window.NSMS.lazyInit(historyBody, loadHistory);
    </script>
</x-app-layout>
