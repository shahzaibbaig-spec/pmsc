<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Class Discipline Reports</h2>
                <p class="mt-1 text-sm text-slate-500">Review discipline reports submitted for students you teach.</p>
            </div>
            <a href="{{ route('teacher.discipline-reports.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                New Report
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('teacher.discipline-reports.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-6">
                <div>
                    <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                    <select id="session" name="session" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        @foreach ($sessions as $session)
                            <option value="{{ $session }}" @selected(($filters['session'] ?? null) === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                    <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div>
                    <label for="class_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                    <select id="class_id" name="class_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Classes</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class['id'] }}" @selected((int) ($filters['class_id'] ?? 0) === (int) $class['id'])>{{ $class['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="subject_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                    <select id="subject_id" name="subject_id" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Subjects</option>
                    </select>
                </div>
                <div>
                    <label for="issue_type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Issue</label>
                    <select id="issue_type" name="issue_type" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All Issues</option>
                        @foreach ($issue_options as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['issue_type'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="severity" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Severity</label>
                    <select id="severity" name="severity" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All</option>
                        @foreach ($severity_options as $severity)
                            <option value="{{ $severity }}" @selected(($filters['severity'] ?? null) === $severity)>{{ ucfirst($severity) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        <option value="">All</option>
                        @foreach ($status_options as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="per_page" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Rows</label>
                    <select id="per_page" name="per_page" class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm">
                        @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-6 flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                    <a href="{{ route('teacher.discipline-reports.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-blue-700">Today</p><p class="mt-2 text-2xl font-semibold text-blue-800">{{ number_format((int) ($cards['today_reports'] ?? 0)) }}</p></article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-amber-700">Open</p><p class="mt-2 text-2xl font-semibold text-amber-800">{{ number_format((int) ($cards['open_reports'] ?? 0)) }}</p></article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-indigo-700">Repeated</p><p class="mt-2 text-2xl font-semibold text-indigo-800">{{ number_format((int) ($cards['repeated_reports'] ?? 0)) }}</p></article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-emerald-700">Resolved</p><p class="mt-2 text-2xl font-semibold text-emerald-800">{{ number_format((int) ($cards['resolved_reports'] ?? 0)) }}</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-slate-500">Most Common Today</p><p class="mt-2 text-sm font-semibold text-slate-900">{{ $cards['most_common_issue_today'] ?? 'N/A' }}</p></article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class / Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Issue</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($reports as $report)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ optional($report->report_date)->format('d M Y') ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-900">
                                    <p class="font-semibold">{{ $report->student?->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $report->student?->student_id }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    {{ trim(($report->classRoom?->name ?? '').' '.($report->classRoom?->section ?? '')) ?: '-' }}
                                    <p class="text-xs text-slate-500">{{ $report->subject?->name ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    <p>{{ $report->issue_label }}</p>
                                    <p class="text-xs text-slate-500">{{ ucfirst($report->severity) }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit($report->auto_message, 85) }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $report->status === 'resolved' ? 'bg-emerald-100 text-emerald-700' : ($report->status === 'acknowledged' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm"><a href="{{ route('teacher.discipline-reports.show', $report) }}" class="font-semibold text-blue-700 hover:text-blue-800">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No discipline reports found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reports->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $reports->links() }}
                </div>
            @endif
        </section>
    </div>

    <script>
        (() => {
            const subjectsByClass = @json($subjects_by_class ?? []);
            const classInput = document.getElementById('class_id');
            const subjectInput = document.getElementById('subject_id');
            const selectedSubject = @json((int) ($filters['subject_id'] ?? 0));

            if (!classInput || !subjectInput) {
                return;
            }

            const hydrateSubjects = () => {
                const classId = Number(classInput.value || 0);
                const subjects = classId > 0 ? (subjectsByClass[classId] || []) : [];
                const options = ['<option value="">All Subjects</option>'];
                for (const subject of subjects) {
                    const selected = Number(subject.id) === selectedSubject ? ' selected' : '';
                    options.push(`<option value="${subject.id}"${selected}>${window.NSMS.escapeHtml(subject.name)}</option>`);
                }
                subjectInput.innerHTML = options.join('');
                if (classId <= 0 && selectedSubject > 0) {
                    subjectInput.innerHTML += `<option value="${selectedSubject}" selected>Selected Subject</option>`;
                }
            };

            classInput.addEventListener('change', hydrateSubjects);
            hydrateSubjects();
        })();
    </script>
</x-app-layout>

