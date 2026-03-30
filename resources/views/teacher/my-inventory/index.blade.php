<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">My Inventory</h2>
            <p class="mt-1 text-sm text-slate-500">Manage stationery demands and Chromebook/device declarations.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <x-ui.card title="Pending Demands">
                    <p class="text-3xl font-semibold text-slate-900">{{ (int) ($demandStats['pending'] ?? 0) }}</p>
                </x-ui.card>
                <x-ui.card title="Approved/Partial">
                    <p class="text-3xl font-semibold text-slate-900">
                        {{ (int) ($demandStats['approved'] ?? 0) + (int) ($demandStats['partially_approved'] ?? 0) }}
                    </p>
                </x-ui.card>
                <x-ui.card title="Rejected">
                    <p class="text-3xl font-semibold text-slate-900">{{ (int) ($demandStats['rejected'] ?? 0) }}</p>
                </x-ui.card>
                <x-ui.card title="Fulfilled">
                    <p class="text-3xl font-semibold text-slate-900">{{ (int) ($demandStats['fulfilled'] ?? 0) }}</p>
                </x-ui.card>
            </div>

            <x-ui.card title="Quick Actions">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @can('create_inventory_demand')
                        <x-ui.button href="{{ route('teacher.my-inventory.demands.create') }}">New Stationery Demand</x-ui.button>
                    @endcan
                    @can('view_own_inventory_demands')
                        <x-ui.button href="{{ route('teacher.my-inventory.demands.index') }}" variant="secondary">My Demand History</x-ui.button>
                    @endcan
                    @can('submit_device_declaration')
                        <x-ui.button href="{{ route('teacher.my-inventory.devices.create') }}" variant="success">Declare Device</x-ui.button>
                    @endcan
                    @can('submit_device_declaration')
                        <x-ui.button href="{{ route('teacher.my-inventory.devices.index') }}" variant="outline">My Device Declarations</x-ui.button>
                    @endcan
                </div>
            </x-ui.card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Recent Demands</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentDemands as $demand)
                            <a
                                href="{{ route('teacher.my-inventory.demands.show', $demand) }}"
                                class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 hover:bg-slate-50"
                            >
                                <div>
                                    <p class="text-sm font-medium text-slate-800">Demand #{{ $demand->id }}</p>
                                    <p class="text-xs text-slate-500">{{ $demand->request_date?->format('M d, Y') }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                    {{ str_replace('_', ' ', $demand->status) }}
                                </span>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">No demand submitted yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Recent Device Declarations</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentDeclarations as $declaration)
                            <div class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ $declaration->serial_number }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $declaration->brand ?: 'N/A' }} {{ $declaration->model ?: '' }}
                                    </p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                    {{ str_replace('_', ' ', $declaration->status) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No device declaration submitted yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
