<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Promotion Campaign Detail</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ trim(($campaign->classRoom?->name ?? 'Class').' '.($campaign->classRoom?->section ?? '')) }}
                    | {{ $campaign->from_session }} → {{ $campaign->to_session }}
                </p>
            </div>
            <a
                href="{{ route('teacher.promotions.index', ['from_session' => $campaign->from_session, 'to_session' => $campaign->to_session]) }}"
                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to Campaigns
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

        @if (!$nextClassLabel)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Next class mapping is not configured for this class. Promote/Conditional decisions will require class mapping before submit.
            </div>
        @endif

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ ucfirst($campaign->status) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Students</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary['total_students'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Passed</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ (int) ($summary['passed_students'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Promote</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-800">{{ (int) ($summary['promoted'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Retain</p>
                <p class="mt-2 text-2xl font-semibold text-rose-800">{{ (int) ($summary['retained'] ?? 0) }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Student Decisions</h3>
                    <p class="mt-1 text-xs text-slate-500">Conditional promote and retain decisions must include a note.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($isEditable)
                        <button
                            id="bulkPromotePassingBtn"
                            type="button"
                            class="inline-flex min-h-10 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                        >
                            Auto Promote Passed
                        </button>
                    @endif
                    @if ($nextClassLabel)
                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Next Class: {{ $nextClassLabel }}
                        </span>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('teacher.promotions.update', $campaign) }}">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Final %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Grade</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pass/Fail</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher Decision</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher Note</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Principal Decision</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Final Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($rows as $index => $row)
                                @php
                                    $isPassed = (bool) $row->is_passed;
                                    $decisionValue = old("rows.$index.teacher_decision", $row->teacher_decision ?: ($isPassed ? 'promote' : ''));
                                    $noteValue = old("rows.$index.teacher_note", (string) ($row->teacher_note ?? ''));
                                @endphp
                                <tr data-is-passed="{{ $isPassed ? 1 : 0 }}">
                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $row->id }}">
                                        <div class="font-semibold">{{ $row->student?->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $row->student?->student_id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        @if($row->final_percentage !== null)
                                            {{ number_format((float) $row->final_percentage, 2) }}%
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $row->final_grade ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $isPassed ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $isPassed ? 'Pass' : 'Fail' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <select
                                            name="rows[{{ $index }}][teacher_decision]"
                                            class="teacher-decision block min-h-10 w-44 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            @disabled(! $isEditable)
                                        >
                                            <option value="">Select</option>
                                            <option value="promote" @selected($decisionValue === 'promote')>Promote</option>
                                            <option value="conditional_promote" @selected($decisionValue === 'conditional_promote')>Conditional Promote</option>
                                            <option value="retain" @selected($decisionValue === 'retain')>Retain</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <textarea
                                            name="rows[{{ $index }}][teacher_note]"
                                            rows="2"
                                            class="block w-64 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Required for conditional/retain"
                                            @disabled(! $isEditable)
                                        >{{ $noteValue }}</textarea>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        @if($row->principal_decision)
                                            <span class="font-semibold">{{ str_replace('_', ' ', ucfirst($row->principal_decision)) }}</span>
                                            @if($row->principal_note)
                                                <div class="mt-1 text-[11px] text-slate-500">{{ $row->principal_note }}</div>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                                        {{ ucfirst($row->final_status) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No eligible student found for this campaign.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($isEditable)
                    <div class="border-t border-slate-200 px-5 py-4">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Save Decisions
                        </button>
                    </div>
                @endif
            </form>
        </section>

        @if ($isEditable)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Submit To Principal</h3>
                <p class="mt-1 text-xs text-slate-500">Principal approval is required before class promotion execution.</p>
                <form method="POST" action="{{ route('teacher.promotions.submit', $campaign) }}" class="mt-4">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex min-h-11 items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                        onclick="return confirm('Submit this campaign to principal for approval?')"
                    >
                        Submit Campaign
                    </button>
                </form>
            </section>
        @endif
    </div>

    @if ($isEditable)
        <script>
            (() => {
                const bulkBtn = document.getElementById('bulkPromotePassingBtn');
                if (!bulkBtn) {
                    return;
                }

                bulkBtn.addEventListener('click', () => {
                    const rows = document.querySelectorAll('tr[data-is-passed="1"]');
                    rows.forEach((row) => {
                        const decisionInput = row.querySelector('.teacher-decision');
                        if (decisionInput) {
                            decisionInput.value = 'promote';
                        }
                    });
                });
            })();
        </script>
    @endif
</x-app-layout>

