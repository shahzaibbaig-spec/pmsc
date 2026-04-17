<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Create Hostel Leave Request</h2>
                <p class="mt-1 text-sm text-slate-500">Record student leave permission with date/time and reason.</p>
            </div>
            <a href="{{ route('warden.hostel.leaves.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Leave List
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

            @if ($errors->has('leave'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('leave') }}
                </div>
            @endif

            <form method="POST" action="{{ route('warden.hostel.leaves.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ studentSearch: '' }">
                @csrf

                <div>
                    <label for="student_search" class="mb-1 block text-sm font-medium text-slate-700">Search Student</label>
                    <input
                        id="student_search"
                        type="text"
                        x-model="studentSearch"
                        @input="
                            const q = studentSearch.toLowerCase();
                            Array.from($refs.studentSelect.options).forEach((option) => {
                                if (!option.value) return;
                                option.hidden = !option.text.toLowerCase().includes(q);
                            });
                        "
                        placeholder="Search by name or admission no"
                        class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >
                </div>

                <div>
                    <label for="student_id" class="mb-1 block text-sm font-medium text-slate-700">Student</label>
                    <select id="student_id" name="student_id" x-ref="studentSelect" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">Select student</option>
                        @foreach ($students as $student)
                            <option value="{{ $student['id'] }}" @selected((int) old('student_id') === (int) $student['id'])>
                                {{ $student['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="hostel_room_id" class="mb-1 block text-sm font-medium text-slate-700">Hostel Room (Optional)</label>
                    <select id="hostel_room_id" name="hostel_room_id" class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">Auto-detect from active allocation</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room['id'] }}" @selected((int) old('hostel_room_id') === (int) $room['id'])>{{ $room['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="leave_from" class="mb-1 block text-sm font-medium text-slate-700">Leave From</label>
                        <input id="leave_from" type="datetime-local" name="leave_from" value="{{ old('leave_from', now()->format('Y-m-d\TH:i')) }}" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="leave_to" class="mb-1 block text-sm font-medium text-slate-700">Leave To</label>
                        <input id="leave_to" type="datetime-local" name="leave_to" value="{{ old('leave_to', now()->addDay()->format('Y-m-d\TH:i')) }}" required class="block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>

                <div>
                    <label for="reason" class="mb-1 block text-sm font-medium text-slate-700">Reason</label>
                    <textarea id="reason" name="reason" rows="4" required class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('reason') }}</textarea>
                </div>

                <div>
                    <label for="remarks" class="mb-1 block text-sm font-medium text-slate-700">Remarks (Optional)</label>
                    <textarea id="remarks" name="remarks" rows="3" class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('remarks') }}</textarea>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Save Leave Request
                    </button>
                    <a href="{{ route('warden.hostel.leaves.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

