<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Career Counselor Records</h2>
            <p class="text-sm text-slate-500">Principal/Admin read-only view for Phase 1 Career Counselor records.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        <form method="GET" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-4">
            <input name="student" value="{{ $filters['student'] ?? '' }}" placeholder="Student name/admission/father" class="rounded-xl border-slate-300 text-sm">
            <select name="class_id" class="rounded-xl border-slate-300 text-sm">
                <option value="">All classes</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(($filters['class_id'] ?? '') == $class->id)>{{ trim($class->name.' '.($class->section ?? '')) }}</option>
                @endforeach
            </select>
            <select name="counselor_id" class="rounded-xl border-slate-300 text-sm">
                <option value="">All counselors</option>
                @foreach ($counselors as $counselor)
                    <option value="{{ $counselor->id }}" @selected(($filters['counselor_id'] ?? '') == $counselor->id)>{{ $counselor->name }}</option>
                @endforeach
            </select>
            <input name="session" value="{{ $filters['session'] ?? '' }}" placeholder="Session" class="rounded-xl border-slate-300 text-sm">
            <div class="flex gap-2 md:col-span-4">
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                <a href="{{ route('principal.career-counseling.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset</a>
            </div>
        </form>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <h3 class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-900">Career Profiles</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Career Paths</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($profiles as $profile)
                            <tr>
                                <td class="px-4 py-3 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $profile->student?->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $profile->student?->student_id }} | {{ trim(($profile->student?->classRoom?->name ?? '').' '.($profile->student?->classRoom?->section ?? '')) }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $profile->session }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $profile->recommended_career_paths ?: '-' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('principal.career-profiles.show', $profile) }}" class="text-sm font-semibold text-blue-700">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No Career Counselor profiles found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $profiles->links() }}</div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <h3 class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-900">Counseling Sessions</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Counselor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Career Path</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="px-4 py-3 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $session->student?->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $session->student?->student_id }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $session->counselor?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $session->counseling_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $session->recommended_career_path ?: '-' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('principal.counseling-sessions.show', $session) }}" class="text-sm font-semibold text-blue-700">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No Career Counselor sessions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $sessions->links() }}</div>
        </section>
    </div>
</x-app-layout>
