<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Class Promotion Campaigns</h2>
            <p class="mt-1 text-sm text-slate-500">Review submitted campaigns, approve/reject, and execute final class promotions.</p>
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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('principal.promotions.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="submitted" @selected(($filters['status'] ?? '') === 'submitted')>Submitted</option>
                        <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                        <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Rejected</option>
                        <option value="executed" @selected(($filters['status'] ?? '') === 'executed')>Executed</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                        <option value="all" @selected(($filters['status'] ?? '') === 'all')>All</option>
                    </select>
                </div>

                <div>
                    <label for="session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From Session</label>
                    <select
                        id="session"
                        name="session"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All</option>
                        @foreach($sessionOptions as $session)
                            <option value="{{ $session }}" @selected(($filters['session'] ?? '') === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                    <input
                        id="search"
                        name="search"
                        type="text"
                        value="{{ $filters['search'] ?? '' }}"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Class or teacher"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Apply
                    </button>
                    <a
                        href="{{ route('principal.promotions.index') }}"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Promotion Campaign List</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($campaigns as $campaign)
                            @php
                                $statusColors = match ($campaign->status) {
                                    'submitted' => 'bg-amber-100 text-amber-800',
                                    'approved' => 'bg-indigo-100 text-indigo-800',
                                    'executed' => 'bg-emerald-100 text-emerald-800',
                                    'rejected' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-800">
                                    {{ trim(($campaign->classRoom?->name ?? 'Class').' '.($campaign->classRoom?->section ?? '')) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $campaign->from_session }} -> {{ $campaign->to_session }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $campaign->creator?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusColors }}">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $campaign->submitted_at?->format('d M Y h:i A') ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a
                                        href="{{ route('principal.promotions.show', $campaign) }}"
                                        class="inline-flex min-h-10 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                    >
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No campaigns found for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-3">
                {{ $campaigns->links() }}
            </div>
        </section>
    </div>
</x-app-layout>
