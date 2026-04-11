<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Teacher ACR</h2>
                <p class="mt-1 text-sm text-slate-500">Generate draft annual confidential reports from performance data and finalize them after principal review.</p>
            </div>
            <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                Session: {{ $selectedSession }}
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please review the entered information.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.5fr_1fr]">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-slate-100 pb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Filter ACRs</h3>
                        <p class="text-sm text-slate-500">Review reports for a selected academic session and narrow the list by status or teacher search.</p>
                    </div>

                    <form method="GET" action="{{ route('principal.acr.index') }}" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                            <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                @foreach ($sessions as $session)
                                    <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                            <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                <option value="">All statuses</option>
                                <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                                <option value="reviewed" @selected($selectedStatus === 'reviewed')>Reviewed</option>
                                <option value="finalized" @selected($selectedStatus === 'finalized')>Finalized</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                            <input id="search" type="text" name="search" value="{{ $search }}" placeholder="Teacher name, ID, or employee code" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>

                        <div class="md:col-span-4 flex flex-wrap gap-3">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Apply Filters
                            </button>
                            <a href="{{ route('principal.acr.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </section>

                @can('manage_teacher_acr')
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-2 border-b border-slate-100 pb-4">
                            <h3 class="text-lg font-semibold text-slate-900">Generate Drafts</h3>
                            <p class="text-sm text-slate-500">Generate for one teacher or leave the teacher field empty to process the whole session.</p>
                        </div>

                        <form method="POST" action="{{ route('principal.acr.generate') }}" class="mt-5 space-y-4">
                            @csrf
                            <div>
                                <label for="generate-session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                                <select id="generate-session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                    @foreach ($sessions as $session)
                                        <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="teacher_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</label>
                                <select id="teacher_id" name="teacher_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                    <option value="">All teachers in this session</option>
                                    @foreach ($teacherOptions as $teacher)
                                        <option value="{{ $teacher['id'] }}">
                                            {{ $teacher['name'] }} ({{ $teacher['teacher_id'] }}){{ $teacher['employee_code'] ? ' | '.$teacher['employee_code'] : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Generate Draft ACR
                            </button>
                        </form>
                    </section>
                @endcan
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Session ACR Register</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $acrs->total() }} report(s) found for {{ $selectedSession }}.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Session</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total Score</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Final Grade</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($acrs as $acr)
                                @php
                                    $statusClasses = match ($acr->status) {
                                        'reviewed' => 'bg-amber-100 text-amber-800',
                                        'finalized' => 'bg-emerald-100 text-emerald-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        <p class="font-semibold text-slate-900">{{ $acr->teacher?->user?->name ?? 'Teacher' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $acr->teacher?->teacher_id ?? '-' }}
                                            @if ($acr->teacher?->employee_code)
                                                | {{ $acr->teacher->employee_code }}
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $acr->session }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ number_format((float) $acr->total_score, 2) }} / 100</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $acr->final_grade ?: 'Pending review' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ ucfirst($acr->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('principal.acr.show', $acr) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Review
                                            </a>
                                            <a href="{{ route('principal.acr.print', $acr) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Print
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">
                                        No ACRs found for the selected filters. Generate drafts for this session to begin the review cycle.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($acrs->hasPages())
                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $acrs->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
