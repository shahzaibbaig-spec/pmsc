<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Create Stationery Demand</h2>
                <p class="mt-1 text-sm text-slate-500">Add one or more stationery items in a single request.</p>
            </div>
            <a
                href="{{ route('teacher.my-inventory.demands.index') }}"
                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Back to Demands
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
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

            <div
                class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm"
                x-data="inventoryDemandForm(@js(
                    old('lines', [
                        ['item_id' => '', 'requested_item_name' => '', 'requested_quantity' => 1, 'remarks' => ''],
                    ])
                ))"
            >
                <form method="POST" action="{{ route('teacher.my-inventory.demands.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="request_date" class="mb-1 block text-sm font-medium text-slate-700">Request Date</label>
                            <input
                                id="request_date"
                                type="date"
                                name="request_date"
                                value="{{ old('request_date', now()->toDateString()) }}"
                                required
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>
                        <div>
                            <label for="session" class="mb-1 block text-sm font-medium text-slate-700">Session (Optional)</label>
                            <input
                                id="session"
                                type="text"
                                name="session"
                                value="{{ old('session') }}"
                                placeholder="e.g. 2026-2027"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="teacher_note" class="mb-1 block text-sm font-medium text-slate-700">Teacher Note (Optional)</label>
                        <textarea
                            id="teacher_note"
                            name="teacher_note"
                            rows="3"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Additional note for reviewers"
                        >{{ old('teacher_note') }}</textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-900">Demand Lines</h3>
                            <button
                                type="button"
                                class="inline-flex min-h-9 items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                @click="addRow()"
                            >
                                Add Line
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Choose an item or select Other to enter a manual item name.</p>

                        <div class="mt-4 space-y-4">
                            <template x-for="(line, index) in rows" :key="line.rowKey">
                                <div class="rounded-md border border-slate-200 p-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                                        <div class="md:col-span-4">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Item</label>
                                            <select
                                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                x-model="line.item_id"
                                                :name="`lines[${index}][item_id]`"
                                            >
                                                <option value="">Select item</option>
                                                @foreach ($items as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                                <option value="other">Other</option>
                                            </select>
                                        </div>

                                        <div class="md:col-span-3" x-show="line.item_id === 'other'">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Other Item Name</label>
                                            <input
                                                type="text"
                                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="e.g. White board duster"
                                                x-model="line.requested_item_name"
                                                :name="`lines[${index}][requested_item_name]`"
                                            >
                                        </div>

                                        <template x-if="line.item_id !== 'other'">
                                            <input type="hidden" :name="`lines[${index}][requested_item_name]`" value="">
                                        </template>

                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Quantity</label>
                                            <input
                                                type="number"
                                                min="1"
                                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                x-model="line.requested_quantity"
                                                :name="`lines[${index}][requested_quantity]`"
                                                required
                                            >
                                        </div>

                                        <div class="md:col-span-3">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Remarks (Optional)</label>
                                            <input
                                                type="text"
                                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                x-model="line.remarks"
                                                :name="`lines[${index}][remarks]`"
                                            >
                                        </div>
                                    </div>

                                    <div class="mt-3 flex justify-end" x-show="rows.length > 1">
                                        <button
                                            type="button"
                                            class="rounded-md border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
                                            @click="removeRow(index)"
                                        >
                                            Remove Line
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Submit Demand
                        </button>
                        <a
                            href="{{ route('teacher.my-inventory.demands.index') }}"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function inventoryDemandForm(initialRows) {
            return {
                rows: (initialRows || []).map((row, idx) => ({
                    rowKey: Date.now() + idx,
                    item_id: row.item_id ? String(row.item_id) : (row.requested_item_name ? 'other' : ''),
                    requested_item_name: row.requested_item_name || '',
                    requested_quantity: row.requested_quantity || 1,
                    remarks: row.remarks || '',
                })),
                addRow() {
                    this.rows.push({
                        rowKey: Date.now() + Math.random(),
                        item_id: '',
                        requested_item_name: '',
                        requested_quantity: 1,
                        remarks: '',
                    });
                },
                removeRow(index) {
                    this.rows.splice(index, 1);
                },
            };
        }
    </script>
</x-app-layout>
