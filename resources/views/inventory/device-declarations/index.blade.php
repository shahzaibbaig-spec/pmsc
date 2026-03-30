<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Device Declarations</h2>
            <p class="mt-1 text-sm text-slate-500">Review teacher Chromebook/device serial declarations.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('inventory.device-declarations.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="search" class="mb-1 block text-sm font-medium text-slate-700">Search</label>
                        <input
                            id="search"
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Teacher or serial number"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                    </div>
                    <div>
                        <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                        <select
                            id="status"
                            name="status"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            <option value="">All</option>
                            @foreach ($statusOptions as $option)
                                <option value="{{ $option }}" @selected($status === $option)>
                                    {{ str_replace('_', ' ', $option) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Filter
                        </button>
                        <a
                            href="{{ route('inventory.device-declarations.index') }}"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Teacher</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Serial Number</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Device</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Asset Link</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($declarations as $declaration)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-800">{{ $declaration->teacher?->user?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $declaration->serial_number }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ ucfirst($declaration->device_type) }}
                                    <span class="text-slate-500">({{ $declaration->brand ?: 'N/A' }} {{ $declaration->model ?: '' }})</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ str_replace('_', ' ', $declaration->status) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $declaration->assetUnit?->serial_number ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ route('inventory.device-declarations.show', $declaration) }}"
                                        class="inline-flex min-h-9 items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                    No declarations found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $declarations->links() }}
        </div>
    </div>
</x-app-layout>
