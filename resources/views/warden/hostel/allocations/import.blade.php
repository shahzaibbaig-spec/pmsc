<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Import Hostel Room List</h2></x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 p-4 text-rose-700"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form action="{{ route('warden.hostel.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-3 rounded-xl bg-white p-6 shadow-sm">
            @csrf
            <label for="workbook" class="block font-semibold">Room-wise student list (.xlsx)</label>
            <p class="text-sm text-slate-600">Upload the list to review rooms and student matches. Paired labels such as J 3-4 remain one room group. Nothing is saved until you import below.</p>
            <input id="workbook" name="workbook" type="file" accept=".xlsx" required>
            <button class="rounded-xl bg-slate-900 px-4 py-3 text-white">Review List</button>
        </form>
        @if ($preview)
            <form action="{{ route('warden.hostel.import.store') }}" method="POST" class="space-y-6">
                @csrf
                <section class="space-y-3 rounded-xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-slate-600">Only unique name and birth date matches are selected automatically. Review each match; enter a student record ID to resolve unmatched names, or leave blank to skip. Existing assignments in this hostel will move to the selected room with history preserved. Students in another hostel cannot be moved here.</p>
                    <p class="text-sm text-slate-600">Select each room's floor, especially rooms under the combined “1st &amp; 2nd FLOOR” heading. New room capacity starts at the list count; adjust it to the actual capacity. Existing rooms keep their capacity; use Edit Room to change it.</p>
                    <label class="block">Hostel
                        <select name="hostel_id" required class="mt-1 block rounded-lg border-slate-300">
                            @foreach ($hostels as $hostel)<option value="{{ $hostel->id }}" @selected((int) old('hostel_id') === $hostel->id)>{{ $hostel->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="block">Allocation date <input type="date" name="allocated_from" value="{{ old('allocated_from', now()->toDateString()) }}" required class="block rounded-lg border-slate-300"></label>
                </section>
                <datalist id="student-records">@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }} — {{ $student->student_id }}</option>@endforeach</datalist>
                @foreach ($preview as $i => $room)
                    <section class="space-y-3 rounded-xl bg-white p-6 shadow-sm">
                        <h3 class="font-semibold">{{ $room['name'] }} · {{ count($room['students']) }} students</h3>
                        <p class="text-sm text-slate-500">Source: {{ $room['floor_label'] }}</p>
                        <div class="flex flex-wrap gap-4">
                            <label>Floor (0 = ground)<input type="number" min="0" max="99" name="rooms[{{ $i }}][floor]" value="{{ old("rooms.$i.floor", $room['floor']) }}" required class="block w-40 rounded-lg border-slate-300"></label>
                            <label>Capacity<input type="number" min="1" max="1000" name="rooms[{{ $i }}][capacity]" value="{{ old("rooms.$i.capacity", max(1, count($room['students']))) }}" required class="block w-40 rounded-lg border-slate-300"></label>
                        </div>
                        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                            <thead><tr><th class="p-2">Spreadsheet student</th><th class="p-2">Birth date</th><th class="p-2">Student record ID</th></tr></thead>
                            <tbody>@foreach ($room['students'] as $j => $row)
                                <tr class="border-t border-slate-100"><td class="p-2">{{ $row['name'] }}</td><td class="p-2">{{ $row['dob'] ?? 'Not provided' }}</td>
                                    <td class="p-2"><input aria-label="Student record for {{ $row['name'] }}" list="student-records" type="text" inputmode="numeric" name="rooms[{{ $i }}][students][{{ $j }}]" value="{{ old("rooms.$i.students.$j", $row['student_id']) }}" placeholder="Skip / select record" class="rounded-lg border-slate-300">
                                    @if ($row['student_id'])<span class="ml-2 text-emerald-700">{{ $students->firstWhere('id', $row['student_id'])?->name }}</span>@else<span class="ml-2 text-amber-700">Match required</span>@endif</td>
                                </tr>
                            @endforeach</tbody>
                        </table></div>
                    </section>
                @endforeach
                <button class="rounded-xl bg-slate-900 px-6 py-3 font-semibold text-white">Import Rooms and Selected Allocations</button>
            </form>
        @endif
    </div>
</x-app-layout>
