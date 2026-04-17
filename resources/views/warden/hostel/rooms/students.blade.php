<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Room Students</h2>
                <p class="mt-1 text-sm text-slate-500">Current active student allocations for {{ $room->room_name }} (Floor {{ $room->floor_number }}).</p>
            </div>
            <a href="{{ route('warden.hostel.rooms.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Rooms
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Capacity</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900">{{ (int) ($occupancy['room']->capacity ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Occupied</p>
                    <p class="mt-1 text-xl font-semibold text-cyan-800">{{ (int) ($occupancy['occupied_beds'] ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Available</p>
                    <p class="mt-1 text-xl font-semibold text-emerald-800">{{ (int) ($occupancy['available_beds'] ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Occupancy %</p>
                    <p class="mt-1 text-xl font-semibold text-indigo-800">{{ number_format((float) ($occupancy['occupancy_percentage'] ?? 0), 2) }}%</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Floor Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Allocation Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($students as $allocation)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $allocation['student_name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $allocation['student_code'] }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $allocation['class_name'] ?: '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $allocation['room_name'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $allocation['floor_number'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        {{ ! empty($allocation['allocated_from']) ? \Illuminate\Support\Carbon::parse($allocation['allocated_from'])->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ ucfirst($allocation['status']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No active students are currently allocated to this room.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

