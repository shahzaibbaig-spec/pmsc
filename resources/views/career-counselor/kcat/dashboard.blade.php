<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">KCAT Dashboard</h2>
                <p class="text-sm text-slate-500">KORT Cognitive Assessment Test workspace.</p>
            </div>
            <a href="{{ route('career-counselor.kcat.tests.create') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">New KCAT Test</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        <section class="grid grid-cols-1 gap-4 md:grid-cols-5">
            @foreach ([
                'Active KCAT Tests' => $activeTests,
                'Assigned Tests' => $assignedTests,
                'Completed Attempts' => $completedAttempts,
                'Average Score' => $averageScore.'%',
                'Students Needing Support' => $needsSupport,
            ] as $label => $value)
                <article class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $value }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <a href="{{ route('career-counselor.kcat.tests.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">Manage Tests</a>
            <a href="{{ route('career-counselor.kcat.assignments.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">Assignments</a>
            <a href="{{ route('career-counselor.kcat.attempts.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">Attempts</a>
            <a href="{{ route('career-counselor.kcat.assignments.create') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">Assign KCAT</a>
            <a href="{{ route('career-counselor.kcat.question-quality.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">Question Quality</a>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-900">Recent KCAT Attempts</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentAttempts as $attempt)
                    <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $attempt->student?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $attempt->test?->title }} | {{ $attempt->percentage ?? 0 }}% | {{ str_replace('_', ' ', $attempt->band ?? '-') }}</p>
                        </div>
                        <a href="{{ route('career-counselor.kcat.reports.show', $attempt) }}" class="text-sm font-semibold text-blue-700">View Report</a>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">No KCAT attempts yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
