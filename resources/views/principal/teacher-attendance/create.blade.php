<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Add Teacher Attendance</h2>
                <p class="mt-1 text-sm text-slate-500">Create or override a teacher attendance record for a specific date.</p>
            </div>
            <a href="{{ route('principal.teacher-attendance.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Attendance
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="list-disc space-y-1 ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('principal.teacher-attendance.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="teacher_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</label>
                        <select id="teacher_id" name="teacher_id" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">Select teacher</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((string) old('teacher_id') === (string) $teacher->id)>
                                    {{ $teacher->user?->name ?? ('Teacher '.$teacher->teacher_id) }} ({{ $teacher->teacher_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="attendance_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Attendance Date</label>
                            <input id="attendance_date" type="date" name="attendance_date" required value="{{ old('attendance_date', now()->toDateString()) }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>

                        <div>
                            <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                            <select id="status" name="status" required class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                @foreach (['present' => 'Present', 'absent' => 'Absent', 'leave' => 'Leave', 'late' => 'Late'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'present') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="remarks" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Remarks (Optional)</label>
                        <textarea id="remarks" name="remarks" rows="4" maxlength="1000" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="flex items-center gap-3 border-t border-slate-100 pt-4">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Save Attendance
                        </button>
                        <a href="{{ route('principal.teacher-attendance.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>

