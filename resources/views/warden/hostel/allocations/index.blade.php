<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Room Allocations</h2>
                <p class="mt-1 text-sm text-slate-500">Manage current hostel room assignments and student shifts.</p>
            </div>
            <a href="{{ route('warden.hostel.allocations.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Allocate Student
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

            @if ($errors->has('allocation'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('allocation') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('warden.hostel.allocations.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-6">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Student, room, remarks" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div>
                        <label for="room_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Room</label>
                        <select id="room_id" name="room_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All rooms</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room['id'] }}" @selected((int) ($filters['room_id'] ?? 0) === (int) $room['id'])>{{ $room['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All classes</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>{{ $class['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All statuses</option>
                            @foreach (['active', 'shifted', 'completed'] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                        <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
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
                        <a href="{{ route('warden.hostel.allocations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="{ openRemove: null }">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Floor Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Allocated From</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($allocations as $allocation)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $allocation->student?->name ?? 'Student' }}</p>
                                        <p class="text-xs text-slate-500">{{ $allocation->student?->student_id ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ trim(($allocation->student?->classRoom?->name ?? '').' '.($allocation->student?->classRoom?->section ?? '')) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $allocation->hostelRoom?->room_name ?? '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ (int) ($allocation->hostelRoom?->floor_number ?? 0) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ optional($allocation->allocated_from)->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        @if ($allocation->status === 'active')
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                        @elseif ($allocation->status === 'shifted')
                                            <span class="inline-flex rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-700">Shifted</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">Completed</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm">
                                        @if ($allocation->status === 'active')
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('warden.hostel.allocations.shift.edit', $allocation->student_id) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                    Shift
                                                </a>
                                                <button type="button" @click="openRemove = openRemove === {{ (int) $allocation->id }} ? null : {{ (int) $allocation->id }}" class="inline-flex min-h-10 items-center rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    Remove
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-500">No actions</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr x-show="openRemove === {{ (int) $allocation->id }}" x-cloak>
                                    <td colspan="7" class="bg-rose-50/60 px-4 py-4">
                                        <form method="POST" action="{{ route('warden.hostel.allocations.remove', $allocation->student_id) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Allocation End Date</label>
                                                <input type="date" name="allocated_to" value="{{ now()->toDateString() }}" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Remarks (Optional)</label>
                                                <input type="text" name="remarks" maxlength="1000" placeholder="Reason for closing allocation" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                            </div>
                                            <div class="flex items-end gap-2">
                                                <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">
                                                    Confirm Remove
                                                </button>
                                                <button type="button" @click="openRemove = null" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No room allocations found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($allocations->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $allocations->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

