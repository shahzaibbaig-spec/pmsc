<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Declare Chromebook / Device</h2>
                <p class="mt-1 text-sm text-slate-500">Submit the serial number of the device currently in your possession.</p>
            </div>
            <a
                href="{{ route('teacher.my-inventory.devices.index') }}"
                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Back to Device List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('teacher.my-inventory.devices.store') }}" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="device_type" class="mb-1 block text-sm font-medium text-slate-700">Device Type</label>
                            <select
                                id="device_type"
                                name="device_type"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="chromebook" @selected(old('device_type', 'chromebook') === 'chromebook')>Chromebook</option>
                                <option value="laptop" @selected(old('device_type') === 'laptop')>Laptop</option>
                                <option value="tablet" @selected(old('device_type') === 'tablet')>Tablet</option>
                                <option value="device" @selected(old('device_type') === 'device')>Other Device</option>
                            </select>
                        </div>
                        <div>
                            <label for="serial_number" class="mb-1 block text-sm font-medium text-slate-700">Serial Number</label>
                            <input
                                id="serial_number"
                                type="text"
                                name="serial_number"
                                value="{{ old('serial_number') }}"
                                required
                                placeholder="Enter serial number"
                                class="block w-full rounded-md border-slate-300 text-sm uppercase shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="brand" class="mb-1 block text-sm font-medium text-slate-700">Brand (Optional)</label>
                            <input
                                id="brand"
                                type="text"
                                name="brand"
                                value="{{ old('brand') }}"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>
                        <div>
                            <label for="model" class="mb-1 block text-sm font-medium text-slate-700">Model (Optional)</label>
                            <input
                                id="model"
                                type="text"
                                name="model"
                                value="{{ old('model') }}"
                                class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="teacher_note" class="mb-1 block text-sm font-medium text-slate-700">Note (Optional)</label>
                        <textarea
                            id="teacher_note"
                            name="teacher_note"
                            rows="3"
                            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Any additional details for admin/principal"
                        >{{ old('teacher_note') }}</textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center rounded-md bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Submit Declaration
                        </button>
                        <a
                            href="{{ route('teacher.my-inventory.devices.index') }}"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
