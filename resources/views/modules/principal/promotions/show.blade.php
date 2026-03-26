<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Review Promotion Campaign</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ trim(($campaign->classRoom?->name ?? 'Class').' '.($campaign->classRoom?->section ?? '')) }}
                    | {{ $campaign->from_session }} → {{ $campaign->to_session }}
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
        $canReview = $campaign->status === 'submitted';
        $canApprove = $campaign->status === 'submitted';
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

        @if (!$nextClassLabel)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Promotion mapping is not configured for this class. Approve/Execute will fail for promote decisions until mapping is added.
            </div>
        @endif

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ ucfirst($campaign->status) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $campaign->creator?->name ?? '-' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Students</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary['total_students'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Promote</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ (int) ($summary['promoted'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Conditional</p>
                <p class="mt-2 text-2xl font-semibold text-amber-800">{{ (int) ($summary['conditional_promoted'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Retain</p>
                <p class="mt-2 text-2xl font-semibold text-rose-800">{{ (int) ($summary['retained'] ?? 0) }}</p>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
                <h3 class="text-sm font-semibold text-slate-900">Campaign Actions</h3>
                <p class="mt-1 text-xs text-slate-500">Principal approval is mandatory before execution.</p>

                @if ($canApprove)
                    <form method="POST" action="{{ route('principal.promotions.approve', $campaign) }}" class="mt-4 space-y-2">
                        @csrf
                        <label for="approve_principal_note" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Approval Note (Optional)</label>
                        <textarea
                            id="approve_principal_note"
                            name="principal_note"
                            rows="2"
                            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Approved after review"
                        >{{ old('principal_note') }}</textarea>
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Approve Campaign
                        </button>
                    </form>
                @endif

                @if ($canReject)
                    <form method="POST" action="{{ route('principal.promotions.reject', $campaign) }}" class="mt-4 space-y-2">
                        @csrf
                        <label for="reject_principal_note" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rejection Note (Required)</label>
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
                            onclick="return confirm('Execute this campaign now? This will update student class and history records.')"
                        >
                            Execute Campaign
                        </button>
                    </form>
                @endif

                @if (!$canApprove && !$canReject && !$canExecute)
                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        No action is available for current campaign status.
                    </div>
                @endif
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h3 class="text-sm font-semibold text-slate-900">Campaign Timeline</h3>
                <div class="mt-4 space-y-2 text-sm text-slate-700">
                    <p><span class="font-semibold">Submitted:</span> {{ $campaign->submitted_at?->format('d M Y h:i A') ?: '-' }}</p>
                    <p><span class="font-semibold">Approved:</span> {{ $campaign->approved_at?->format('d M Y h:i A') ?: '-' }}</p>
                    <p><span class="font-semibold">Executed:</span> {{ $campaign->executed_at?->format('d M Y h:i A') ?: '-' }}</p>
                    <p><span class="font-semibold">Principal Note:</span> {{ $campaign->principal_note ?: '-' }}</p>
                    <p><span class="font-semibold">Next Class Mapping:</span> {{ $nextClassLabel ?: 'Not configured' }}</p>
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Student Promotion Review</h3>
                <p class="mt-1 text-xs text-slate-500">Principal can override teacher decisions before campaign approval.</p>
            </div>

            <form method="POST" action="{{ route('principal.promotions.review', $campaign) }}">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Final %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher Decision</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher Note</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Principal Decision</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Principal Note</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Effective Decision</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($rows as $index => $row)
                                @php
                                    $principalDecision = old("rows.$index.principal_decision", $row->principal_decision);
                                    $principalNote = old("rows.$index.principal_note", $row->principal_note);
                                    $effectiveDecision = $row->principal_decision ?? $row->teacher_decision;
                                @endphp
                                <tr>
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
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $row->teacher_decision ? str_replace('_', ' ', ucfirst($row->teacher_decision)) : '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ $row->teacher_note ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <select
                                            name="rows[{{ $index }}][principal_decision]"
                                            class="block min-h-10 w-44 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            @disabled(! $canReview)
                                        >
                                            <option value="">Use Teacher Decision</option>
                                            <option value="promote" @selected($principalDecision === 'promote')>Promote</option>
                                            <option value="conditional_promote" @selected($principalDecision === 'conditional_promote')>Conditional Promote</option>
                                            <option value="retain" @selected($principalDecision === 'retain')>Retain</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <textarea
                                            name="rows[{{ $index }}][principal_note]"
                                            rows="2"
                                            class="block w-64 rounded-lg border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Required for conditional/retain"
                                            @disabled(! $canReview)
                                        >{{ $principalNote }}</textarea>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                                        {{ $effectiveDecision ? str_replace('_', ' ', ucfirst($effectiveDecision)) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                                        {{ ucfirst($row->final_status) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No student rows found for this campaign.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($canReview)
                    <div class="border-t border-slate-200 px-5 py-4">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Save Principal Review
                        </button>
                    </div>
                @endif
            </form>
        </section>
    </div>
</x-app-layout>

