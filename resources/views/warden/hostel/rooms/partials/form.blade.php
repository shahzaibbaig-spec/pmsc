@php
    $room = $room ?? null;
@endphp

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

@if ($errors->has('hostel_room'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ $errors->first('hostel_room') }}
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if (strtoupper($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="room_name" class="mb-1 block text-sm font-medium text-slate-700">Room Name</label>
            <input
                id="room_name"
                type="text"
                name="room_name"
                value="{{ old('room_name', $room?->room_name) }}"
                required
                maxlength="255"
                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                placeholder="e.g. A-101"
            >
        </div>

        <div>
            <label for="floor_number" class="mb-1 block text-sm font-medium text-slate-700">Floor Number</label>
            <input
                id="floor_number"
                type="number"
                name="floor_number"
                value="{{ old('floor_number', $room?->floor_number ?? 0) }}"
                required
                min="0"
                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >
        </div>

        <div>
            <label for="capacity" class="mb-1 block text-sm font-medium text-slate-700">Capacity</label>
            <input
                id="capacity"
                type="number"
                name="capacity"
                value="{{ old('capacity', $room?->capacity ?? 1) }}"
                required
                min="1"
                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >
        </div>

        <div>
            <label for="gender" class="mb-1 block text-sm font-medium text-slate-700">Gender (Optional)</label>
            <input
                id="gender"
                type="text"
                name="gender"
                value="{{ old('gender', $room?->gender) }}"
                maxlength="50"
                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                placeholder="e.g. boys / girls / mixed"
            >
        </div>
    </div>

    <div>
        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notes (Optional)</label>
        <textarea
            id="notes"
            name="notes"
            rows="4"
            class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
        >{{ old('notes', $room?->notes) }}</textarea>
    </div>

    <div>
        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked((int) old('is_active', $room?->is_active ?? 1) === 1)
                class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500"
            >
            Active Room
        </label>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            {{ $submitLabel ?? 'Save Room' }}
        </button>
        <a href="{{ route('warden.hostel.rooms.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>

