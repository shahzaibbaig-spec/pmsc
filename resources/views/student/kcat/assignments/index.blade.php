<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">My KCAT Assignments</h2></x-slot>

    <div class="mx-auto max-w-5xl py-8">
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif
        <div class="space-y-4">
            @forelse ($assignments as $assignment)
                <article class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $assignment->test?->title }}</h3>
                        <p class="text-sm text-slate-500">Due: {{ optional($assignment->due_date)->format('d M Y') ?? '-' }} | {{ ucfirst($assignment->status) }} | {{ $assignment->test?->is_adaptive_enabled ? 'Adaptive' : 'Fixed' }}</p>
                    </div>
                    @if ($assignment->status !== 'completed')
                        <form method="POST" action="{{ route('student.kcat.assignments.start', $assignment) }}">@csrf<button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Start</button></form>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">No KCAT assignments available.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
