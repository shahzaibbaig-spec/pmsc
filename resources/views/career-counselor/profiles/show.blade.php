<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Career Counselor Profile</h2>
                <p class="text-sm text-slate-500">{{ $profile->student?->name }} · {{ $profile->session }}</p>
            </div>
            <button onclick="window.print()" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Print</button>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">{{ $profile->student?->name }}</h3>
            <p class="text-sm text-slate-500">{{ $profile->student?->student_id }} · {{ trim(($profile->student?->classRoom?->name ?? '').' '.($profile->student?->classRoom?->section ?? '')) }} · Father: {{ $profile->student?->father_name ?? '-' }}</p>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ([
                'strengths' => 'Strengths',
                'weaknesses' => 'Weaknesses',
                'interests' => 'Interests',
                'preferred_subjects' => 'Preferred Subjects',
                'career_goals' => 'Career Goals',
                'parent_expectations' => 'Parent Expectations',
                'recommended_career_paths' => 'Recommended Career Paths',
                'counselor_notes' => 'Counselor Notes',
            ] as $field => $label)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $label }}</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $profile->{$field} ?: '-' }}</p>
                </article>
            @endforeach
        </section>
    </div>
</x-app-layout>
