<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Academic Calendar</h2>
            <p class="mt-1 text-sm text-slate-500">Manage school events and teacher reminders.</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <x-ui.card title="Filters">
        <form method="GET" action="{{ route('academic-calendar.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div>
                <label for="type_filter" class="text-xs font-medium text-slate-600">Type</label>
                <select
                    id="type_filter"
                    name="type"
                    class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All types</option>
                    @foreach ($typeOptions as $typeOption)
                        <option value="{{ $typeOption }}" @selected($selectedType === $typeOption)>
                            {{ strtoupper($typeOption) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-3">
                <x-ui.button type="submit">Apply Filter</x-ui.button>
                <x-ui.button href="{{ route('academic-calendar.index') }}" variant="outline">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    @if ($canManage)
        <x-ui.card class="mt-4" :title="$editingEvent ? 'Edit Event' : 'Add Event'">
            <form
                method="POST"
                action="{{ $editingEvent ? route('academic-calendar.update', $editingEvent) : route('academic-calendar.store') }}"
                class="grid grid-cols-1 gap-4 md:grid-cols-2"
            >
                @csrf
                @if ($editingEvent)
                    @method('PUT')
                @endif

                <div class="md:col-span-2">
                    <label for="title" class="text-xs font-medium text-slate-600">Title</label>
                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="{{ old('title', $editingEvent?->title) }}"
                        class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        required
                    >
                    @error('title')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="text-xs font-medium text-slate-600">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >{{ old('description', $editingEvent?->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="start_date" class="text-xs font-medium text-slate-600">Start Date</label>
                    <input
                        id="start_date"
                        name="start_date"
                        type="date"
                        value="{{ old('start_date', optional($editingEvent?->start_date)->toDateString()) }}"
                        class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        required
                    >
                    @error('start_date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="text-xs font-medium text-slate-600">End Date</label>
                    <input
                        id="end_date"
                        name="end_date"
                        type="date"
                        value="{{ old('end_date', optional($editingEvent?->end_date)->toDateString()) }}"
                        class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                    @error('end_date')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="text-xs font-medium text-slate-600">Type</label>
                    <select
                        id="type"
                        name="type"
                        class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        required
                    >
                        @foreach ($typeOptions as $typeOption)
                            <option value="{{ $typeOption }}" @selected(old('type', $editingEvent?->type) === $typeOption)>
                                {{ strtoupper($typeOption) }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col justify-end">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            id="notify_before"
                            name="notify_before"
                            type="checkbox"
                            value="1"
                            @checked(old('notify_before', $editingEvent?->notify_before))
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        Notify teachers before event
                    </label>
                    @error('notify_before')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notify_days_before" class="text-xs font-medium text-slate-600">Notify Days Before</label>
                    <input
                        id="notify_days_before"
                        name="notify_days_before"
                        type="number"
                        min="0"
                        max="365"
                        value="{{ old('notify_days_before', $editingEvent?->notify_days_before ?? 0) }}"
                        class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                    @error('notify_days_before')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2 flex flex-wrap items-center gap-2">
                    <x-ui.button type="submit">{{ $editingEvent ? 'Update Event' : 'Create Event' }}</x-ui.button>
                    @if ($editingEvent)
                        <x-ui.button href="{{ route('academic-calendar.index', ['type' => $selectedType]) }}" variant="outline">Cancel Edit</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card class="mt-4" title="Event List">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Notify</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Created By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $event->title }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ \Illuminate\Support\Str::limit((string) $event->description, 120) ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ strtoupper($event->type) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ optional($event->start_date)->format('d M Y') }}
                                @if ($event->end_date && $event->end_date->toDateString() !== $event->start_date?->toDateString())
                                    <span class="text-slate-400">to</span> {{ optional($event->end_date)->format('d M Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                @if ($event->notify_before)
                                    Yes ({{ (int) $event->notify_days_before }} day{{ (int) $event->notify_days_before === 1 ? '' : 's' }} before)
                                @else
                                    No
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $event->creator?->name ?? 'System' }}</td>
                            <td class="px-4 py-3">
                                @if ($canManage)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.button
                                            href="{{ route('academic-calendar.index', ['type' => $selectedType, 'edit' => $event->id]) }}"
                                            variant="outline"
                                            size="sm"
                                        >
                                            Edit
                                        </x-ui.button>

                                        <form method="POST" action="{{ route('academic-calendar.send-reminder', $event) }}">
                                            @csrf
                                            <x-ui.button type="submit" variant="secondary" size="sm">Send Reminder</x-ui.button>
                                        </form>

                                        <form method="POST" action="{{ route('academic-calendar.destroy', $event) }}" onsubmit="return confirm('Delete this event?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No academic events found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $events->links() }}
        </div>
    </x-ui.card>

    @if ($canManage)
        <script>
            (() => {
                const notifyCheckbox = document.getElementById('notify_before');
                const notifyDaysInput = document.getElementById('notify_days_before');
                if (!notifyCheckbox || !notifyDaysInput) {
                    return;
                }

                const syncState = () => {
                    notifyDaysInput.disabled = !notifyCheckbox.checked;
                    if (!notifyCheckbox.checked) {
                        notifyDaysInput.value = 0;
                    }
                };

                notifyCheckbox.addEventListener('change', syncState);
                syncState();
            })();
        </script>
    @endif
</x-app-layout>

