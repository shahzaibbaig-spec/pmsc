<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Class Promotions</h2>
            <p class="mt-1 text-sm text-slate-500">Create class promotion campaign and submit recommendations for principal approval.</p>
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
            <h3 class="text-base font-semibold text-slate-900">Create / Open Promotion Campaign</h3>
            <p class="mt-1 text-xs text-slate-500">Only your assigned class teacher classes are available for selected session.</p>

            <form method="POST" action="{{ route('teacher.promotions.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                @csrf
                <div>
                    <label for="from_session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From Session</label>
                    <select
                        id="from_session"
                        name="from_session"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($sessions as $session)
                            <option value="{{ $session }}" @selected(old('from_session', $selectedFromSession) === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="to_session" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To Session</label>
                    <input
                        id="to_session"
                        name="to_session"
                        type="text"
                        value="{{ old('to_session', $selectedToSession) }}"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="2026-2027"
                        required
                    >
                </div>

                <div>
                    <label for="class_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select
                        id="class_id"
                        name="class_id"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        @if($classAssignments->isEmpty())
                            <option value="">No class teacher assignment found</option>
                        @endif
                        @foreach($classAssignments as $assignment)
                            <option value="{{ $assignment->class_id }}" @selected((string) old('class_id') === (string) $assignment->class_id)>
                                {{ trim(($assignment->classRoom?->name ?? 'Class').' '.($assignment->classRoom?->section ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Open Campaign
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">My Promotion Campaigns</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</th>
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
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusColors }}">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $campaign->updated_at?->format('d M Y h:i A') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a
                                        href="{{ route('teacher.promotions.show', $campaign) }}"
                                        class="inline-flex min-h-10 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                    >
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No promotion campaign found for selected session.</td>
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
