<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Payroll Profiles
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($canManage)
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900">Create Payroll Profile</h3>
                        <form method="POST" action="{{ route('principal.payroll.profiles.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            @csrf

                            <div class="md:col-span-3">
                                <x-input-label for="user_id" value="Employee User" />
                                <select id="user_id" name="user_id" required class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select user</option>
                                    @foreach($eligibleUsers as $user)
                                        <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="basic_salary" value="Basic Salary" />
                                <x-text-input id="basic_salary" name="basic_salary" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('basic_salary') }}" required />
                            </div>

                            <div>
                                <x-input-label for="allowances" value="Base Allowances" />
                                <x-text-input id="allowances" name="allowances" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('allowances', '0') }}" />
                            </div>

                            <div>
                                <x-input-label for="deductions" value="Base Deductions" />
                                <x-text-input id="deductions" name="deductions" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('deductions', '0') }}" />
                            </div>

                            <div>
                                <x-input-label for="bank_name" value="Bank Name" />
                                <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block min-h-11 w-full" value="{{ old('bank_name') }}" />
                            </div>

                            <div>
                                <x-input-label for="account_no" value="Account No" />
                                <x-text-input id="account_no" name="account_no" type="text" class="mt-1 block min-h-11 w-full" value="{{ old('account_no') }}" />
                            </div>

                            <div>
                                <x-input-label for="status" value="Status" />
                                <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                                </select>
                            </div>

                            <div class="md:col-span-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-900">Allowance Rows</h4>
                                        <button type="button" id="addAllowanceBtn" class="text-xs font-medium text-indigo-700 hover:text-indigo-900">+ Add</button>
                                    </div>
                                    <div id="allowanceRows" class="space-y-2"></div>
                                </div>

                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-900">Deduction Rows</h4>
                                        <button type="button" id="addDeductionBtn" class="text-xs font-medium text-indigo-700 hover:text-indigo-900">+ Add</button>
                                    </div>
                                    <div id="deductionRows" class="space-y-2"></div>
                                </div>
                            </div>

                            <div class="md:col-span-3">
                                <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                    Save Payroll Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Search</h3>
                    <form method="GET" action="{{ route('principal.payroll.profiles.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <x-input-label for="search" value="Name or Email" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['search'] }}" />
                        </div>
                        <div>
                            <x-input-label for="status_filter" value="Status" />
                            <select id="status_filter" name="status" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="active" @selected($filters['status'] === 'active')>Active</option>
                                <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Apply</button>
                            <a href="{{ route('principal.payroll.profiles.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[1200px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Basic</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Allowances</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Deductions</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Net Estimate</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Bank</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($profiles as $profile)
                                @php
                                    $allowancesTotal = (float) $profile->allowances + (float) $profile->allowancesRows->sum('amount');
                                    $deductionsTotal = (float) $profile->deductions + (float) $profile->deductionsRows->sum('amount');
                                    $netEstimate = max((float) $profile->basic_salary + $allowancesTotal - $deductionsTotal, 0);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        <div class="font-medium">{{ $profile->user?->name ?? 'User' }}</div>
                                        <div class="text-xs text-gray-500">{{ $profile->user?->email ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $profile->basic_salary, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format($allowancesTotal, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format($deductionsTotal, 2) }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ number_format($netEstimate, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <div>{{ $profile->bank_name ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $profile->account_no ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($profile->status === 'active')
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Active</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($canEdit)
                                            <a href="{{ route('principal.payroll.profiles.edit', $profile) }}" class="inline-flex min-h-10 items-center rounded-md border border-indigo-300 px-3 text-xs font-medium text-indigo-700 hover:bg-indigo-50">Edit</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No payroll profiles found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4">
                    {{ $profiles->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        const allowanceRows = document.getElementById('allowanceRows');
        const deductionRows = document.getElementById('deductionRows');
        const addAllowanceBtn = document.getElementById('addAllowanceBtn');
        const addDeductionBtn = document.getElementById('addDeductionBtn');

        let allowanceIndex = 0;
        let deductionIndex = 0;

        function rowTemplate(type, index) {
            return `
                <div class="grid grid-cols-12 gap-2" data-row>
                    <input type="text" name="${type}_items[${index}][title]" placeholder="Title" class="col-span-7 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <input type="number" min="0" step="0.01" name="${type}_items[${index}][amount]" placeholder="Amount" class="col-span-4 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <button type="button" class="col-span-1 rounded-md border border-red-200 text-xs text-red-600 hover:bg-red-50" data-remove>&times;</button>
                </div>
            `;
        }

        function addRow(type) {
            if (type === 'allowance') {
                allowanceRows.insertAdjacentHTML('beforeend', rowTemplate(type, allowanceIndex++));
                return;
            }

            deductionRows.insertAdjacentHTML('beforeend', rowTemplate(type, deductionIndex++));
        }

        function bindRemove(container) {
            container.addEventListener('click', (event) => {
                const target = event.target;
                if (!target.matches('[data-remove]')) {
                    return;
                }

                const row = target.closest('[data-row]');
                if (row) {
                    row.remove();
                }
            });
        }

        if (addAllowanceBtn) {
            addAllowanceBtn.addEventListener('click', () => addRow('allowance'));
        }
        if (addDeductionBtn) {
            addDeductionBtn.addEventListener('click', () => addRow('deduction'));
        }
        if (allowanceRows) {
            bindRemove(allowanceRows);
        }
        if (deductionRows) {
            bindRemove(deductionRows);
        }
    </script>
</x-app-layout>
