<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Inventory Demand #{{ $demand->id }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Teacher: {{ $demand->teacher?->user?->name ?? '-' }} | Requested on {{ $demand->request_date?->format('M d, Y') }}
                </p>
            </div>
            <a
                href="{{ route('inventory.demands.index') }}"
                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                    <h3 class="text-base font-semibold text-slate-900">Line-by-Line Review</h3>
                    <form method="POST" action="{{ route('inventory.demands.review', $demand) }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="overflow-hidden rounded-md border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Item</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Requested</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Line Status</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Approved Qty</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($demand->lines as $index => $line)
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-slate-800">
                                                {{ $line->item?->name ?: $line->requested_item_name }}
                                                @if ($line->item && $line->item->unit)
                                                    <span class="text-xs text-slate-500">(Stock: {{ $line->item->current_stock }} {{ $line->item->unit }})</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-sm text-slate-700">{{ $line->requested_quantity }}</td>
                                            <td class="px-3 py-2">
                                                <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                                <select
                                                    name="lines[{{ $index }}][line_status]"
                                                    class="block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                    @disabled($demand->status === 'fulfilled')
                                                >
                                                    <option value="approved" @selected(old("lines.$index.line_status", $line->line_status) === 'approved')>Approved</option>
                                                    <option value="partially_approved" @selected(old("lines.$index.line_status", $line->line_status) === 'partially_approved')>Partially Approved</option>
                                                    <option value="rejected" @selected(old("lines.$index.line_status", $line->line_status) === 'rejected')>Rejected</option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input
                                                    type="number"
                                                    name="lines[{{ $index }}][approved_quantity]"
                                                    min="0"
                                                    max="{{ $line->requested_quantity }}"
                                                    value="{{ old("lines.$index.approved_quantity", $line->approved_quantity) }}"
                                                    class="block w-24 rounded-md border-slate-300 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                    @disabled($demand->status === 'fulfilled')
                                                >
                                            </td>
                                            <td class="px-3 py-2">
                                                <input
                                                    type="text"
                                                    name="lines[{{ $index }}][remarks]"
                                                    value="{{ old("lines.$index.remarks", $line->remarks) }}"
                                                    class="block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                    @disabled($demand->status === 'fulfilled')
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <label for="review_note" class="mb-1 block text-sm font-medium text-slate-700">Review Note</label>
                            <textarea
                                id="review_note"
                                name="review_note"
                                rows="3"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                @disabled($demand->status === 'fulfilled')
                            >{{ old('review_note', $demand->review_note) }}</textarea>
                        </div>

                        @if ($demand->status !== 'fulfilled')
                            <button
                                type="submit"
                                class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                Save Review
                            </button>
                        @endif
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-900">Demand Summary</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-slate-600">Status</dt>
                                <dd class="text-slate-900">{{ str_replace('_', ' ', $demand->status) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-600">Session</dt>
                                <dd class="text-slate-900">{{ $demand->session ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-600">Teacher Note</dt>
                                <dd class="text-slate-900">{{ $demand->teacher_note ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-600">Reviewed By</dt>
                                <dd class="text-slate-900">{{ $demand->reviewer?->name ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-600">Reviewed At</dt>
                                <dd class="text-slate-900">{{ $demand->reviewed_at?->format('M d, Y h:i A') ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    @if (in_array($demand->status, ['approved', 'partially_approved'], true))
                        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-base font-semibold text-slate-900">Fulfill Demand</h3>
                            <p class="mt-1 text-sm text-slate-500">Only approved stock lines will be converted into issue records and stock movements.</p>
                            <form method="POST" action="{{ route('inventory.demands.fulfill', $demand) }}" class="mt-4 space-y-3">
                                @csrf
                                <div>
                                    <label for="note" class="mb-1 block text-sm font-medium text-slate-700">Fulfillment Note</label>
                                    <textarea
                                        id="note"
                                        name="note"
                                        rows="2"
                                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    class="inline-flex min-h-10 items-center rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                >
                                    Fulfill Approved Lines
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Issue History</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($demand->issues as $issue)
                        <div class="rounded-md border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900">
                                    Issue #{{ $issue->id }} on {{ $issue->issue_date?->format('M d, Y') }}
                                </p>
                                <p class="text-xs text-slate-500">Issued By: {{ $issue->issuer?->name ?: '-' }}</p>
                            </div>
                            <ul class="mt-2 list-disc space-y-1 ps-5 text-sm text-slate-700">
                                @foreach ($issue->lines as $line)
                                    <li>{{ $line->item?->name ?: 'Unknown Item' }} - Qty {{ $line->quantity }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No issue records yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
