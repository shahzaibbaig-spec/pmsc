<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Career Counselor Session</h2>
            <button onclick="window.print()" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Print</button>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">{{ $session->student?->name }}</h3>
            <p class="text-sm text-slate-500">{{ $session->student?->student_id }} · {{ trim(($session->student?->classRoom?->name ?? '').' '.($session->student?->classRoom?->section ?? '')) }} · {{ $session->counseling_date?->format('d M Y') }}</p>
        </section>
        <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ([
                'discussion_topic' => 'Discussion Topic',
                'student_interests' => 'Student Interests',
                'academic_concerns' => 'Academic Concerns',
                'recommended_subjects' => 'Recommended Subjects',
                'recommended_career_path' => 'Recommended Career Path',
                'counselor_advice' => 'Counselor Advice',
                'private_notes' => 'Private Notes',
            ] as $field => $label)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">{{ $label }}</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $session->{$field} ?: '-' }}</p>
                </article>
            @endforeach
        </section>
        @if(request()->routeIs('career-counselor.*'))
            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <form method="POST" action="{{ route('career-counselor.urgent.mark', $session) }}" class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm">
                    @csrf
                    <h3 class="font-semibold text-slate-900">Urgent Guidance</h3>
                    <textarea name="urgent_reason" rows="3" placeholder="Urgent reason" class="mt-3 w-full rounded-xl border-slate-300 text-sm">{{ $session->urgent_reason }}</textarea>
                    <button class="mt-3 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Mark Urgent</button>
                </form>
                <form method="POST" action="{{ route('career-counselor.visibility.update', $session) }}" class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
                    @csrf
                    @method('PUT')
                    <h3 class="font-semibold text-slate-900">Student/Parent Visibility</h3>
                    <select name="visibility" class="mt-3 w-full rounded-xl border-slate-300 text-sm">
                        @foreach(['private' => 'Private', 'student' => 'Student', 'parent' => 'Parent', 'student_parent' => 'Student and Parent'] as $value => $label)
                            <option value="{{ $value }}" @selected($session->visibility === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <textarea name="public_summary" rows="3" placeholder="Public summary" class="mt-3 w-full rounded-xl border-slate-300 text-sm">{{ $session->public_summary }}</textarea>
                    <button class="mt-3 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save Visibility</button>
                </form>
            </section>
        @endif
    </div>
</x-app-layout>
