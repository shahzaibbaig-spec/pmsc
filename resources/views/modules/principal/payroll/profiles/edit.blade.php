<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Payroll Profile
        </h2>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <form method="POST" action="{{ route('principal.payroll.profiles.update', $profile) }}" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        @csrf
                        @method('PUT')

                        <div class="md:col-span-3">
                            <x-input-label for="user_id" value="Employee User" />
                            <select id="user_id" name="user_id" required class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($eligibleUsers as $user)
                                    <option value="{{ $user->id }}" @selected((string) old('user_id', $profile->user_id) === (string) $user->id)>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="basic_salary" value="Basic Salary" />
                            <x-text-input id="basic_salary" name="basic_salary" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('basic_salary', $profile->basic_salary) }}" required />
                        </div>

                        <div>
                            <x-input-label for="allowances" value="Base Allowances" />
                            <x-text-input id="allowances" name="allowances" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('allowances', $profile->allowances) }}" />
                        </div>

                        <div>
                            <x-input-label for="deductions" value="Base Deductions" />
                            <x-text-input id="deductions" name="deductions" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('deductions', $profile->deductions) }}" />
                        </div>

                        <div>
                            <x-input-label for="bank_name" value="Bank Name" />
                            <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block min-h-11 w-full" value="{{ old('bank_name', $profile->bank_name) }}" />
                        </div>

                        <div>
                            <x-input-label for="account_no" value="Account No" />
                            <x-text-input id="account_no" name="account_no" type="text" class="mt-1 block min-h-11 w-full" value="{{ old('account_no', $profile->account_no) }}" />
                        </div>

                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="active" @selected(old('status', $profile->status) === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $profile->status) === 'inactive')>Inactive</option>
                            </select>
                        </div>

                        <div class="md:col-span-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-gray-900">Allowance Rows</h4>
                                    <button type="button" id="addAllowanceBtn" class="text-xs font-medium text-indigo-700 hover:text-indigo-900">+ Add</button>
                                </div>
                                <div id="allowanceRows" class="space-y-2">
                                    @php
                                        $allowanceRows = old('allowance_items', $profile->allowancesRows->map(fn ($row) => ['title' => $row->title, 'amount' => $row->amount])->all());
                                    @endphp
                                    @foreach($allowanceRows as $index => $row)
                                        <div class="grid grid-cols-12 gap-2" data-row>
                                            <input type="text" name="allowance_items[{{ $index }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Title" class="col-span-7 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <input type="number" min="0" step="0.01" name="allowance_items[{{ $index }}][amount]" value="{{ $row['amount'] ?? '' }}" placeholder="Amount" class="col-span-4 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <button type="button" class="col-span-1 rounded-md border border-red-200 text-xs text-red-600 hover:bg-red-50" data-remove>&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-gray-900">Deduction Rows</h4>
                                    <button type="button" id="addDeductionBtn" class="text-xs font-medium text-indigo-700 hover:text-indigo-900">+ Add</button>
                                </div>
                                <div id="deductionRows" class="space-y-2">
                                    @php
                                        $deductionRows = old('deduction_items', $profile->deductionsRows->map(fn ($row) => ['title' => $row->title, 'amount' => $row->amount])->all());
                                    @endphp
                                    @foreach($deductionRows as $index => $row)
                                        <div class="grid grid-cols-12 gap-2" data-row>
                                            <input type="text" name="deduction_items[{{ $index }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Title" class="col-span-7 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <input type="number" min="0" step="0.01" name="deduction_items[{{ $index }}][amount]" value="{{ $row['amount'] ?? '' }}" placeholder="Amount" class="col-span-4 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            <button type="button" class="col-span-1 rounded-md border border-red-200 text-xs text-red-600 hover:bg-red-50" data-remove>&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-3 flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Update Profile
                            </button>
                            <a href="{{ route('principal.payroll.profiles.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Back
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allowanceRows = document.getElementById('allowanceRows');
        const deductionRows = document.getElementById('deductionRows');
        const addAllowanceBtn = document.getElementById('addAllowanceBtn');
        const addDeductionBtn = document.getElementById('addDeductionBtn');

        let allowanceIndex = allowanceRows ? allowanceRows.querySelectorAll('[data-row]').length : 0;
        let deductionIndex = deductionRows ? deductionRows.querySelectorAll('[data-row]').length : 0;

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

        addAllowanceBtn?.addEventListener('click', () => addRow('allowance'));
        addDeductionBtn?.addEventListener('click', () => addRow('deduction'));
        if (allowanceRows) {
            bindRemove(allowanceRows);
        }
        if (deductionRows) {
            bindRemove(deductionRows);
        }
    </script>
</x-app-layout>
