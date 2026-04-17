<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Edit Hostel Room</h2>
                <p class="mt-1 text-sm text-slate-500">Update room details and monitor occupancy summary.</p>
            </div>
            <a href="{{ route('warden.hostel.rooms.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Room List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Capacity</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900">{{ (int) ($occupancy['room']->capacity ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Occupied Beds</p>
                    <p class="mt-1 text-xl font-semibold text-cyan-800">{{ (int) ($occupancy['occupied_beds'] ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Available Beds</p>
                    <p class="mt-1 text-xl font-semibold text-emerald-800">{{ (int) ($occupancy['available_beds'] ?? 0) }}</p>
                </article>
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Occupancy %</p>
                    <p class="mt-1 text-xl font-semibold text-indigo-800">{{ number_format((float) ($occupancy['occupancy_percentage'] ?? 0), 2) }}%</p>
                </article>
            </section>

            @include('warden.hostel.rooms.partials.form', [
                'action' => route('warden.hostel.rooms.update', $room),
                'method' => 'PUT',
                'room' => $room,
                'submitLabel' => 'Update Room',
            ])
        </div>
    </div>
</x-app-layout>

