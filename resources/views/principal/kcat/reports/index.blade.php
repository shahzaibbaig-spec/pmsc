<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-slate-900">KCAT Reports</h2>
            <div class="flex gap-2">
                <a href="{{ route('principal.kcat.analytics.index') }}" class="rounded-xl border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700">Grade-wise Summary</a>
                <a href="{{ route('principal.kcat.question-quality.index') }}" class="rounded-xl border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700">Question Quality</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 py-8">
        <form method="GET" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-3">
            <input name="student" value="{{ request('student') }}" placeholder="Search student/admission" class="rounded-xl border-slate-300 text-sm">
            <input name="session" value="{{ request('session') }}" placeholder="Session" class="rounded-xl border-slate-300 text-sm">
            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-blue-50 text-left text-xs font-semibold uppercase tracking-wide text-blue-700"><tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">Class</th><th class="px-4 py-3">Test</th><th class="px-4 py-3">Mode</th><th class="px-4 py-3">Score</th><th class="px-4 py-3">Band</th><th class="px-4 py-3">Top Stream</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attempts as $attempt)
                        <tr><td class="px-4 py-3 font-semibold">{{ $attempt->student?->name }}</td><td class="px-4 py-3">{{ trim(($attempt->student?->classRoom?->name ?? '').' '.($attempt->student?->classRoom?->section ?? '')) }}</td><td class="px-4 py-3">{{ $attempt->test?->title }}</td><td class="px-4 py-3">{{ $attempt->is_adaptive ? 'Adaptive' : 'Fixed' }}</td><td class="px-4 py-3">{{ $attempt->percentage ?? 0 }}%</td><td class="px-4 py-3">{{ str_replace('_', ' ', $attempt->band ?? '-') }}</td><td class="px-4 py-3">{{ $attempt->counselor_override_stream ?: $attempt->recommended_stream ?: '-' }}</td><td class="px-4 py-3 text-right"><a href="{{ route('principal.kcat.reports.show', $attempt) }}" class="font-semibold text-blue-700">View</a></td></tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">No KCAT reports found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $attempts->links() }}</div>
    </div>
</x-app-layout>
