<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Finance · Add Arrears</h2>
            <p class="mt-1 text-sm text-slate-500">Manage manual student arrears and track pending, partial, and paid balances.</p>
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
                <a href="{{ route('principal.fees.reports.arrears') }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    View Arrears Report
                </a>
            </div>

            <form method="GET" action="{{ route('principal.fees.add-arrears.index') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <x-input-label for="session" value="Session" />
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Sessions</option>
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
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                        <option value="partial" @selected($filters['status'] === 'partial')>Partial</option>
                        <option value="paid" @selected($filters['status'] === 'paid')>Paid</option>
                        <option value="all" @selected($filters['status'] === 'all')>All</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="search" value="Search" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 block min-h-11 w-full" value="{{ $filters['search'] }}" placeholder="Student or title" />
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                        Apply
                    </button>
                    <a href="{{ route('principal.fees.add-arrears.index') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-ui.card title="Rows">
                <p class="text-3xl font-semibold text-slate-900">{{ (int) ($summary['rows'] ?? 0) }}</p>
            </x-ui.card>
            <x-ui.card title="Total Amount">
                <p class="text-3xl font-semibold text-slate-900">{{ number_format((float) ($summary['total_amount'] ?? 0), 2) }}</p>
            </x-ui.card>
            <x-ui.card title="Total Paid">
                <p class="text-3xl font-semibold text-emerald-700">{{ number_format((float) ($summary['total_paid'] ?? 0), 2) }}</p>
            </x-ui.card>
            <x-ui.card title="Total Due">
                <p class="text-3xl font-semibold text-rose-700">{{ number_format((float) ($summary['total_due'] ?? 0), 2) }}</p>
            </x-ui.card>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Add Manual Arrear</h3>
            <form method="POST" action="{{ route('principal.fees.add-arrears.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                @csrf

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
                    <x-input-label for="form_session" value="Session (Optional)" />
                    <select id="form_session" name="session" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">No Session</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected(old('session', $filters['session']) === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="title" value="Arrear Title" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block min-h-11 w-full" value="{{ old('title') }}" required />
                </div>

                <div>
                    <x-input-label for="amount" value="Amount" />
                    <x-text-input id="amount" name="amount" type="number" min="0" step="0.01" class="mt-1 block min-h-11 w-full" value="{{ old('amount') }}" required />
                </div>

                <div>
                    <x-input-label for="due_date" value="Due Date (Optional)" />
                    <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block min-h-11 w-full" value="{{ old('due_date') }}" />
                </div>

                <div class="xl:col-span-2">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                </div>

                <div class="xl:col-span-4 flex justify-end">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Add Arrear
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[1100px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Due Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Paid</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Remaining</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($arrears as $arrear)
                            @php
                                $remaining = round(max((float) $arrear->amount - (float) $arrear->paid_amount, 0), 2);
                                $status = strtolower((string) $arrear->status);
                                $statusClass = match ($status) {
                                    'paid' => 'bg-emerald-100 text-emerald-700',
                                    'partial' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                $className = trim(($arrear->student?->classRoom?->name ?? 'Class').' '.($arrear->student?->classRoom?->section ?? ''));
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-800">
                                    <p class="font-medium">{{ $arrear->student?->name ?? 'Student' }}</p>
                                    <p class="text-xs text-slate-500">{{ $arrear->student?->student_id ?? '-' }} · {{ $className }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <p class="font-medium">{{ $arrear->title }}</p>
                                    @if($arrear->notes)
                                        <p class="mt-1 text-xs text-slate-500">{{ $arrear->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $arrear->session ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ optional($arrear->due_date)->toDateString() ?: '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-700">{{ number_format((float) $arrear->amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-700">{{ number_format((float) $arrear->paid_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ number_format($remaining, 2) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $statusClass }}">{{ ucfirst($status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($remaining > 0)
                                        <form method="POST" action="{{ route('principal.fees.add-arrears.pay', $arrear) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="number" name="amount_paid" min="0.01" step="0.01" max="{{ $remaining }}" class="block min-h-9 w-24 rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ number_format($remaining, 2, '.', '') }}" required>
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
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">No arrears found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($arrears->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">
                    {{ $arrears->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>

