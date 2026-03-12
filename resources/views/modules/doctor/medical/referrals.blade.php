<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Doctor Medical Referrals
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                            <p class="text-sm text-gray-600 mt-1">You receive a notification when principal submits a referral.</p>
                        </div>
                        <a href="{{ route('medical.reports.index') }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Monthly / Yearly Reports
                        </a>
                    </div>
                    <div class="mt-3">
                        @if($unreadNotifications->isEmpty())
                            <p class="text-sm text-gray-500">No unread notifications.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach($unreadNotifications as $notification)
                                    <li class="rounded-md border border-gray-200 px-3 py-2 text-sm">
                                        <span class="font-medium">{{ $notification->data['message'] ?? 'New medical referral' }}</span>
                                        <span class="text-gray-500"> | Student: {{ $notification->data['student_name'] ?? '-' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Referral List</h3>
                    <p class="text-sm text-gray-600 mt-1">Select a referral and add diagnosis, prescription, and notes.</p>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                        <div>
                            <x-input-label for="search" value="Search" />
                            <x-text-input id="search" type="text" class="mt-1 block w-full" placeholder="Student / illness" />
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
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Action</th>
                                </tr>
                            </thead>
                            <tbody id="referralBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Loading referrals...</td>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Update Medical Record</h3>
                    <p id="selectedReferralText" class="text-sm text-gray-600 mt-1">Select a referral from the table.</p>

                    <form id="updateForm" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="hidden" id="referral_id">

                        <div class="md:col-span-2">
                            <x-input-label for="diagnosis" value="Diagnosis" />
                            <textarea id="diagnosis" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3"></textarea>
                        </div>
                        <div>
                            <x-input-label for="prescription" value="Prescription" />
                            <textarea id="prescription" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3"></textarea>
                        </div>
                        <div>
                            <x-input-label for="notes" value="Notes" />
                            <textarea id="notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3"></textarea>
                        </div>
                        <div>
                            <x-input-label for="update_status" value="Status" />
                            <select id="update_status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-primary-button id="saveRecordBtn">Save Medical Record</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const referralBody = document.getElementById('referralBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');
        const searchFilter = document.getElementById('search');
        const statusFilter = document.getElementById('status');
        const monthFilter = document.getElementById('month');
        const yearFilter = document.getElementById('year');
        const perPageFilter = document.getElementById('per_page');
        const messageBox = document.getElementById('messageBox');

        const referralIdInput = document.getElementById('referral_id');
        const diagnosisInput = document.getElementById('diagnosis');
        const prescriptionInput = document.getElementById('prescription');
        const notesInput = document.getElementById('notes');
        const updateStatusInput = document.getElementById('update_status');
        const saveRecordBtn = document.getElementById('saveRecordBtn');
        const selectedReferralText = document.getElementById('selectedReferralText');

        let state = { page: 1, per_page: 10, search: '', status: '', month: '', year: new Date().getFullYear() };
        let referralsCache = [];

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

        async function loadReferrals() {
            referralBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Loading referrals...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.per_page,
                search: state.search,
                status: state.status,
                month: state.month,
                year: state.year
            });

            const response = await fetch(`{{ route('doctor.medical.referrals.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                referralBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-red-600">Failed to load referrals.</td></tr>';
                return;
            }

            const result = await response.json();
            referralsCache = result.data || [];

            if (!referralsCache.length) {
                referralBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No referrals found.</td></tr>';
            } else {
                referralBody.innerHTML = referralsCache.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.created_at)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.student_name)} (${escapeHtml(row.student_id)})</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.illness_label)}${row.illness_other_text ? ' - ' + escapeHtml(row.illness_other_text) : ''}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">
                            ${row.status === 'completed'
                                ? '<span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Completed</span>'
                                : '<span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Pending</span>'
                            }
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <button type="button" class="edit-btn rounded-md bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700" data-id="${row.id}">Update</button>
                        </td>
                    </tr>
                `).join('');
            }

            paginationInfo.textContent = `Page ${result.meta.current_page} of ${result.meta.last_page} | Total: ${result.meta.total}`;
            prevPageBtn.disabled = result.meta.current_page <= 1;
            nextPageBtn.disabled = result.meta.current_page >= result.meta.last_page;
        }

        referralBody.addEventListener('click', (event) => {
            const button = event.target.closest('.edit-btn');
            if (!button) return;

            const id = Number(button.dataset.id);
            const row = referralsCache.find(item => Number(item.id) === id);
            if (!row) return;

            referralIdInput.value = row.id;
            diagnosisInput.value = row.diagnosis ?? '';
            prescriptionInput.value = row.prescription ?? '';
            notesInput.value = row.notes ?? '';
            updateStatusInput.value = row.status ?? 'pending';
            selectedReferralText.textContent = `Selected: ${row.student_name} | Illness: ${row.illness_label}`;
        });

        document.getElementById('updateForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const referralId = Number(referralIdInput.value);
            if (!referralId) {
                showMessage('Select a referral to update.', 'error');
                return;
            }

            const payload = {
                diagnosis: diagnosisInput.value.trim(),
                prescription: prescriptionInput.value.trim() || null,
                notes: notesInput.value.trim() || null,
                status: updateStatusInput.value
            };

            saveRecordBtn.disabled = true;
            saveRecordBtn.textContent = 'Saving...';

            try {
                const response = await fetch(`/doctor/medical/referrals/${referralId}`, {
                    method: 'PUT',
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
                        showMessage(msg || 'Failed to save medical record.', 'error');
                    } else {
                        showMessage(result.message || 'Failed to save medical record.', 'error');
                    }
                    return;
                }

                showMessage('Medical record updated successfully.');
                await loadReferrals();
            } catch (error) {
                showMessage('Unexpected error while saving medical record.', 'error');
            } finally {
                saveRecordBtn.disabled = false;
                saveRecordBtn.textContent = 'Save Medical Record';
            }
        });

        prevPageBtn.addEventListener('click', async () => {
            if (state.page > 1) {
                state.page -= 1;
                await loadReferrals();
            }
        });

        nextPageBtn.addEventListener('click', async () => {
            state.page += 1;
            await loadReferrals();
        });

        const onReferralSearchInput = window.NSMS.debounce(async () => {
            state.search = searchFilter.value.trim();
            state.page = 1;
            await loadReferrals();
        }, 300);
        searchFilter.addEventListener('input', onReferralSearchInput);

        statusFilter.addEventListener('change', async () => {
            state.status = statusFilter.value;
            state.page = 1;
            await loadReferrals();
        });

        monthFilter.addEventListener('change', async () => {
            state.month = monthFilter.value;
            state.page = 1;
            await loadReferrals();
        });

        yearFilter.addEventListener('change', async () => {
            state.year = yearFilter.value;
            state.page = 1;
            await loadReferrals();
        });

        perPageFilter.addEventListener('change', async () => {
            state.per_page = perPageFilter.value;
            state.page = 1;
            await loadReferrals();
        });

        window.NSMS.lazyInit(referralBody, loadReferrals);
    </script>
</x-app-layout>
