<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Admit Card Overrides</h2>
                <p class="mt-1 text-sm text-slate-500">Allow fee-defaulter students for selected exam session when approved.</p>
            </div>
            <a
                href="{{ route('principal.admit-cards.index', ['exam_session_id' => $filters['exam_session_id']]) }}"
                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back To Admit Cards
            </a>
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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('principal.admit-cards.overrides.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="exam_session_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</label>
                    <select
                        id="exam_session_id"
                        name="exam_session_id"
                        required
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Select exam session</option>
                        @foreach($examSessions as $examSession)
                            <option value="{{ $examSession->id }}" @selected((string) $filters['exam_session_id'] === (string) $examSession->id)>
                                {{ $examSession->name }} ({{ $examSession->session }})
                            </option>
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
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit">Apply</x-ui.button>
                    <a
                        href="{{ route('principal.admit-cards.overrides.index') }}"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3 text-sm text-slate-600">
                @if($selectedExamSession)
                    Selected: <span class="font-semibold text-slate-900">{{ $selectedExamSession->name }}</span>
                    ({{ $selectedExamSession->session }}) |
                    {{ optional($selectedExamSession->start_date)->format('d M Y') }} - {{ optional($selectedExamSession->end_date)->format('d M Y') }}
                @else
                    Select an exam session to manage overrides.
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Due</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Oldest Due</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Override Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($rows as $row)
                            @php
                                $student = $row->student;
                                $override = $overrideMap->get((int) $row->student_id);
                                $studentCode = $student?->student_id ?: (string) ($student?->id ?? '-');
                                $className = trim(($student?->classRoom?->name ?? 'Class').' '.($student?->classRoom?->section ?? ''));
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">{{ $student?->name ?? 'Student' }}</div>
                                    <div class="text-xs text-slate-500">{{ $studentCode }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $className !== '' ? $className : '-' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-rose-700">PKR {{ number_format((float) $row->total_due, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ optional($row->oldest_due_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if($override && $override->is_allowed)
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Allowed</span>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $override->reason ?: 'No reason provided' }}
                                            @if($override->approver?->name)
                                                | by {{ $override->approver->name }}
                                            @endif
                                        </div>
                                    @elseif($override && ! $override->is_allowed)
                                        <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Blocked</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">No Override</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if($selectedExamSession)
                                        <form method="POST" action="{{ route('principal.admit-cards.overrides.store', ['search' => $filters['search'], 'class_id' => $filters['class_id']]) }}" class="grid grid-cols-1 gap-2">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ (int) $row->student_id }}">
                                            <input type="hidden" name="exam_session_id" value="{{ (int) $selectedExamSession->id }}">
                                            <select
                                                name="is_allowed"
                                                class="block min-h-10 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="1" @selected(! $override || $override->is_allowed)>Allow Admit Card</option>
                                                <option value="0" @selected($override && ! $override->is_allowed)>Block Admit Card</option>
                                            </select>
                                            <input
                                                type="text"
                                                name="reason"
                                                value="{{ $override?->reason }}"
                                                placeholder="Reason (optional)"
                                                class="block min-h-10 w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Save Override
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No active fee defaulters found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $rows->links() }}
            </div>
        </section>
    </div>
</x-app-layout>
