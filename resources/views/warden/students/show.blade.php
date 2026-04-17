<x-app-layout>
    @php
        $attendance = $attendance_summary ?? [];
        $academic = $academic_summary ?? [];
        $discipline = $discipline_summary ?? [];
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Student Educational Record</h2>
                <p class="mt-1 text-sm text-slate-500">Read-only academic, attendance, and discipline overview.</p>
            </div>
            <a href="{{ route('warden.students.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Students
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900">{{ $student->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Admission No: {{ $student->student_id }}</p>
                        <div class="mt-4 grid grid-cols-1 gap-2 text-sm text-slate-700 md:grid-cols-2">
                            <p><span class="font-semibold text-slate-900">Father Name:</span> {{ $student->father_name ?: '-' }}</p>
                            <p><span class="font-semibold text-slate-900">Status:</span> {{ ucfirst((string) ($profile['status'] ?? 'inactive')) }}</p>
                            <p><span class="font-semibold text-slate-900">Current Class:</span> {{ $profile['class_name'] ?: '-' }}</p>
                            <p><span class="font-semibold text-slate-900">Session Class:</span> {{ $profile['session_class_name'] ?: '-' }}</p>
                            <p><span class="font-semibold text-slate-900">Session Status:</span> {{ ucfirst(str_replace('_', ' ', (string) ($profile['session_status'] ?? 'n/a'))) }}</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('warden.students.show', $student) }}" class="w-full max-w-xs rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select id="session" name="session" class="mt-2 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected($selected_session === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="mt-3 inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Apply Session
                        </button>
                    </form>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Attendance %</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($attendance['attendance_percentage'] ?? 0), 2) }}%</p>
                    <p class="mt-1 text-xs text-slate-500">Source: {{ $attendance['source'] ?? '-' }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Academic Average</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($academic['average_percentage'] ?? 0), 2) }}%</p>
                </article>
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Current Grade</p>
                    <p class="mt-2 text-2xl font-semibold text-indigo-800">{{ $academic['current_grade'] ?? 'N/A' }}</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Open Discipline Cases</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-800">{{ number_format((int) ($discipline['open'] ?? 0)) }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900">Subject Results</h3>
                    <p class="mt-1 text-xs text-slate-500">Session: {{ $selected_session }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total Marks</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Obtained Marks</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Percentage</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($subject_results as $result)
                                <tr>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $result['subject_name'] }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ number_format((float) $result['total_marks'], 2) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ number_format((float) $result['obtained_marks'], 2) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ number_format((float) $result['percentage'], 2) }}%</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $result['grade'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No subject results found for this session.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h3 class="text-base font-semibold text-slate-900">Recent Results</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Marks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($recent_results as $result)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ optional($result->result_date)->format('d M Y') ?: '-' }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $result->subject?->name ?? '-' }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ number_format((float) $result->obtained_marks, 2) }} / {{ number_format((float) $result->total_marks, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No recent results found for this session.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h3 class="text-base font-semibold text-slate-900">Recent Discipline Notes</h3>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @forelse (($discipline['recent'] ?? []) as $item)
                            <li class="px-4 py-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $item['date'] }} - {{ ucfirst((string) $item['status']) }}</p>
                                <p class="mt-1 text-sm text-slate-700">{{ $item['description'] }}</p>
                            </li>
                        @empty
                            <li class="px-4 py-8 text-center text-sm text-slate-500">No discipline notes found for this session.</li>
                        @endforelse
                    </ul>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
