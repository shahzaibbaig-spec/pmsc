<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">My Device Declarations</h2>
                <p class="mt-1 text-sm text-slate-500">Declare and track Chromebook/device serial records.</p>
            </div>
            <a
                href="{{ route('teacher.my-inventory.devices.create') }}"
                class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                New Declaration
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

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Serial Number</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Device Info</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Linked Asset</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($declarations as $declaration)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $declaration->serial_number }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ ucfirst($declaration->device_type) }}
                                    <span class="text-slate-500">
                                        ({{ $declaration->brand ?: 'N/A' }} {{ $declaration->model ?: '' }})
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ str_replace('_', ' ', $declaration->status) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $declaration->assetUnit?->serial_number ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $declaration->created_at?->format('M d, Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                    No device declarations yet.
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
