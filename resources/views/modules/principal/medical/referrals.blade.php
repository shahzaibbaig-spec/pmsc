<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Medical Referrals
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Create Referral</h3>
                    <p class="text-sm text-gray-600 mt-1">Search student, select illness, and submit referral to Doctor.</p>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <form id="referralForm" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="hidden" id="student_id" name="student_id">

                        <div class="md:col-span-2 relative">
                            <x-input-label for="student_search" value="Search Student" />
                            <x-text-input id="student_search" type="text" class="mt-1 block w-full" placeholder="Name, student ID, father name" autocomplete="off" />
                            <div id="searchResults" class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg hidden max-h-56 overflow-y-auto"></div>
                            <p id="selectedStudentText" class="mt-2 text-xs text-gray-600"></p>
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

                        <div id="otherIllnessWrapper" class="hidden md:col-span-2">
                            <x-input-label for="illness_other_text" value="Other Illness Detail" />
                            <x-text-input id="illness_other_text" name="illness_other_text" type="text" class="mt-1 block w-full" />
                        </div>

                        <div class="md:col-span-5">
                            <x-primary-button id="submitReferralBtn">Submit Referral</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <h3 class="text-lg font-medium text-gray-900">Medical History</h3>
                        <a href="{{ route('medical.reports.index') }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Monthly / Yearly Reports
                        </a>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                        <div>
                            <x-input-label for="search" value="Search" />
                            <x-text-input id="search" type="text" class="mt-1 block w-full" placeholder="Student / illness / doctor" />
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
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Illness</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Doctor</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Diagnosis</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                </tr>
                            </thead>
                            <tbody id="historyBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Loading medical history...</td>
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
        const submitReferralBtn = document.getElementById('submitReferralBtn');
        const referralForm = document.getElementById('referralForm');
        const messageBox = document.getElementById('messageBox');

        const historyBody = document.getElementById('historyBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');
        const searchFilter = document.getElementById('search');
        const statusFilter = document.getElementById('status');
        const monthFilter = document.getElementById('month');
        const yearFilter = document.getElementById('year');
        const perPageFilter = document.getElementById('per_page');

        let state = { page: 1, per_page: 10, search: '', status: '', month: '', year: new Date().getFullYear() };

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
            messageBox.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            messageBox.textContent = text;
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

        async function searchStudents() {
            const q = studentSearchInput.value.trim();
            if (q.length < 2) {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
                return;
            }

            const params = new URLSearchParams({ q });
            const response = await fetch(`{{ route('medical.students.search') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                return;
            }

            const result = await response.json();
            const rows = result.data || [];

            if (!rows.length) {
                searchResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No students found</div>';
                searchResults.classList.remove('hidden');
                return;
            }

            searchResults.innerHTML = rows.map(student => `
                <button type="button" class="student-result w-full text-left px-3 py-2 text-sm hover:bg-gray-50" data-id="${student.id}" data-name="${escapeHtml(student.name)}" data-student-id="${escapeHtml(student.student_id)}" data-class="${escapeHtml(student.class_name)}">
                    <div class="font-medium text-gray-900">${escapeHtml(student.name)} (${escapeHtml(student.student_id)})</div>
                    <div class="text-xs text-gray-500">${escapeHtml(student.class_name)}</div>
                </button>
            `).join('');
            searchResults.classList.remove('hidden');
        }

        async function loadHistory() {
            historyBody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Loading medical history...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.per_page,
                search: state.search,
                status: state.status,
                month: state.month,
                year: state.year
            });

            const response = await fetch(`{{ route('principal.medical.referrals.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                historyBody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-red-600">Failed to load medical history.</td></tr>';
                return;
            }

            const result = await response.json();
            const rows = result.data || [];

            if (!rows.length) {
                historyBody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No records found.</td></tr>';
            } else {
                historyBody.innerHTML = rows.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.created_at)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.student_name)} (${escapeHtml(row.student_id)})</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.illness_label)}${row.illness_other_text ? ' - ' + escapeHtml(row.illness_other_text) : ''}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.doctor_name ?? '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.diagnosis ?? '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">
                            ${row.status === 'completed'
                                ? '<span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Completed</span>'
                                : '<span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Pending</span>'
                            }
                        </td>
                    </tr>
                `).join('');
            }

            paginationInfo.textContent = `Page ${result.meta.current_page} of ${result.meta.last_page} | Total: ${result.meta.total}`;
            prevPageBtn.disabled = result.meta.current_page <= 1;
            nextPageBtn.disabled = result.meta.current_page >= result.meta.last_page;
        }

        referralForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearMessage();

            if (!studentIdInput.value) {
                showMessage('Please select a student from search results.', 'error');
                return;
            }

            const payload = {
                student_id: Number(studentIdInput.value),
                illness_type: illnessTypeInput.value,
                illness_other_text: illnessTypeInput.value === 'other' ? otherIllnessInput.value.trim() : null
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
                referralForm.reset();
                studentIdInput.value = '';
                selectedStudentText.textContent = '';
                otherIllnessWrapper.classList.add('hidden');
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

        const onStudentSearchInput = window.NSMS.debounce(searchStudents, 300);
        studentSearchInput.addEventListener('input', onStudentSearchInput);

        searchResults.addEventListener('click', (event) => {
            const button = event.target.closest('.student-result');
            if (!button) return;

            studentIdInput.value = button.dataset.id;
            studentSearchInput.value = `${button.dataset.name} (${button.dataset.studentId})`;
            selectedStudentText.textContent = `Selected: ${button.dataset.name} | Class: ${button.dataset.class}`;
            searchResults.classList.add('hidden');
            searchResults.innerHTML = '';
        });

        document.addEventListener('click', (event) => {
            if (!searchResults.contains(event.target) && event.target !== studentSearchInput) {
                searchResults.classList.add('hidden');
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

        statusFilter.addEventListener('change', async () => {
            state.status = statusFilter.value;
            state.page = 1;
            await loadHistory();
        });

        monthFilter.addEventListener('change', async () => {
            state.month = monthFilter.value;
            state.page = 1;
            await loadHistory();
        });

        yearFilter.addEventListener('change', async () => {
            state.year = yearFilter.value;
            state.page = 1;
            await loadHistory();
        });

        perPageFilter.addEventListener('change', async () => {
            state.per_page = perPageFilter.value;
            state.page = 1;
            await loadHistory();
        });

        window.NSMS.lazyInit(historyBody, loadHistory);
    </script>
</x-app-layout>
