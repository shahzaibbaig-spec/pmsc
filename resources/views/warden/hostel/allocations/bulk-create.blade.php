<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Bulk Allocate Students</h2>
                <p class="mt-1 text-sm text-slate-500">Assign multiple students to a hostel room in one action.</p>
            </div>
            <a href="{{ route('warden.hostel.allocations.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Allocations
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="bulkAllocationForm()">
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

            <form method="POST" action="{{ route('warden.hostel.allocations.bulk.store') }}" class="space-y-6">
                @csrf

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="md:col-span-2">
                            <label for="hostel_room_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Room</label>
                            <select id="hostel_room_id" name="hostel_room_id" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                <option value="">Select room</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room['id'] }}" @selected((int) old('hostel_room_id') === (int) $room['id'])>
                                        {{ $room['name'] }} | Capacity: {{ $room['capacity'] }} | Occupied: {{ $room['occupied_beds'] }} | Available: {{ $room['available_beds'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="allocated_from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Allocated From</label>
                            <input id="allocated_from" type="date" name="allocated_from" value="{{ old('allocated_from', now()->toDateString()) }}" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>
                        <div>
                            <label for="remarks" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Remarks</label>
                            <input id="remarks" type="text" name="remarks" value="{{ old('remarks') }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" maxlength="1000" placeholder="Optional">
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Select Students</h3>
                                <p class="text-xs text-slate-500" x-text="selectedCount + ' student(s) selected'"></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="student_search" type="text" x-model="search" placeholder="Search by name, code, class" class="block min-h-10 w-72 rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                <button type="button" @click="toggleSelectAllVisible()" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Select All Visible
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Select</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Admission #</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($students as $student)
                                    @php
                                        $line = strtolower($student['name'].' '.$student['student_code'].' '.$student['class_name']);
                                    @endphp
                                    <tr x-show="isVisible(@js($line))">
                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            <input type="checkbox" :name="'student_ids[]'" value="{{ $student['id'] }}" x-model="selected" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                        </td>
                                        <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $student['name'] }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $student['student_code'] }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $student['class_name'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No students available for bulk allocation.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Allocate Selected Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bulkAllocationForm() {
            return {
                search: '',
                selected: @js(array_map('strval', (array) old('student_ids', []))),
                visibleIds: @js(array_map(fn($s) => (string) $s['id'], $students)),
                isVisible(searchLine) {
                    const q = (this.search || '').toLowerCase().trim();
                    return q === '' || searchLine.includes(q);
                },
                toggleSelectAllVisible() {
                    const checkboxes = Array.from(document.querySelectorAll('input[name="student_ids[]"]')).filter((el) => {
                        const row = el.closest('tr');
                        return row && row.style.display !== 'none';
                    });

                    const allVisibleSelected = checkboxes.every((el) => this.selected.includes(el.value));
                    if (allVisibleSelected) {
                        this.selected = this.selected.filter((id) => !checkboxes.some((el) => el.value === id));
                        return;
                    }

                    const ids = new Set(this.selected);
                    checkboxes.forEach((el) => ids.add(el.value));
                    this.selected = Array.from(ids);
                },
                get selectedCount() {
                    return this.selected.length;
                },
            };
        }
    </script>
</x-app-layout>

