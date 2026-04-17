<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Mark Night Attendance</h2>
                <p class="mt-1 text-sm text-slate-500">Bulk mark nightly hostel attendance for allocated students.</p>
            </div>
            <a href="{{ route('warden.hostel.night-attendance.index', ['date' => $attendance_date]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Attendance List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
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

            @if ($errors->has('night_attendance'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('night_attendance') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('warden.hostel.night-attendance.create') }}" class="grid grid-cols-1 gap-4 md:grid-cols-5">
                    <div>
                        <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                        <input id="date" type="date" name="date" value="{{ $attendance_date }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Student name or admission no" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
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

                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Load Students</button>
                        <a href="{{ route('warden.hostel.night-attendance.create', ['date' => $attendance_date]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </section>

            <form method="POST" action="{{ route('warden.hostel.night-attendance.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="{ bulkStatus: '' }">
                @csrf
                <input type="hidden" name="attendance_date" value="{{ $attendance_date }}">

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">Attendance Date: {{ \Illuminate\Support\Carbon::parse($attendance_date)->format('d M Y') }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="bulk_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bulk Status</label>
                        <select id="bulk_status" x-model="bulkStatus" class="min-h-10 rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">Select</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                        <button type="button" @click="if (bulkStatus) { document.querySelectorAll('.attendance-status').forEach(el => el.value = bulkStatus); }" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Apply to All
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Floor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($hostel_students as $index => $studentRow)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $studentRow['student_name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $studentRow['student_code'] }} | {{ $studentRow['class_name'] }}</p>
                                        <input type="hidden" name="rows[{{ $index }}][student_id]" value="{{ $studentRow['student_id'] }}">
                                        <input type="hidden" name="rows[{{ $index }}][hostel_room_id]" value="{{ $studentRow['hostel_room_id'] }}">
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $studentRow['room_name'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $studentRow['floor_number'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        <select name="rows[{{ $index }}][status]" class="attendance-status block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                            @foreach ($statuses as $status)
                                                <option value="{{ $status }}" @selected(old("rows.$index.status", $studentRow['existing_status']) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        <input type="text" name="rows[{{ $index }}][remarks]" value="{{ old("rows.$index.remarks", $studentRow['existing_remarks']) }}" maxlength="1000" class="block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No active hostel allocations found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-4">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800" @disabled(count($hostel_students) === 0)>
                        Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

