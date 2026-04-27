<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Career Counselor Profile</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl py-8">
        <form method="POST" action="{{ route('career-counselor.profiles.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="student_id" value="{{ old('student_id', $student?->id) }}">

            <div class="rounded-xl bg-blue-50 p-4">
                <p class="text-sm font-semibold text-blue-900">{{ $student?->name ?? 'Select a student from dashboard search' }}</p>
                <p class="text-xs text-blue-700">{{ $student?->student_id }} {{ $student ? '· '.trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')) : '' }}</p>
                @error('student_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                    <div>
                        <label class="block text-sm font-semibold text-slate-700" for="{{ $field }}">{{ $label }}</label>
                        <textarea id="{{ $field }}" name="{{ $field }}" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old($field, $profile?->{$field}) }}</textarea>
                        @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('career-counselor.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</a>
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Career Profile</button>
            </div>
        </form>
    </div>
</x-app-layout>
