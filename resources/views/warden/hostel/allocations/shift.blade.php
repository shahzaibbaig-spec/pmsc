<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Shift Student Room</h2>
                <p class="mt-1 text-sm text-slate-500">Close the current room assignment and move the student to a new room.</p>
            </div>
            <a href="{{ route('warden.hostel.allocations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Allocations
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->has('allocation'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('allocation') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Current Allocation</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                    <p><span class="font-semibold text-slate-900">Student:</span> {{ $currentAllocation->student?->name ?? $student->name }}</p>
                    <p><span class="font-semibold text-slate-900">Admission No:</span> {{ $currentAllocation->student?->student_id ?? '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Class:</span> {{ trim(($currentAllocation->student?->classRoom?->name ?? '').' '.($currentAllocation->student?->classRoom?->section ?? '')) }}</p>
                    <p>
                        <span class="font-semibold text-slate-900">Current Room:</span>
                        @if ($currentAllocation->hostelRoom)
                            {{ $currentAllocation->hostelRoom?->room_name }} (Floor {{ $currentAllocation->hostelRoom?->floor_number }})
                        @else
                            Hostel Assigned (Room Pending)
                        @endif
                    </p>
                    <p><span class="font-semibold text-slate-900">Allocated From:</span> {{ optional($currentAllocation->allocated_from)->format('d M Y') }}</p>
                </div>
            </section>

            <form method="POST" action="{{ route('warden.hostel.allocations.shift.update', $student) }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')

                <div>
                    <label for="hostel_room_id" class="mb-1 block text-sm font-medium text-slate-700">New Room</label>
                    <select id="hostel_room_id" name="hostel_room_id" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">Select room</option>
                        @foreach ($availableRooms as $room)
                            <option value="{{ $room['id'] }}" @selected((int) old('hostel_room_id') === (int) $room['id'])>
                                {{ $room['name'] }} | Capacity: {{ $room['capacity'] }} | Occupied: {{ $room['occupied_beds'] }} | Available: {{ $room['available_beds'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="allocated_from" class="mb-1 block text-sm font-medium text-slate-700">New Allocation Start Date</label>
                    <input id="allocated_from" type="date" name="allocated_from" value="{{ old('allocated_from', now()->toDateString()) }}" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div>
                    <label for="remarks" class="mb-1 block text-sm font-medium text-slate-700">Remarks (Optional)</label>
                    <textarea id="remarks" name="remarks" rows="4" class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('remarks') }}</textarea>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Shift Room
                    </button>
                    <a href="{{ route('warden.hostel.allocations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
