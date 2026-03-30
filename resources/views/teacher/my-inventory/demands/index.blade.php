<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">My Stationery Demands</h2>
                <p class="mt-1 text-sm text-slate-500">Track the status of your submitted stationery requests.</p>
            </div>
            @can('create_inventory_demand')
                <a
                    href="{{ route('teacher.my-inventory.demands.create') }}"
                    class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    New Demand
                </a>
            @endcan
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
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Demand</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Request Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Lines</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($demands as $demand)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-800">#{{ $demand->id }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $demand->request_date?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $demand->session ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $demand->lines_count }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ str_replace('_', ' ', $demand->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ route('teacher.my-inventory.demands.show', $demand) }}"
                                        class="inline-flex min-h-9 items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                    No demand requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $demands->links() }}
        </div>
    </div>
</x-app-layout>
