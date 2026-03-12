<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Record Payments
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

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Filters</h3>
                    <form method="GET" action="{{ route('principal.fees.payments.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-6">
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sessions</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="class_id" value="Class" />
                            <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Classes</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                        {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="month" value="Month" />
                            <x-text-input id="month" name="month" type="month" class="mt-1 block min-h-11 w-full" value="{{ $filters['month'] }}" />
                        </div>
                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                                <option value="paid" @selected($filters['status'] === 'paid')>Paid</option>
                                <option value="all" @selected($filters['status'] === 'all')>All</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="student_name" value="Student Name" />
                            <x-text-input id="student_name" name="student_name" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['student_name'] }}" />
                        </div>
                        <div>
                            <x-input-label for="challan_number" value="Challan Number" />
                            <x-text-input id="challan_number" name="challan_number" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['challan_number'] }}" />
                        </div>
                        <div class="md:col-span-6 flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                                Apply
                            </button>
                            <a href="{{ route('principal.fees.payments.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[1460px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Challan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Session/Month</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Totals</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Payment Entry</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($challans as $challan)
                                @php
                                    $statusClass = match ($challan->status) {
                                        'paid' => 'bg-emerald-100 text-emerald-700',
                                        'partially_paid' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-rose-100 text-rose-700',
                                    };
                                    $remainingAmount = (float) $challan->remaining_amount;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        <div class="font-medium">{{ $challan->challan_number }}</div>
                                        <div class="text-xs text-gray-500">Due: {{ optional($challan->due_date)->format('Y-m-d') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        <div class="font-medium">{{ $challan->student?->name ?? 'Student' }}</div>
                                        <div class="text-xs text-gray-500">{{ $challan->student?->student_id ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ trim(($challan->classRoom?->name ?? 'Class').' '.($challan->classRoom?->section ?? '')) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        <div>{{ $challan->session }}</div>
                                        <div class="text-xs text-gray-500">{{ $challan->month_label }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        <div>Total: {{ number_format((float) $challan->total_amount, 2) }}</div>
                                        <div>Paid: {{ number_format((float) $challan->paid_amount, 2) }}</div>
                                        <div class="font-medium">Remaining: {{ number_format($remainingAmount, 2) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $statusClass }}">
                                            {{ ucwords(str_replace('_', ' ', $challan->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        @if ($remainingAmount <= 0)
                                            <span class="text-xs font-medium text-emerald-700">Fully paid</span>
                                        @else
                                            <form method="POST" action="{{ route('principal.fees.payments.store') }}" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                @csrf
                                                <input type="hidden" name="challan_id" value="{{ $challan->id }}">

                                                <div>
                                                    <x-input-label value="Amount" />
                                                    <x-text-input name="amount_paid" type="number" min="0.01" max="{{ $remainingAmount }}" step="0.01" class="mt-1 block min-h-10 w-full" value="{{ number_format($remainingAmount, 2, '.', '') }}" required />
                                                </div>

                                                <div>
                                                    <x-input-label value="Date" />
                                                    <x-text-input name="payment_date" type="date" class="mt-1 block min-h-10 w-full" value="{{ $defaultPaymentDate }}" required />
                                                </div>

                                                <div>
                                                    <x-input-label value="Method" />
                                                    <select name="payment_method" class="mt-1 block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="">Select</option>
                                                        <option value="cash">Cash</option>
                                                        <option value="bank_transfer">Bank Transfer</option>
                                                        <option value="cheque">Cheque</option>
                                                        <option value="mobile_wallet">Mobile Wallet</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <x-input-label value="Reference No" />
                                                    <x-text-input name="reference_no" type="text" class="mt-1 block min-h-10 w-full" />
                                                </div>

                                                <div class="sm:col-span-2">
                                                    <x-input-label value="Notes" />
                                                    <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                                </div>

                                                <div class="sm:col-span-2">
                                                    <button type="submit" class="inline-flex min-h-10 items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700">
                                                        Save Payment
                                                    </button>
                                                </div>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No challans found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4">
                    {{ $challans->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
