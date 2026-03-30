<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Device Declaration #{{ $declaration->id }}</h2>
                <p class="mt-1 text-sm text-slate-500">Teacher: {{ $declaration->teacher?->user?->name ?? '-' }}</p>
            </div>
            <a
                href="{{ route('inventory.device-declarations.index') }}"
                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
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

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <h3 class="text-base font-semibold text-slate-900">Declaration Details</h3>
                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">Serial Number</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $declaration->serial_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">Device Type</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ ucfirst($declaration->device_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">Brand</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $declaration->brand ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">Model</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $declaration->model ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">Current Status</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ str_replace('_', ' ', $declaration->status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">Linked Asset Unit</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $declaration->assetUnit?->serial_number ?: '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">Teacher Note</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $declaration->teacher_note ?: '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">Admin Note</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $declaration->admin_note ?: '-' }}</dd>
                        </div>
                    </dl>

                    <h4 class="mt-6 text-sm font-semibold uppercase tracking-wide text-slate-600">Matched Asset Units</h4>
                    <div class="mt-2 overflow-hidden rounded-md border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Serial</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Brand / Model</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($matchedAssets as $asset)
                                    <tr>
                                        <td class="px-3 py-2 text-xs font-medium text-slate-800">{{ $asset->serial_number }}</td>
                                        <td class="px-3 py-2 text-xs text-slate-700">{{ $asset->brand ?: '-' }} {{ $asset->model ?: '' }}</td>
                                        <td class="px-3 py-2 text-xs text-slate-700">{{ $asset->status }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-3 text-xs text-slate-500">No similar asset units found by serial.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm" x-data="{ action: '{{ old('action', 'verify') }}' }">
                    <h3 class="text-base font-semibold text-slate-900">Review Action</h3>
                    <form method="POST" action="{{ route('inventory.device-declarations.review', $declaration) }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="action" class="mb-1 block text-sm font-medium text-slate-700">Action</label>
                            <select
                                id="action"
                                name="action"
                                x-model="action"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="verify">Verify</option>
                                <option value="link">Link to Asset</option>
                                <option value="reject">Reject</option>
                            </select>
                        </div>

                        <div x-show="action === 'link'">
                            <label for="asset_unit_id" class="mb-1 block text-sm font-medium text-slate-700">Asset Unit</label>
                            <select
                                id="asset_unit_id"
                                name="asset_unit_id"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select asset unit</option>
                                @foreach ($matchedAssets as $asset)
                                    <option value="{{ $asset->id }}" @selected((string) old('asset_unit_id') === (string) $asset->id)>
                                        {{ $asset->serial_number }} ({{ $asset->status }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="admin_note" class="mb-1 block text-sm font-medium text-slate-700">Admin Note</label>
                            <textarea
                                id="admin_note"
                                name="admin_note"
                                rows="3"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >{{ old('admin_note', $declaration->admin_note) }}</textarea>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Submit Review
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
