<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Warden Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Read-only monitoring for diary, discipline, records, and hostel workflow operations.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('warden.dashboard') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                        <input
                            id="date"
                            type="date"
                            name="date"
                            value="{{ $summary['date'] }}"
                            class="mt-1 block min-h-11 rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                    </div>
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Refresh
                    </button>
                </form>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diary Entries (Date)</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) ($summary['total_daily_diary_entries_today'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Discipline Reports</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) ($summary['total_discipline_reports'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Open Discipline Reports</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-800">{{ number_format((int) ($summary['open_discipline_reports'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Total Students</p>
                    <p class="mt-2 text-2xl font-semibold text-indigo-800">{{ number_format((int) ($summary['total_students'] ?? 0)) }}</p>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Daily Reports (Date)</p>
                    <p class="mt-2 text-2xl font-semibold text-blue-800">{{ number_format((int) ($summary['warden_reports_today'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Present (Daily Report)</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ number_format((int) ($summary['warden_present_today'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Absent (Daily Report)</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-800">{{ number_format((int) ($summary['warden_absent_today'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Discipline Incidents (Date)</p>
                    <p class="mt-2 text-2xl font-semibold text-orange-800">{{ number_format((int) ($summary['warden_discipline_incidents_today'] ?? 0)) }}</p>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Health Cases (Date)</p>
                    <p class="mt-2 text-2xl font-semibold text-cyan-800">{{ number_format((int) ($summary['warden_health_cases_today'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hostel Rooms</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) ($summary['total_hostel_rooms'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Active Room Allocations</p>
                    <p class="mt-2 text-2xl font-semibold text-cyan-800">{{ number_format((int) ($summary['active_room_allocations'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Pending Leave Requests</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-800">{{ number_format((int) ($summary['pending_hostel_leave_requests'] ?? 0)) }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Night Attendance Marked (Date)</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ number_format((int) ($summary['night_attendance_marked_today'] ?? 0)) }}</p>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <a href="{{ route('warden.daily-diary.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-900">Daily Diary</h3>
                    <p class="mt-1 text-sm text-slate-500">Browse all teacher diary postings by class, subject, and date.</p>
                </a>
                <a href="{{ route('warden.discipline-reports.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-900">Discipline Reports</h3>
                    <p class="mt-1 text-sm text-slate-500">Review student incidents and case status updates.</p>
                </a>
                <a href="{{ route('warden.students.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-900">Students / Educational Records</h3>
                    <p class="mt-1 text-sm text-slate-500">Open student profiles for attendance and academic history.</p>
                </a>
                <a href="{{ route('warden.daily-reports.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-900">Warden Daily Reports</h3>
                    <p class="mt-1 text-sm text-slate-500">Record and review attendance, discipline, and health logs by day.</p>
                </a>
                <a href="{{ route('warden.hostel.rooms.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-900">Hostel Rooms</h3>
                    <p class="mt-1 text-sm text-slate-500">Manage room capacity, floor mapping, and active room status.</p>
                </a>
                <a href="{{ route('warden.hostel.allocations.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-900">Room Allocations</h3>
                    <p class="mt-1 text-sm text-slate-500">Allocate students, shift rooms, and manage active hostel placements.</p>
                </a>
                <a href="{{ route('warden.hostel.leaves.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-900">Hostel Leave & Night Attendance</h3>
                    <p class="mt-1 text-sm text-slate-500">Manage leave approvals and monitor nightly hostel attendance.</p>
                </a>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-base font-semibold text-slate-900">Recent Discipline Cases</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Incident</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse (($summary['recent_discipline_cases'] ?? []) as $case)
                                <tr>
                                    <td class="px-4 py-4 text-sm text-slate-900">
                                        <p class="font-semibold">{{ $case['student_name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $case['student_code'] }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $case['class_name'] ?: '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        {{ ! empty($case['complaint_date']) ? \Illuminate\Support\Carbon::parse($case['complaint_date'])->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ ucfirst((string) $case['status']) }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $case['description_preview'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No discipline cases found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
