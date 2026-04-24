<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Create Warden Daily Report</h2>
                <p class="mt-1 text-sm text-slate-500">Record attendance, discipline, and health logs for hostel students.</p>
            </div>
            <a href="{{ route('warden.daily-reports.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Reports
            </a>
        </div>
    </x-slot>

    @php
        $studentOptions = $students->map(fn ($student) => [
            'id' => (int) $student->id,
            'name' => (string) $student->name,
            'code' => (string) $student->student_id,
            'class_name' => trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? '')),
        ])->values();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="dailyReportForm()">
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

            @if ($errors->has('daily_report'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('daily_report') }}
                </div>
            @endif

            <form method="POST" action="{{ route('warden.daily-reports.store') }}" class="space-y-6">
                @csrf

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="report_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Report Date</label>
                            <input id="report_date" type="date" name="report_date" value="{{ old('report_date', $reportDate) }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">General Notes</label>
                            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" placeholder="Optional day summary...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h3 class="text-base font-semibold text-slate-900">Night Attendance</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($students as $index => $student)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-slate-900">
                                            <p class="font-semibold">{{ $student->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $student->student_id }} | {{ $student->classRoom?->name }} {{ $student->classRoom?->section }}</p>
                                            <input type="hidden" name="attendance[{{ $index }}][student_id]" value="{{ $student->id }}">
                                        </td>
                                        <td class="px-4 py-4">
                                            <select name="attendance[{{ $index }}][status]" class="block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                                                @foreach (['present' => 'Present', 'absent' => 'Absent', 'on_leave' => 'On Leave'] as $key => $label)
                                                    <option value="{{ $key }}" @selected(old("attendance.$index.status", 'present') === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-4 py-4">
                                            <input type="text" name="attendance[{{ $index }}][remarks]" value="{{ old("attendance.$index.remarks") }}" class="block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" maxlength="1000">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">No students found for this hostel.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">Discipline Log</h3>
                        <button type="button" @click="addDisciplineRow()" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Add Entry
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(row, index) in disciplineRows" :key="'discipline-'+index">
                            <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-5">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Student</label>
                                    <select :name="`discipline[${index}][student_id]`" x-model="row.student_id" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                        <option value="">Select student</option>
                                        <template x-for="student in students" :key="'discipline-student-'+student.id">
                                            <option :value="student.id" x-text="`${student.name} (${student.code})`"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Issue Type</label>
                                    <input type="text" :name="`discipline[${index}][issue_type]`" x-model="row.issue_type" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</label>
                                    <select :name="`discipline[${index}][severity]`" x-model="row.severity" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                        <option value="">Select severity</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Description</label>
                                    <textarea :name="`discipline[${index}][description]`" x-model="row.description" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Action Taken</label>
                                    <textarea :name="`discipline[${index}][action_taken]`" x-model="row.action_taken" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
                                </div>
                                <div class="flex items-end justify-end">
                                    <button type="button" @click="removeDisciplineRow(index)" class="inline-flex min-h-10 items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">Health Log</h3>
                        <button type="button" @click="addHealthRow()" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Add Entry
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(row, index) in healthRows" :key="'health-'+index">
                            <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-6">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Student</label>
                                    <select :name="`health[${index}][student_id]`" x-model="row.student_id" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                        <option value="">Select student</option>
                                        <template x-for="student in students" :key="'health-student-'+student.id">
                                            <option :value="student.id" x-text="`${student.name} (${student.code})`"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Condition</label>
                                    <textarea :name="`health[${index}][condition]`" x-model="row.condition" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Temperature</label>
                                    <input type="number" step="0.1" :name="`health[${index}][temperature]`" x-model="row.temperature" class="mt-1 block min-h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Medication</label>
                                    <textarea :name="`health[${index}][medication]`" x-model="row.medication" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="hidden" :name="`health[${index}][doctor_visit]`" value="0">
                                    <input type="checkbox" :name="`health[${index}][doctor_visit]`" value="1" x-model="row.doctor_visit" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                    <span class="text-sm text-slate-700">Doctor Visit</span>
                                </div>
                                <div class="md:col-span-5"></div>
                                <div class="flex items-end justify-end">
                                    <button type="button" @click="removeHealthRow(index)" class="inline-flex min-h-10 items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Save Daily Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function dailyReportForm() {
            return {
                students: @js($studentOptions),
                disciplineRows: @js(old('discipline', [['student_id' => '', 'issue_type' => '', 'severity' => '', 'description' => '', 'action_taken' => '']])),
                healthRows: @js(old('health', [['student_id' => '', 'condition' => '', 'temperature' => '', 'medication' => '', 'doctor_visit' => false]])),
                addDisciplineRow() {
                    this.disciplineRows.push({
                        student_id: '',
                        issue_type: '',
                        severity: '',
                        description: '',
                        action_taken: ''
                    });
                },
                removeDisciplineRow(index) {
                    this.disciplineRows.splice(index, 1);
                },
                addHealthRow() {
                    this.healthRows.push({
                        student_id: '',
                        condition: '',
                        temperature: '',
                        medication: '',
                        doctor_visit: false
                    });
                },
                removeHealthRow(index) {
                    this.healthRows.splice(index, 1);
                }
            }
        }
    </script>
</x-app-layout>

