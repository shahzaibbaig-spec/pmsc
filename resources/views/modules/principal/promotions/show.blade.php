<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Promotion Campaign Review</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ trim(($campaign->classRoom?->name ?? 'Class').' '.($campaign->classRoom?->section ?? '')) }}
                    | {{ $campaign->from_session }} -> {{ $campaign->to_session }}
                </p>
            </div>
            <a
                href="{{ route('principal.promotions.index', request()->query()) }}"
                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to List
            </a>
        </div>
    </x-slot>

    @php
        $canReview = in_array($campaign->status, ['draft', 'submitted', 'approved'], true);
        $canApprove = in_array($campaign->status, ['draft', 'submitted'], true);
        $canReject = in_array($campaign->status, ['submitted', 'approved'], true);
        $canExecute = $campaign->status === 'approved';
    @endphp

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

        @if ($isTerminalClass)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                This is a terminal class. Use Pass Out for passed students, and conditional promotion is not allowed.
            </div>
        @endif

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Students</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary['total_students'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ $isTerminalClass ? 'Passed Out' : 'Promote' }}</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ (int) ($isTerminalClass ? ($summary['passed_out'] ?? 0) : ($summary['promoted'] ?? 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Conditional</p>
                <p class="mt-2 text-2xl font-semibold text-amber-800">{{ (int) ($summary['conditional_promoted'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Retain</p>
                <p class="mt-2 text-2xl font-semibold text-rose-800">{{ (int) ($summary['retained'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Pending</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-800">{{ (int) ($summary['pending'] ?? 0) }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Group Actions</h3>
            <p class="mt-1 text-xs text-slate-500">Select students below, then apply a group action.</p>

            <form
                id="groupActionForm"
                method="POST"
                action="{{ route('principal.promotions.group-action', $campaign) }}"
                class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-5"
            >
                @csrf
                <div class="md:col-span-2">
                    <label for="group_decision" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Decision</label>
                    <select
                        id="group_decision"
                        name="decision"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @disabled(! $canReview)
                    >
                        <option value="promote">{{ $isTerminalClass ? 'Pass Out Selected Students' : 'Promote Selected Students' }}</option>
                        @unless ($isTerminalClass)
                            <option value="conditional_promote">Conditionally Promote Selected Students</option>
                        @endunless
                        <option value="retain">Retain Selected Students</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="group_note" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Note</label>
                    <input
                        id="group_note"
                        name="note"
                        type="text"
                        maxlength="1000"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="{{ $isTerminalClass ? 'Required for retain decision' : 'Required for retain / conditional' }}"
                        @disabled(! $canReview)
                    >
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @disabled(! $canReview)
                    >
                        Apply Group Action
                    </button>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
                <h3 class="text-sm font-semibold text-slate-900">Campaign Actions</h3>
                <p class="mt-1 text-xs text-slate-500">Approve before execution. Execution updates current class and class history.</p>

                @if ($canApprove)
                    <form method="POST" action="{{ route('principal.promotions.approve', $campaign) }}" class="mt-4 space-y-2">
                        @csrf
                        <label for="approve_principal_note" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Approval Note (Optional)</label>
                        <textarea
                            id="approve_principal_note"
                            name="principal_note"
                            rows="2"
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Approved by principal"
                        >{{ old('principal_note') }}</textarea>
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Approve Campaign
                        </button>
                    </form>
                @endif

                @if ($canReject)
                    <form method="POST" action="{{ route('principal.promotions.reject', $campaign) }}" class="mt-4 space-y-2">
                        @csrf
                        <label for="reject_principal_note" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rejection Note</label>
                        <textarea
                            id="reject_principal_note"
                            name="principal_note"
                            rows="2"
                            required
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Reason for rejection"
                        >{{ old('principal_note') }}</textarea>
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                            Reject Campaign
                        </button>
                    </form>
                @endif

                @if ($canExecute)
                    <form method="POST" action="{{ route('principal.promotions.execute', $campaign) }}" class="mt-4">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                            onclick="return confirm('Execute this campaign now? This will update student class and history records for the target session.')"
                        >
                            Execute Campaign
                        </button>
                    </form>
                @endif
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h3 class="text-sm font-semibold text-slate-900">Campaign Timeline</h3>
                <div class="mt-4 space-y-2 text-sm text-slate-700">
                    <p><span class="font-semibold">Status:</span> {{ ucfirst($campaign->status) }}</p>
                    <p><span class="font-semibold">Created By:</span> {{ $campaign->creator?->name ?? '-' }}</p>
                    <p><span class="font-semibold">Approved By:</span> {{ $campaign->approver?->name ?? '-' }}</p>
                    <p><span class="font-semibold">Submitted:</span> {{ $campaign->submitted_at?->format('d M Y h:i A') ?: '-' }}</p>
                    <p><span class="font-semibold">Approved:</span> {{ $campaign->approved_at?->format('d M Y h:i A') ?: '-' }}</p>
                    <p><span class="font-semibold">Executed:</span> {{ $campaign->executed_at?->format('d M Y h:i A') ?: '-' }}</p>
                    <p><span class="font-semibold">Principal Note:</span> {{ $campaign->principal_note ?: '-' }}</p>
                    <p><span class="font-semibold">Next Class:</span> {{ $nextClassLabel ?: 'Not Mapped' }}</p>
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Student Promotion Review</h3>
                    <p class="mt-1 text-xs text-slate-500">Use checkboxes for group action. You can still change individual final decisions below.</p>
                </div>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                    <input id="selectAllStudents" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    Select All
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Select</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Current Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Suggested Next Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Final %</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Final Grade</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pass/Fail</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Current Decision</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Current Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($rows as $row)
                            @php
                                $effectiveDecision = $row->principal_decision ?? $row->teacher_decision;
                                $effectiveNote = $row->principal_note ?? $row->teacher_note;
                                $effectiveDecisionLabel = $effectiveDecision
                                    ? (($isTerminalClass && (bool) $row->is_passed && $effectiveDecision === 'promote')
                                        ? 'Pass Out'
                                        : str_replace('_', ' ', ucfirst($effectiveDecision)))
                                    : 'Pending';
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="student-check rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        value="{{ $row->student_id }}"
                                        @disabled(! $canReview)
                                    >
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-800">
                                    <div class="font-semibold">{{ $row->student?->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $row->student?->student_id }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ trim(($row->fromClass?->name ?? '').' '.($row->fromClass?->section ?? '')) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if ($row->toClass)
                                        {{ trim(($row->toClass?->name ?? '').' '.($row->toClass?->section ?? '')) }}
                                    @elseif ($nextClassLabel)
                                        {{ $nextClassLabel }}
                                    @else
                                        Not Mapped
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $row->final_percentage !== null ? number_format((float) $row->final_percentage, 2).'%' : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row->final_grade ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_passed ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $row->is_passed ? 'Pass' : 'Fail' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                                    {{ $effectiveDecisionLabel }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $effectiveNote ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">No eligible students found for this campaign.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Edit Individual Decisions</h3>
                <p class="mt-1 text-xs text-slate-500">This allows fine-tuning before final approval and execution.</p>
            </div>

            <form method="POST" action="{{ route('principal.promotions.review', $campaign) }}">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Principal Decision</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Principal Note</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Final Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($rows as $index => $row)
                                @php
                                    $principalDecision = old("rows.$index.principal_decision", $row->principal_decision);
                                    $principalNote = old("rows.$index.principal_note", $row->principal_note);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $row->id }}">
                                        <div class="font-semibold">{{ $row->student?->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $row->student?->student_id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <select
                                            name="rows[{{ $index }}][principal_decision]"
                                            class="block min-h-10 w-52 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            @disabled(! $canReview)
                                        >
                                            <option value="">Use Current Decision</option>
                                            <option value="promote" @selected($principalDecision === 'promote')>{{ $isTerminalClass ? 'Pass Out' : 'Promote' }}</option>
                                            @unless ($isTerminalClass)
                                                <option value="conditional_promote" @selected($principalDecision === 'conditional_promote')>Conditional Promote</option>
                                            @endunless
                                            <option value="retain" @selected($principalDecision === 'retain')>Retain</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <textarea
                                            name="rows[{{ $index }}][principal_note]"
                                            rows="2"
                                            class="block w-64 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="{{ $isTerminalClass ? 'Required for retain decision' : 'Required for conditional/retain' }}"
                                            @disabled(! $canReview)
                                        >{{ $principalNote }}</textarea>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-700">{{ ucfirst($row->final_status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No rows available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($canReview)
                    <div class="border-t border-slate-200 px-5 py-4">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Save Individual Decisions
                        </button>
                    </div>
                @endif
            </form>
        </section>
    </div>

    <script>
        (() => {
            const selectAll = document.getElementById('selectAllStudents');
            const rowChecks = Array.from(document.querySelectorAll('.student-check'));
            const groupForm = document.getElementById('groupActionForm');

            if (!groupForm || rowChecks.length === 0) {
                return;
            }

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    rowChecks.forEach((checkbox) => {
                        if (!checkbox.disabled) {
                            checkbox.checked = selectAll.checked;
                        }
                    });
                });
            }

            groupForm.addEventListener('submit', (event) => {
                groupForm.querySelectorAll('input[name="student_ids[]"]').forEach((node) => node.remove());

                const selected = rowChecks
                    .filter((checkbox) => checkbox.checked && !checkbox.disabled)
                    .map((checkbox) => checkbox.value);

                if (selected.length === 0) {
                    event.preventDefault();
                    alert('Select at least one student to apply group action.');
                    return;
                }

                selected.forEach((studentId) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'student_ids[]';
                    input.value = studentId;
                    groupForm.appendChild(input);
                });
            });
        })();
    </script>
</x-app-layout>
