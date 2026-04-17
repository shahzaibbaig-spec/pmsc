<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Create Hostel Room</h2>
                <p class="mt-1 text-sm text-slate-500">Add a new hostel room with floor, capacity, and status details.</p>
            </div>
            <a href="{{ route('warden.hostel.rooms.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Room List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('warden.hostel.rooms.partials.form', [
                'action' => route('warden.hostel.rooms.store'),
                'method' => 'POST',
                'room' => null,
                'submitLabel' => 'Create Room',
            ])
        </div>
    </div>
</x-app-layout>

