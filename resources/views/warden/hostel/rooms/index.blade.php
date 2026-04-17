<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Hostel Rooms</h2>
                <p class="mt-1 text-sm text-slate-500">Manage room inventory, capacity, and occupancy status.</p>
            </div>
            <a href="{{ route('warden.hostel.rooms.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Add Room
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('warden.hostel.rooms.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-6">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Room name or notes" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="floor_number" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Floor</label>
                        <select id="floor_number" name="floor_number" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All floors</option>
                            @foreach ($floor_options as $floorNumber)
                                <option value="{{ $floorNumber }}" @selected((string) ($filters['floor_number'] ?? '') === (string) $floorNumber)>Floor {{ $floorNumber }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="gender" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Gender</label>
                        <select id="gender" name="gender" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All</option>
                            @foreach ($gender_options as $genderOption)
                                <option value="{{ $genderOption }}" @selected(($filters['gender'] ?? null) === $genderOption)>{{ ucfirst($genderOption) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="is_active" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="is_active" name="is_active" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All</option>
                            <option value="1" @selected(($filters['is_active'] ?? null) === true || (string) ($filters['is_active'] ?? '') === '1')>Active</option>
                            <option value="0" @selected(($filters['is_active'] ?? null) === false || (string) ($filters['is_active'] ?? '') === '0')>Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label for="per_page" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rows</label>
                        <select id="per_page" name="per_page" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ([10, 15, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 15) === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                        <a href="{{ route('warden.hostel.rooms.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Floor Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Capacity</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Occupied Beds / Students</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Available Beds</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($rooms as $room)
                                @php
                                    $occupiedBeds = (int) ($room->occupied_beds ?? 0);
                                    $capacity = (int) ($room->capacity ?? 0);
                                    $availableBeds = max(0, $capacity - $occupiedBeds);
                                @endphp
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $room->room_name }}</p>
                                        @if ($room->gender)
                                            <p class="text-xs text-slate-500">{{ ucfirst((string) $room->gender) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ (int) $room->floor_number }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $capacity }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $occupiedBeds }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $availableBeds }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        @if ($room->is_active)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('warden.hostel.rooms.students', $room) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Students
                                            </a>
                                            <a href="{{ route('warden.hostel.rooms.edit', $room) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No hostel rooms found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($rooms->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $rooms->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

