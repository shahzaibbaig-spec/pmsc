<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">New Career Counselor Session</h2></x-slot>

    <div class="mx-auto max-w-5xl py-8">
        <form method="POST" action="{{ route('career-counselor.sessions.store') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="student_id" value="{{ old('student_id', $student?->id) }}">
            <div class="rounded-xl bg-blue-50 p-4">
                <p class="text-sm font-semibold text-blue-900">{{ $student?->name ?? 'Select a student from dashboard search' }}</p>
                <p class="text-xs text-blue-700">{{ $student?->student_id }} {{ $student ? '· '.trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')) : '' }}</p>
                @error('student_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-slate-700" for="counseling_date">Counseling Date</label>
                    <input id="counseling_date" type="date" name="counseling_date" value="{{ old('counseling_date', now()->toDateString()) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('counseling_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700" for="discussion_topic">Discussion Topic</label>
                    <input id="discussion_topic" name="discussion_topic" value="{{ old('discussion_topic') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                @foreach ([
                    'student_interests' => 'Student Interests',
                    'academic_concerns' => 'Academic Concerns',
                    'recommended_subjects' => 'Recommended Subjects',
                    'recommended_career_path' => 'Recommended Career Path',
                    'counselor_advice' => 'Counselor Advice',
                    'private_notes' => 'Private Notes',
                ] as $field => $label)
                    <div>
                        <label class="block text-sm font-semibold text-slate-700" for="{{ $field }}">{{ $label }}</label>
                        <textarea id="{{ $field }}" name="{{ $field }}" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old($field) }}</textarea>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-4 rounded-xl bg-blue-50 p-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-slate-700" for="visibility">Visibility</label>
                    <select id="visibility" name="visibility" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                        @foreach(['private' => 'Private', 'student' => 'Student', 'parent' => 'Parent', 'student_parent' => 'Student and Parent'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('visibility', 'private') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700" for="public_summary">Public Summary</label>
                    <textarea id="public_summary" name="public_summary" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">{{ old('public_summary') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('career-counselor.dashboard') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</a>
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Session</button>
            </div>
        </form>
    </div>
</x-app-layout>
