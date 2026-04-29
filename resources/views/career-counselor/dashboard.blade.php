<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Career Counselor Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Phase 1 workspace for Grade 7 to Grade 12 student guidance.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
            <label for="careerStudentSearch" class="block text-xs font-semibold uppercase tracking-wide text-blue-700">Grade 7 to Grade 12 Student Search</label>
            <input id="careerStudentSearch" type="text" placeholder="Search by name, admission number, father name, class or section" class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <div id="careerStudentResults" class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white"></div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ([
                'Grade 7 to Grade 12 Students' => $stats['total_students'] ?? 0,
                'Career Profiles This Session' => $stats['total_profiles'] ?? 0,
                'My Counseling Sessions This Session' => $stats['total_sessions'] ?? 0,
            ] as $label => $value)
                <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((int) $value) }}</p>
                </article>
            @endforeach
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900">Recent Career Counselor Sessions</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse (($stats['recent_sessions'] ?? collect()) as $session)
                    <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $session->student?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $session->student?->student_id }} | {{ trim(($session->student?->classRoom?->name ?? '').' '.($session->student?->classRoom?->section ?? '')) }}</p>
                        </div>
                        <a href="{{ route('career-counselor.sessions.show', $session) }}" class="text-sm font-semibold text-blue-700">View</a>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">No Career Counselor sessions recorded yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <script>
        (() => {
            const input = document.getElementById('careerStudentSearch');
            const results = document.getElementById('careerStudentResults');
            const endpoint = @json(route('career-counselor.students.search'));
            const profileUrl = @json(route('career-counselor.profiles.create'));
            const sessionUrl = @json(route('career-counselor.sessions.create'));
            if (!input || !results) return;

            const render = (students) => {
                if (!students.length) {
                    results.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No Grade 7 to Grade 12 students found.</div>';
                    return;
                }

                results.innerHTML = students.map((student) => `
                    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">${window.NSMS.escapeHtml(student.name)}</p>
                            <p class="text-xs text-slate-500">${window.NSMS.escapeHtml(student.admission_number)} | ${window.NSMS.escapeHtml(student.class_section)} | ${window.NSMS.escapeHtml(student.father_name)}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white" href="${profileUrl}?student_id=${student.id}">Career Profile</a>
                            <a class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700" href="${sessionUrl}?student_id=${student.id}">New Session</a>
                            <a class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700" href="{{ route('career-counselor.assessments.create') }}?student_id=${student.id}">Assessment</a>
                            <a class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700" href="{{ route('career-counselor.parent-meetings.create') }}?student_id=${student.id}">Parent Meeting</a>
                        </div>
                    </div>
                `).join('');
            };

            input.addEventListener('input', window.NSMS.debounce(async () => {
                const term = input.value.trim();
                if (term.length < 2) {
                    results.innerHTML = '';
                    return;
                }

                const response = await fetch(`${endpoint}?term=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
                const payload = await response.json();
                render(payload.data || []);
            }, 250));
        })();
    </script>
</x-app-layout>
