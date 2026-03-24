<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Exams Room Invigilators</h2>
            <p class="mt-1 text-sm text-slate-500">Assign invigilators to exam rooms based on latest seating plan.</p>
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
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rooms In Plan</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($summary['total_rooms'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rooms Assigned</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-700">{{ (int) ($summary['assigned_rooms'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Invigilators Assigned</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ (int) ($summary['total_assignments'] ?? 0) }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Seats In Plan</p>
                <p class="mt-2 text-2xl font-semibold text-amber-700">{{ (int) ($summary['seats_in_plan'] ?? 0) }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('principal.exams.room-invigilators.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="exam_session_id_filter" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Session</label>
                    <select
                        id="exam_session_id_filter"
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
                <div class="flex items-end gap-2 md:col-span-3">
                    <x-ui.button type="submit">Apply Session</x-ui.button>
                    <a
                        href="{{ route('principal.exams.room-invigilators.index') }}"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Assign Invigilator</h3>
                <p class="mt-1 text-xs text-slate-500">Teachers can mark attendance only for assigned rooms.</p>

                @if(! $hasSeatingPlan)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        No seating plan found for selected exam session. Generate seating plan first.
                    </div>
                @else
                    <form method="POST" action="{{ route('principal.exams.room-invigilators.store') }}" class="mt-4 grid grid-cols-1 gap-4">
                        @csrf
                        <input type="hidden" name="exam_session_id" value="{{ $selectedExamSessionId }}">
                        <div>
                            <label for="room_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Room</label>
                            <select
                                id="room_id"
                                name="room_id"
                                required
                                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select room</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" @selected((string) old('room_id') === (string) $room->id)>
                                        {{ $room->name }} (Seats: {{ (int) ($seatCountMap->get((int) $room->id) ?? 0) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="teacher_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Invigilator (Teacher)</label>
                            <select
                                id="teacher_id"
                                name="teacher_id"
                                required
                                class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((string) old('teacher_id') === (string) $teacher->id)>
                                        {{ $teacher->user?->name }} ({{ $teacher->teacher_id ?: ('T-'.$teacher->id) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-ui.button type="submit">Assign Invigilator</x-ui.button>
                        </div>
                    </form>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Room Seat Summary</h3>
                <div class="mt-4 max-h-80 overflow-y-auto">
                    <div class="space-y-2">
                        @forelse($rooms as $room)
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $room->name }}</p>
                                    <p class="text-xs text-slate-500">Capacity: {{ (int) $room->capacity }}</p>
                                </div>
                                <span class="inline-flex rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                    {{ (int) ($seatCountMap->get((int) $room->id) ?? 0) }} seats
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No room data available for selected session.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Assigned Invigilators</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Invigilator</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($assignments as $assignment)
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $assignment->room?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $assignment->teacher?->user?->name ?? 'Teacher' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $assignment->teacher?->teacher_id ?: ('T-'.$assignment->teacher_id) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <form
                                        method="POST"
                                        action="{{ route('principal.exams.room-invigilators.destroy', ['examRoomInvigilator' => $assignment, 'exam_session_id' => $selectedExamSessionId]) }}"
                                        onsubmit="return confirm('Remove this invigilator assignment?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex min-h-10 items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No invigilators assigned for selected exam session.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $assignments->links() }}
            </div>
        </section>
    </div>
</x-app-layout>
