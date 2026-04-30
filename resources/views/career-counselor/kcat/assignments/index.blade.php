<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">KCAT Assignments</h2>
            <a href="{{ route('career-counselor.kcat.assignments.create') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Assign KCAT</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl py-8">
        @if (isset($attempts))
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-blue-50 text-left text-xs font-semibold uppercase tracking-wide text-blue-700"><tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">Test</th><th class="px-4 py-3">Score</th><th class="px-4 py-3">Band</th><th class="px-4 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($attempts as $attempt)
                            <tr><td class="px-4 py-3">{{ $attempt->student?->name }}</td><td class="px-4 py-3">{{ $attempt->test?->title }}</td><td class="px-4 py-3">{{ $attempt->percentage ?? 0 }}%</td><td class="px-4 py-3">{{ str_replace('_', ' ', $attempt->band ?? '-') }}</td><td class="px-4 py-3 text-right"><a href="{{ route('career-counselor.kcat.reports.show', $attempt) }}" class="font-semibold text-blue-700">Report</a></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $attempts->links() }}</div>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-blue-50 text-left text-xs font-semibold uppercase tracking-wide text-blue-700"><tr><th class="px-4 py-3">Test</th><th class="px-4 py-3">Assigned To</th><th class="px-4 py-3">Session</th><th class="px-4 py-3">Due</th><th class="px-4 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($assignments as $assignment)
                            <tr><td class="px-4 py-3">{{ $assignment->test?->title }}</td><td class="px-4 py-3">{{ $assignment->student?->name ?? trim(($assignment->classRoom?->name ?? '').' '.($assignment->section ?? $assignment->classRoom?->section ?? '')) }}</td><td class="px-4 py-3">{{ $assignment->session }}</td><td class="px-4 py-3">{{ optional($assignment->due_date)->format('d M Y') ?? '-' }}</td><td class="px-4 py-3">{{ ucfirst($assignment->status) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No KCAT assignments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $assignments->links() }}</div>
        @endif
    </div>
</x-app-layout>
