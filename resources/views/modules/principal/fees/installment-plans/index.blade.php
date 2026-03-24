<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Finance · Installment Plans</h2>
            <p class="mt-1 text-sm text-slate-500">Create student-wise installment plans, auto-generate schedules, and record partial payments.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc ps-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900">Filters</h3>
                <a href="{{ route('principal.fees.challans.index') }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Go To Challans
                </a>
            </div>

            <form method="GET" action="{{ route('principal.fees.installment-plans.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <x-input-label for="session" value="Session" />
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected($filters['session'] === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="class_id" value="Class" />
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Classes</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="search" value="Student" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['search'] }}" placeholder="Name or student ID" />
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                        Apply
                    </button>
                    <a href="{{ route('principal.fees.installment-plans.index') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Create Installment Plan</h3>
            <p class="mt-1 text-xs text-slate-500">Installments are generated automatically from total amount and installment count.</p>

            <form method="POST" action="{{ route('principal.fees.installment-plans.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4" id="installment-plan-form">
                @csrf
                <input type="hidden" name="session" value="{{ $selectedSession }}">

                <div class="xl:col-span-2">
                    <x-input-label for="student_id" value="Student" />
                    <select id="student_id" name="student_id" required class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" @selected((string) old('student_id') === (string) $student->id)>
                                {{ $student->name }} ({{ $student->student_id }}) · {{ trim(($student->classRoom?->name ?? 'Class').' '.($student->classRoom?->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="plan_name" value="Plan Name" />
                    <x-text-input id="plan_name" name="plan_name" type="text" class="mt-1 block min-h-11 w-full" value="{{ old('plan_name') }}" placeholder="Optional label" />
                </div>

                <div>
                    <x-input-label for="plan_type" value="Plan Type" />
                    <select id="plan_type" name="plan_type" required class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($planTypes as $type)
                            <option value="{{ $type }}" @selected(old('plan_type', 'monthly') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="total_amount" value="Total Amount" />
                    <x-text-input id="total_amount" name="total_amount" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('total_amount') }}" required />
                </div>

                <div>
                    <x-input-label for="number_of_installments" value="No. of Installments" />
                    <x-text-input id="number_of_installments" name="number_of_installments" type="number" min="1" step="1" class="mt-1 block min-h-11 w-full" value="{{ old('number_of_installments', 10) }}" required />
                </div>

                <div>
                    <x-input-label for="first_due_date" value="First Due Date" />
                    <x-text-input id="first_due_date" name="first_due_date" type="date" class="mt-1 block min-h-11 w-full" value="{{ old('first_due_date', now()->toDateString()) }}" required />
                </div>

                <div>
                    <x-input-label for="custom_interval_days" value="Custom Interval (Days)" />
                    <x-text-input id="custom_interval_days" name="custom_interval_days" type="number" min="1" step="1" class="mt-1 block min-h-11 w-full" value="{{ old('custom_interval_days', 30) }}" />
                </div>

                <div class="xl:col-span-4">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                </div>

                <div class="xl:col-span-4 flex items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="deactivate_existing" value="1" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('deactivate_existing', '1') === '1')>
                        Deactivate existing active plans for this student/session
                    </label>
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Create Plan
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Installment Schedules</h3>
                <p class="mt-1 text-xs text-slate-500">Each plan includes a per-student installment schedule and payment controls.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($plans as $plan)
                    @php
                        $installmentTotal = round((float) $plan->installments->sum('amount'), 2);
                        $paidTotal = round((float) $plan->installments->sum('paid_amount'), 2);
                        $dueTotal = round(max($installmentTotal - $paidTotal, 0), 2);
                        $studentClass = trim(($plan->student?->classRoom?->name ?? 'Class').' '.($plan->student?->classRoom?->section ?? ''));
                    @endphp

                    <details class="group">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $plan->student?->name ?? 'Student' }}
                                    <span class="text-xs font-normal text-slate-500">({{ $plan->student?->student_id ?? '-' }})</span>
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $studentClass }} · {{ $plan->session }} · {{ ucfirst((string) $plan->plan_type) }}
                                    @if($plan->plan_name)
                                        · {{ $plan->plan_name }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full bg-slate-100 px-2 py-1 font-medium text-slate-700">Total {{ number_format($installmentTotal, 2) }}</span>
                                <span class="rounded-full bg-emerald-100 px-2 py-1 font-medium text-emerald-700">Paid {{ number_format($paidTotal, 2) }}</span>
                                <span class="rounded-full bg-rose-100 px-2 py-1 font-medium text-rose-700">Due {{ number_format($dueTotal, 2) }}</span>
                                <span class="rounded-full px-2 py-1 font-medium {{ $plan->is_active ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </summary>

                        <div class="overflow-x-auto border-t border-slate-100 px-5 py-4">
                            <table class="min-w-[760px] divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Title</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Due Date</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Paid</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Remaining</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse($plan->installments as $installment)
                                        @php
                                            $remaining = round(max((float) $installment->amount - (float) $installment->paid_amount, 0), 2);
                                            $status = strtolower((string) $installment->status);
                                            $statusClass = match ($status) {
                                                'paid' => 'bg-emerald-100 text-emerald-700',
                                                'partial' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-slate-100 text-slate-700',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-slate-700">{{ $installment->installment_no }}</td>
                                            <td class="px-3 py-2 text-sm text-slate-700">{{ $installment->title ?: 'Installment '.$installment->installment_no }}</td>
                                            <td class="px-3 py-2 text-sm text-slate-700">{{ optional($installment->due_date)->toDateString() }}</td>
                                            <td class="px-3 py-2 text-right text-sm text-slate-700">{{ number_format((float) $installment->amount, 2) }}</td>
                                            <td class="px-3 py-2 text-right text-sm text-slate-700">{{ number_format((float) $installment->paid_amount, 2) }}</td>
                                            <td class="px-3 py-2 text-right text-sm font-semibold text-slate-900">{{ number_format($remaining, 2) }}</td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $statusClass }}">{{ ucfirst($status) }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                @if($remaining > 0)
                                                    <form method="POST" action="{{ route('principal.fees.installment-plans.installments.pay', $installment) }}" class="flex items-center gap-2">
                                                        @csrf
                                                        <input type="number" name="amount_paid" min="0.01" step="0.01" max="{{ $remaining }}" class="block min-h-9 w-28 rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ number_format($remaining, 2, '.', '') }}" required>
                                                        <button type="submit" class="inline-flex min-h-9 items-center rounded-md border border-indigo-300 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                                            Pay
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-slate-400">Settled</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-500">No installments found in this plan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </details>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-500">No installment plans found for selected filters.</div>
                @endforelse
            </div>

            @if($plans->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">
                    {{ $plans->links() }}
                </div>
            @endif
        </section>
    </div>

    <script>
        (() => {
            const planType = document.getElementById('plan_type');
            const customInterval = document.getElementById('custom_interval_days');

            if (!planType || !customInterval) {
                return;
            }

            const syncCustomState = () => {
                const isCustom = planType.value === 'custom';
                customInterval.required = isCustom;
                customInterval.closest('div')?.classList.toggle('opacity-60', !isCustom);
            };

            syncCustomState();
            planType.addEventListener('change', syncCustomState);
        })();
    </script>
</x-app-layout>

