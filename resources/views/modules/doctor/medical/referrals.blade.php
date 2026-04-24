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
                            <p class="text-sm text-gray-600 mt-1">You receive notifications when Principal submits a referral.</p>
                        </div>
                        <a href="{{ route('medical.reports.index') }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Monthly / Yearly Reports
                        </a>
                    </div>
                    <div class="mt-3">
                        <div id="doctorNotificationList">
                            @if($unreadNotifications->isEmpty())
                                <p class="text-sm text-gray-500">No unread notifications.</p>
                            @else
                                <ul class="space-y-2">
                                    @foreach($unreadNotifications as $notification)
                                        <li class="rounded-md border border-gray-200 px-3 py-2 text-sm">
                                            <span class="font-medium">{{ $notification->data['message'] ?? 'New medical referral' }}</span>
                                            <span class="text-gray-500"> | Student: {{ $notification->data['student_name'] ?? '-' }}</span>
                                            <span class="text-gray-400"> | {{ optional($notification->created_at)->format('Y-m-d H:i') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div id="direct-visit" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Add Walk-in Student (Direct Visit)</h3>
                    <p class="text-sm text-gray-600 mt-1">Record a direct medical visit without Principal referral.</p>

                    <div id="directVisitMessageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <form id="directVisitForm" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="hidden" id="direct_student_id">

                        <div class="md:col-span-2 relative">
                            <x-input-label for="direct_student_search" value="Search Student" />
                            <x-text-input id="direct_student_search" type="text" class="mt-1 block w-full" placeholder="Name, student ID, father name" autocomplete="off" />
                            <div id="directStudentResults" class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg hidden max-h-56 overflow-y-auto"></div>
                            <p id="directSelectedStudentText" class="mt-2 text-xs text-gray-600"></p>
                        </div>

                        <div>
                            <x-input-label for="direct_visit_date" value="Visit Date" />
                            <x-text-input id="direct_visit_date" type="date" class="mt-1 block w-full" value="{{ now()->toDateString() }}" />
                        </div>

                        <div>
                            <x-input-label for="direct_session" value="Session" />
                            <select id="direct_session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessionOptions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="direct_problem" value="Medical Reason / Problem" />
                            <textarea id="direct_problem" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div>
                            <x-input-label for="direct_diagnosis" value="Diagnosis" />
                            <textarea id="direct_diagnosis" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div>
                            <x-input-label for="direct_prescription" value="Prescription" />
                            <textarea id="direct_prescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="direct_notes" value="Notes" />
                            <textarea id="direct_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <x-primary-button id="saveDirectVisitBtn">Save Direct Visit</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Assigned Cases (Referred + Direct)</h3>
                    <p class="text-sm text-gray-600 mt-1">Use source filter to focus only on Principal referrals or Doctor direct visits.</p>

                    <div id="messageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
                        <div>
                            <x-input-label for="search" value="Search" />
                            <x-text-input id="search" type="text" class="mt-1 block w-full" placeholder="Student / problem / diagnosis" />
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
                                <option value="principal_referral" selected>Principal Referral</option>
                                <option value="doctor_direct">Doctor Direct Visit</option>
                            </select>
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
                            <x-input-label for="has_cbc_report" value="CBC Report" />
                            <select id="has_cbc_report" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="1">Has CBC</option>
                                <option value="0">No CBC</option>
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
                            <x-input-label for="date_from" value="Date From" />
                            <x-text-input id="date_from" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="date_to" value="Date To" />
                            <x-text-input id="date_to" type="date" class="mt-1 block w-full" />
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
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Problem</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">CBC</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Action</th>
                                </tr>
                            </thead>
                            <tbody id="referralBody" class="divide-y divide-gray-200 bg-white">
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">Loading cases...</td>
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
                    <p id="selectedReferralText" class="text-sm text-gray-600 mt-1">Select a case from the table.</p>

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

                    <div class="mt-8 border-t pt-6">
                        <h4 class="text-md font-medium text-gray-900">CBC Blood Report</h4>
                        <p class="text-sm text-gray-600 mt-1">Attach CBC report to selected medical visit and keep historical entries.</p>
                        <div id="cbcMessageBox" class="mt-4 hidden rounded-md p-3 text-sm"></div>

                        <form id="cbcForm" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="cbc_report_date" value="Report Date" />
                                <x-text-input id="cbc_report_date" type="date" class="mt-1 block w-full" value="{{ now()->toDateString() }}" />
                            </div>
                            <div>
                                <x-input-label for="cbc_session" value="Session" />
                                <select id="cbc_session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($sessionOptions as $session)
                                        <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="cbc_machine_report_no" value="Machine Report No" />
                                <x-text-input id="cbc_machine_report_no" type="text" class="mt-1 block w-full" />
                            </div>
                            <div><x-input-label for="cbc_hemoglobin" value="Hemoglobin" /><x-text-input id="cbc_hemoglobin" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_rbc_count" value="RBC Count" /><x-text-input id="cbc_rbc_count" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_wbc_count" value="WBC Count" /><x-text-input id="cbc_wbc_count" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_platelet_count" value="Platelet Count" /><x-text-input id="cbc_platelet_count" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_hematocrit_pcv" value="Hematocrit PCV" /><x-text-input id="cbc_hematocrit_pcv" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_mcv" value="MCV" /><x-text-input id="cbc_mcv" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_mch" value="MCH" /><x-text-input id="cbc_mch" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_mchc" value="MCHC" /><x-text-input id="cbc_mchc" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_neutrophils" value="Neutrophils" /><x-text-input id="cbc_neutrophils" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_lymphocytes" value="Lymphocytes" /><x-text-input id="cbc_lymphocytes" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_monocytes" value="Monocytes" /><x-text-input id="cbc_monocytes" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_eosinophils" value="Eosinophils" /><x-text-input id="cbc_eosinophils" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_basophils" value="Basophils" /><x-text-input id="cbc_basophils" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div><x-input-label for="cbc_esr" value="ESR" /><x-text-input id="cbc_esr" type="number" step="0.01" class="mt-1 block w-full" /></div>
                            <div class="md:col-span-3">
                                <x-input-label for="cbc_remarks" value="Remarks" />
                                <textarea id="cbc_remarks" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                            <div class="md:col-span-3">
                                <x-primary-button id="saveCbcBtn">Add CBC Report</x-primary-button>
                            </div>
                        </form>

                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Report Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Machine #</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Doctor</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="cbcReportsBody" class="divide-y divide-gray-200 bg-white">
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Select a case to view attached CBC reports.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const directVisitMessageBox = document.getElementById('directVisitMessageBox');
        const directVisitForm = document.getElementById('directVisitForm');
        const directStudentIdInput = document.getElementById('direct_student_id');
        const directStudentSearchInput = document.getElementById('direct_student_search');
        const directStudentResults = document.getElementById('directStudentResults');
        const directSelectedStudentText = document.getElementById('directSelectedStudentText');
        const directVisitDateInput = document.getElementById('direct_visit_date');
        const directSessionInput = document.getElementById('direct_session');
        const directProblemInput = document.getElementById('direct_problem');
        const directDiagnosisInput = document.getElementById('direct_diagnosis');
        const directPrescriptionInput = document.getElementById('direct_prescription');
        const directNotesInput = document.getElementById('direct_notes');
        const saveDirectVisitBtn = document.getElementById('saveDirectVisitBtn');

        const referralBody = document.getElementById('referralBody');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');
        const searchFilter = document.getElementById('search');
        const statusFilter = document.getElementById('status');
        const sourceTypeFilter = document.getElementById('source_type');
        const sessionFilter = document.getElementById('session_filter');
        const hasCbcReportFilter = document.getElementById('has_cbc_report');
        const monthFilter = document.getElementById('month');
        const yearFilter = document.getElementById('year');
        const dateFromFilter = document.getElementById('date_from');
        const dateToFilter = document.getElementById('date_to');
        const perPageFilter = document.getElementById('per_page');
        const messageBox = document.getElementById('messageBox');

        const referralIdInput = document.getElementById('referral_id');
        const diagnosisInput = document.getElementById('diagnosis');
        const prescriptionInput = document.getElementById('prescription');
        const notesInput = document.getElementById('notes');
        const updateStatusInput = document.getElementById('update_status');
        const saveRecordBtn = document.getElementById('saveRecordBtn');
        const selectedReferralText = document.getElementById('selectedReferralText');
        const doctorNotificationList = document.getElementById('doctorNotificationList');
        const updateRouteTemplate = `{{ route('doctor.medical.referrals.update', ['medicalReferral' => '__REFERRAL__']) }}`;
        const cbcMessageBox = document.getElementById('cbcMessageBox');
        const cbcForm = document.getElementById('cbcForm');
        const cbcReportsBody = document.getElementById('cbcReportsBody');
        const saveCbcBtn = document.getElementById('saveCbcBtn');
        const cbcReportDateInput = document.getElementById('cbc_report_date');
        const cbcSessionInput = document.getElementById('cbc_session');
        const cbcMachineReportNoInput = document.getElementById('cbc_machine_report_no');
        const cbcHemoglobinInput = document.getElementById('cbc_hemoglobin');
        const cbcRbcCountInput = document.getElementById('cbc_rbc_count');
        const cbcWbcCountInput = document.getElementById('cbc_wbc_count');
        const cbcPlateletCountInput = document.getElementById('cbc_platelet_count');
        const cbcHematocritPcvInput = document.getElementById('cbc_hematocrit_pcv');
        const cbcMcvInput = document.getElementById('cbc_mcv');
        const cbcMchInput = document.getElementById('cbc_mch');
        const cbcMchcInput = document.getElementById('cbc_mchc');
        const cbcNeutrophilsInput = document.getElementById('cbc_neutrophils');
        const cbcLymphocytesInput = document.getElementById('cbc_lymphocytes');
        const cbcMonocytesInput = document.getElementById('cbc_monocytes');
        const cbcEosinophilsInput = document.getElementById('cbc_eosinophils');
        const cbcBasophilsInput = document.getElementById('cbc_basophils');
        const cbcEsrInput = document.getElementById('cbc_esr');
        const cbcRemarksInput = document.getElementById('cbc_remarks');
        const cbcAttachRouteTemplate = `{{ route('doctor.cbc-reports.attach', ['medicalReferral' => '__REFERRAL__']) }}`;

        let state = {
            page: 1,
            per_page: 10,
            search: '',
            status: '',
            source_type: 'principal_referral',
            session: '',
            has_cbc_report: '',
            month: '',
            year: new Date().getFullYear(),
            date_from: '',
            date_to: '',
        };
        let referralsCache = [];
        let pollingHandle = null;

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showMessage(target, text, type = 'success') {
            target.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            target.textContent = text;
            if (type === 'error') {
                target.classList.add('bg-red-50', 'text-red-700');
            } else {
                target.classList.add('bg-green-50', 'text-green-700');
            }
        }

        function sourceBadge(sourceType) {
            if (sourceType === 'doctor_direct') {
                return '<span class="inline-flex rounded-full bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-800">Direct</span>';
            }
            return '<span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800">Referral</span>';
        }

        function statusBadge(status) {
            if (status === 'completed') {
                return '<span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Completed</span>';
            }
            return '<span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Pending</span>';
        }

        function renderNotifications(items = []) {
            if (!doctorNotificationList) return;
            if (!items.length) {
                doctorNotificationList.innerHTML = '<p class="text-sm text-gray-500">No unread notifications.</p>';
                return;
            }

            doctorNotificationList.innerHTML = `
                <ul class="space-y-2">
                    ${items.map(item => `
                        <li class="rounded-md border border-gray-200 px-3 py-2 text-sm">
                            <span class="font-medium">${escapeHtml(item.message || 'New medical referral')}</span>
                            <span class="text-gray-500"> | Student: ${escapeHtml(item.student_name || '-')}</span>
                            <span class="text-gray-400"> | ${escapeHtml(item.created_at || '-')}</span>
                        </li>
                    `).join('')}
                </ul>
            `;
        }

        async function loadNotifications() {
            const response = await fetch(`{{ route('doctor.medical.referrals.notifications') }}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) return;
            const result = await response.json();
            renderNotifications(result.data || []);
        }

        async function fetchStudents(q) {
            const params = new URLSearchParams({ q });
            const response = await fetch(`{{ route('medical.students.search') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) return [];
            const result = await response.json();
            return result.data || [];
        }

        async function searchDirectStudents() {
            const q = directStudentSearchInput.value.trim();
            if (q.length < 2) {
                directStudentResults.classList.add('hidden');
                directStudentResults.innerHTML = '';
                return;
            }

            const rows = await fetchStudents(q);
            if (!rows.length) {
                directStudentResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No students found</div>';
                directStudentResults.classList.remove('hidden');
                return;
            }

            directStudentResults.innerHTML = rows.map(student => `
                <button type="button" class="student-result w-full text-left px-3 py-2 text-sm hover:bg-gray-50" data-id="${student.id}" data-name="${escapeHtml(student.name)}" data-student-id="${escapeHtml(student.student_id)}" data-class="${escapeHtml(student.class_name)}">
                    <div class="font-medium text-gray-900">${escapeHtml(student.name)} (${escapeHtml(student.student_id)})</div>
                    <div class="text-xs text-gray-500">${escapeHtml(student.class_name)}</div>
                </button>
            `).join('');
            directStudentResults.classList.remove('hidden');
        }

        async function saveDirectVisit(event) {
            event.preventDefault();

            if (!directStudentIdInput.value) {
                showMessage(directVisitMessageBox, 'Please select a student from search results.', 'error');
                return;
            }

            const payload = {
                student_id: Number(directStudentIdInput.value),
                visit_date: directVisitDateInput.value,
                session: directSessionInput.value,
                problem: directProblemInput.value.trim(),
                diagnosis: directDiagnosisInput.value.trim() || null,
                prescription: directPrescriptionInput.value.trim() || null,
                notes: directNotesInput.value.trim() || null,
            };

            saveDirectVisitBtn.disabled = true;
            saveDirectVisitBtn.textContent = 'Saving...';

            try {
                const response = await fetch(`{{ route('doctor.medical.direct-visits.store') }}`, {
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
                        showMessage(directVisitMessageBox, msg || 'Failed to save direct visit.', 'error');
                    } else {
                        showMessage(directVisitMessageBox, result.message || 'Failed to save direct visit.', 'error');
                    }
                    return;
                }

                showMessage(directVisitMessageBox, 'Direct visit saved and Principal/Admin notified.');
                const selectedSession = directSessionInput.value;
                const selectedDate = directVisitDateInput.value;
                directVisitForm.reset();
                directStudentIdInput.value = '';
                directSelectedStudentText.textContent = '';
                directSessionInput.value = selectedSession;
                directVisitDateInput.value = selectedDate;
                state.source_type = '';
                sourceTypeFilter.value = '';
                await loadReferrals();
            } catch (error) {
                showMessage(directVisitMessageBox, 'Unexpected error while saving direct visit.', 'error');
            } finally {
                saveDirectVisitBtn.disabled = false;
                saveDirectVisitBtn.textContent = 'Save Direct Visit';
            }
        }

        async function loadReferrals() {
            referralBody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">Loading cases...</td></tr>';

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.per_page,
                search: state.search,
                status: state.status,
                source_type: state.source_type,
                session: state.session,
                has_cbc_report: state.has_cbc_report,
                month: state.month,
                year: state.year,
                date_from: state.date_from,
                date_to: state.date_to,
            });

            const response = await fetch(`{{ route('doctor.medical.referrals.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                referralBody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-red-600">Failed to load cases.</td></tr>';
                return;
            }

            const result = await response.json();
            referralsCache = result.data || [];

            if (!referralsCache.length) {
                referralBody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No cases found.</td></tr>';
            } else {
                referralBody.innerHTML = referralsCache.map(row => `
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.visit_date || row.created_at)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${sourceBadge(row.source_type)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.student_name)} (${escapeHtml(row.student_id)})</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(row.problem || row.illness_label || '-')}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${Number(row.cbc_reports_count || 0)}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">${statusBadge(row.status)}</td>
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

        directVisitForm.addEventListener('submit', saveDirectVisit);

        directStudentResults.addEventListener('click', (event) => {
            const button = event.target.closest('.student-result');
            if (!button) return;
            directStudentIdInput.value = button.dataset.id;
            directStudentSearchInput.value = `${button.dataset.name} (${button.dataset.studentId})`;
            directSelectedStudentText.textContent = `Selected: ${button.dataset.name} | Class: ${button.dataset.class}`;
            directStudentResults.classList.add('hidden');
            directStudentResults.innerHTML = '';
        });

        const onDirectStudentSearchInput = window.NSMS.debounce(searchDirectStudents, 300);
        directStudentSearchInput.addEventListener('input', onDirectStudentSearchInput);

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
            selectedReferralText.textContent = `Selected: ${row.student_name} | Source: ${row.source_label} | Problem: ${row.problem || row.illness_label}`;
            renderAttachedCbcReports(row);
        });

        function renderAttachedCbcReports(row) {
            const reports = row?.cbc_reports || [];
            if (!reports.length) {
                cbcReportsBody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No CBC reports attached to this visit.</td></tr>';
                return;
            }

            cbcReportsBody.innerHTML = reports.map((cbc) => `
                <tr>
                    <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(cbc.report_date || '-')}</td>
                    <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(cbc.machine_report_no || '-')}</td>
                    <td class="px-4 py-2 text-sm text-gray-800">${escapeHtml(cbc.doctor_name || '-')}</td>
                    <td class="px-4 py-2 text-sm text-gray-800">
                        <a href="/doctor/cbc-reports/${encodeURIComponent(cbc.id)}" class="text-blue-700 hover:underline">View</a>
                        <span class="mx-1">|</span>
                        <a href="/doctor/cbc-reports/${encodeURIComponent(cbc.id)}/print" target="_blank" class="text-emerald-700 hover:underline">Print</a>
                    </td>
                </tr>
            `).join('');
        }

        function toNullableNumber(value) {
            const text = String(value ?? '').trim();
            if (text === '') {
                return null;
            }
            const numberValue = Number(text);
            return Number.isFinite(numberValue) ? numberValue : null;
        }

        cbcForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const referralId = Number(referralIdInput.value);
            if (!referralId) {
                showMessage(cbcMessageBox, 'Select a medical case before adding CBC report.', 'error');
                return;
            }

            const selectedCase = referralsCache.find((item) => Number(item.id) === referralId);
            if (!selectedCase) {
                showMessage(cbcMessageBox, 'Selected case not found in the current list.', 'error');
                return;
            }

            const payload = {
                student_medical_record_id: referralId,
                student_id: Number(selectedCase.student_db_id || 0),
                report_date: cbcReportDateInput.value,
                session: cbcSessionInput.value,
                machine_report_no: cbcMachineReportNoInput.value.trim() || null,
                hemoglobin: toNullableNumber(cbcHemoglobinInput.value),
                rbc_count: toNullableNumber(cbcRbcCountInput.value),
                wbc_count: toNullableNumber(cbcWbcCountInput.value),
                platelet_count: toNullableNumber(cbcPlateletCountInput.value),
                hematocrit_pcv: toNullableNumber(cbcHematocritPcvInput.value),
                mcv: toNullableNumber(cbcMcvInput.value),
                mch: toNullableNumber(cbcMchInput.value),
                mchc: toNullableNumber(cbcMchcInput.value),
                neutrophils: toNullableNumber(cbcNeutrophilsInput.value),
                lymphocytes: toNullableNumber(cbcLymphocytesInput.value),
                monocytes: toNullableNumber(cbcMonocytesInput.value),
                eosinophils: toNullableNumber(cbcEosinophilsInput.value),
                basophils: toNullableNumber(cbcBasophilsInput.value),
                esr: toNullableNumber(cbcEsrInput.value),
                remarks: cbcRemarksInput.value.trim() || null,
            };

            if (!payload.student_id || Number.isNaN(payload.student_id)) {
                showMessage(cbcMessageBox, 'Unable to resolve student for this case. Reload and try again.', 'error');
                return;
            }

            saveCbcBtn.disabled = true;
            saveCbcBtn.textContent = 'Saving CBC...';

            try {
                const response = await fetch(cbcAttachRouteTemplate.replace('__REFERRAL__', String(referralId)), {
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
                        showMessage(cbcMessageBox, msg || 'Failed to save CBC report.', 'error');
                    } else {
                        showMessage(cbcMessageBox, result.message || 'Failed to save CBC report.', 'error');
                    }
                    return;
                }

                showMessage(cbcMessageBox, 'CBC report attached successfully.');
                await loadReferrals();
                const refreshedRow = referralsCache.find((item) => Number(item.id) === referralId);
                if (refreshedRow) {
                    renderAttachedCbcReports(refreshedRow);
                }
            } catch (error) {
                showMessage(cbcMessageBox, 'Unexpected error while saving CBC report.', 'error');
            } finally {
                saveCbcBtn.disabled = false;
                saveCbcBtn.textContent = 'Add CBC Report';
            }
        });

        document.getElementById('updateForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const referralId = Number(referralIdInput.value);
            if (!referralId) {
                showMessage(messageBox, 'Select a case to update.', 'error');
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
                const response = await fetch(updateRouteTemplate.replace('__REFERRAL__', String(referralId)), {
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
                        showMessage(messageBox, msg || 'Failed to save medical record.', 'error');
                    } else {
                        showMessage(messageBox, result.message || 'Failed to save medical record.', 'error');
                    }
                    return;
                }

                showMessage(messageBox, 'Medical record updated successfully.');
                await loadReferrals();
            } catch (error) {
                showMessage(messageBox, 'Unexpected error while saving medical record.', 'error');
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

        statusFilter.addEventListener('change', async () => { state.status = statusFilter.value; state.page = 1; await loadReferrals(); });
        sourceTypeFilter.addEventListener('change', async () => { state.source_type = sourceTypeFilter.value; state.page = 1; await loadReferrals(); });
        sessionFilter.addEventListener('change', async () => { state.session = sessionFilter.value; state.page = 1; await loadReferrals(); });
        hasCbcReportFilter.addEventListener('change', async () => { state.has_cbc_report = hasCbcReportFilter.value; state.page = 1; await loadReferrals(); });
        monthFilter.addEventListener('change', async () => { state.month = monthFilter.value; state.page = 1; await loadReferrals(); });
        yearFilter.addEventListener('change', async () => { state.year = yearFilter.value; state.page = 1; await loadReferrals(); });
        dateFromFilter.addEventListener('change', async () => { state.date_from = dateFromFilter.value; state.page = 1; await loadReferrals(); });
        dateToFilter.addEventListener('change', async () => { state.date_to = dateToFilter.value; state.page = 1; await loadReferrals(); });
        perPageFilter.addEventListener('change', async () => { state.per_page = perPageFilter.value; state.page = 1; await loadReferrals(); });

        async function refreshDoctorPanel() {
            await Promise.all([loadReferrals(), loadNotifications()]);
        }

        window.NSMS.lazyInit(referralBody, refreshDoctorPanel);

        pollingHandle = window.setInterval(() => {
            if (document.visibilityState !== 'visible') return;
            refreshDoctorPanel();
        }, 15000);

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                refreshDoctorPanel();
            }
        });

        document.addEventListener('click', (event) => {
            if (!directStudentResults.contains(event.target) && event.target !== directStudentSearchInput) {
                directStudentResults.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>
