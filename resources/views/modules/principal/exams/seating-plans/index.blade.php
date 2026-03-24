<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Exams Seating Plan</h2>
            <p class="mt-1 text-sm text-slate-500">Create rooms, generate room-wise seat assignments, and print seating records.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Rooms</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $rooms->count() }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Rooms</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ $rooms->where('is_active', true)->count() }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Capacity</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-700">{{ (int) $rooms->where('is_active', true)->sum('capacity') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Saved Plans</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $plans->total() }}</p>
            </article>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Create Exam Room</h3>
                <p class="mt-1 text-xs text-slate-500">Room names should be unique. Use active toggle to include/exclude room from generation.</p>

                <form method="POST" action="{{ route('principal.exams.seating-plans.rooms.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @csrf
                    <div class="md:col-span-2">
                        <label for="room_name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Room Name</label>
                        <input
                            id="room_name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                            placeholder="Room A-01"
                            class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>
                    <div>
                        <label for="room_capacity" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Capacity</label>
                        <input
                            id="room_capacity"
                            name="capacity"
                            type="number"
                            min="1"
                            max="5000"
                            value="{{ old('capacity') }}"
                            required
                            placeholder="40"
                            class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm text-slate-700">
                            <input type="hidden" name="is_active" value="0">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(old('is_active', '1') === '1')
                            >
                            Active Room
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.button type="submit">Save Room</x-ui.button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Generate Seating Plan</h3>
                <p class="mt-1 text-xs text-slate-500">Select exam session and classes. Seats are allocated by roll order unless randomization is enabled.</p>

                @php
                    $selectedClassIds = collect(old('class_ids', []))
                        ->map(fn ($id): int => (int) $id)
                        ->all();
                @endphp

                <form method="POST" action="{{ route('principal.exams.seating-plans.generate') }}" class="mt-4 grid grid-cols-1 gap-4">
                    @csrf
                    <div>
                        <label for="exam_session_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</label>
                        <select
                            id="exam_session_id"
                            name="exam_session_id"
                            required
                            class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Select exam session</option>
                            @foreach($examSessions as $examSession)
                                <option
                                    value="{{ $examSession->id }}"
                                    @selected((string) old('exam_session_id', $selectedExamSessionId) === (string) $examSession->id)
                                >
                                    {{ $examSession->name }} ({{ $examSession->session }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Classes</label>
                        <div class="max-h-52 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-3">
                            @forelse($classes as $classRoom)
                                @php
                                    $classLabel = trim($classRoom->name.' '.($classRoom->section ?? ''));
                                    $classLabel = $classLabel !== '' ? $classLabel : ('Class '.$classRoom->id);
                                @endphp
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="class_ids[]"
                                        value="{{ $classRoom->id }}"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        @checked(in_array((int) $classRoom->id, $selectedClassIds, true))
                                    >
                                    <span>{{ $classLabel }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-slate-500">No classes found.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <label class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="is_randomized"
                                value="1"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(old('is_randomized') === '1')
                            >
                            Randomize Students Across Classes
                        </label>
                    </div>

                    <div>
                        <x-ui.button type="submit">Generate Seating Plan</x-ui.button>
                    </div>
                </form>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Exam Rooms</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Capacity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($rooms as $room)
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $room->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $room->capacity }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($room->is_active)
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">No exam rooms found. Create at least one active room.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <form method="GET" action="{{ route('principal.exams.seating-plans.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label for="filter_exam_session_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Filter By Exam Session</label>
                        <select
                            id="filter_exam_session_id"
                            name="exam_session_id"
                            class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach($examSessions as $examSession)
                                <option value="{{ $examSession->id }}" @selected((string) $selectedExamSessionId === (string) $examSession->id)>
                                    {{ $examSession->name }} ({{ $examSession->session }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2 md:col-span-2">
                        <x-ui.button type="submit">Apply Filter</x-ui.button>
                        <a
                            href="{{ route('principal.exams.seating-plans.index') }}"
                            class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Classes</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Students</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rooms</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Generated</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($plans as $plan)
                            @php
                                $classNames = collect((array) $plan->class_ids)
                                    ->map(fn ($id) => $classMap->get((int) $id))
                                    ->filter()
                                    ->values();
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">{{ $plan->examSession?->name ?? 'Session' }}</div>
                                    <div class="text-xs text-slate-500">{{ $plan->examSession?->session ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if($classNames->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($classNames as $name)
                                                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ (int) $plan->total_students }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $plan->total_rooms }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($plan->is_randomized)
                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Randomized</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">Roll Order</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div>{{ optional($plan->generated_at)->format('d M Y h:i A') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $plan->generator?->name ?: 'System' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('principal.exams.seating-plans.show', $plan) }}"
                                            class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            View
                                        </a>
                                        <a
                                            href="{{ route('principal.exams.seating-plans.print', $plan) }}"
                                            target="_blank"
                                            class="inline-flex min-h-10 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                        >
                                            Printable
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No seating plans generated yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $plans->links() }}
            </div>
        </section>
    </div>
</x-app-layout>
