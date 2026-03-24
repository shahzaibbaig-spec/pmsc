<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Finance · Fee Defaulters</h2>
            <p class="mt-1 text-sm text-slate-500">Auto-marked defaulters based on overdue challans, installments, and manual arrears.</p>
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
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Session</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $filters['session'] }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Defaulters</p>
                <p class="mt-2 text-2xl font-semibold text-rose-700">{{ (int) ($summary['session_active_total'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marked Today</p>
                <p class="mt-2 text-2xl font-semibold text-amber-700">{{ (int) ($summary['session_marked'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visible Due Total</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-700">PKR {{ number_format((float) ($summary['visible_due_total'] ?? 0), 2) }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('principal.fees.defaulters.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <label for="session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select
                        id="session"
                        name="session"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected((string) $filters['session'] === (string) $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="class_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select
                        id="class_id"
                        name="class_id"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Classes</option>
                        @foreach($classes as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>
                                {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="cleared" @selected($filters['status'] === 'cleared')>Cleared</option>
                        <option value="all" @selected($filters['status'] === 'all')>All</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search Student</label>
                    <input
                        id="search"
                        name="search"
                        type="text"
                        value="{{ $filters['search'] }}"
                        placeholder="Name or student ID"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
                <div class="flex items-end gap-2 md:col-span-5">
                    <x-ui.button type="submit">Apply Filters</x-ui.button>
                    <a
                        href="{{ route('principal.fees.defaulters.index') }}"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total Due</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Oldest Due</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($defaulters as $row)
                            @php
                                $student = $row->student;
                                $studentCode = $student?->student_id ?: (string) ($student?->id ?? '-');
                                $className = trim(($student?->classRoom?->name ?? 'Class').' '.($student?->classRoom?->section ?? ''));
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">{{ $student?->name ?? 'Student' }}</div>
                                    <div class="text-xs text-slate-500">{{ $studentCode }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $className !== '' ? $className : '-' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">PKR {{ number_format((float) $row->total_due, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ optional($row->oldest_due_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($row->is_active)
                                        <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Active</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Cleared</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="grid grid-cols-1 gap-2">
                                        <a
                                            href="{{ route('principal.fees.challans.index', ['session' => $row->session, 'search' => $studentCode]) }}"
                                            class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            View Fee Statement
                                        </a>

                                        <form method="POST" action="{{ route('principal.fees.defaulters.send-reminder', $row) }}" class="grid grid-cols-1 gap-2">
                                            @csrf
                                            <input
                                                type="text"
                                                name="message"
                                                placeholder="Reminder note (optional)"
                                                class="block min-h-10 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Send Reminder
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('principal.fees.defaulters.add-note', $row) }}" class="grid grid-cols-1 gap-2">
                                            @csrf
                                            <input
                                                type="text"
                                                name="remarks"
                                                value="{{ $row->remarks }}"
                                                placeholder="Add note"
                                                class="block min-h-10 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Save Note
                                            </button>
                                        </form>

                                        @if($canOverride)
                                            <form method="POST" action="{{ route('principal.fees.defaulters.create-override', $row) }}" class="grid grid-cols-1 gap-2">
                                                @csrf
                                                <select
                                                    name="block_type"
                                                    class="block min-h-10 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                    @foreach($blockTypeOptions as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <input
                                                    type="text"
                                                    name="reason"
                                                    placeholder="Override reason"
                                                    class="block min-h-10 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100">
                                                    Create Override
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('principal.fees.defaulters.waive-late-fee', $row) }}" onsubmit="return confirm('Waive all unpaid late fees for this student in the selected session?')">
                                            @csrf
                                            <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                                                Waive Late Fee
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No defaulters found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $defaulters->links() }}
            </div>
        </section>
    </div>
</x-app-layout>
