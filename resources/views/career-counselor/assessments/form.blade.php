<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Career Assessment</h2></x-slot>
    <div class="mx-auto max-w-5xl py-8">
        <form method="POST" action="{{ route('career-counselor.assessments.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="student_id" value="{{ old('student_id', $student?->id) }}">
            <div class="rounded-xl bg-blue-50 p-4 text-sm text-blue-900">{{ $student?->name ?? 'Select a student from dashboard search' }} {{ $student ? '| '.$student->student_id : '' }}</div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <input type="date" name="assessment_date" value="{{ old('assessment_date', now()->toDateString()) }}" class="rounded-xl border-slate-300 text-sm">
                <input name="title" value="{{ old('title') }}" placeholder="Title" class="rounded-xl border-slate-300 text-sm">
                <input name="recommended_stream" value="{{ old('recommended_stream') }}" placeholder="Recommended stream override" class="rounded-xl border-slate-300 text-sm">
                <input name="alternative_stream" value="{{ old('alternative_stream') }}" placeholder="Alternative stream override" class="rounded-xl border-slate-300 text-sm">
            </div>
            <textarea name="overall_summary" rows="3" placeholder="Overall summary" class="w-full rounded-xl border-slate-300 text-sm">{{ old('overall_summary') }}</textarea>
            <textarea name="suggested_subjects" rows="3" placeholder="Suggested subjects" class="w-full rounded-xl border-slate-300 text-sm">{{ old('suggested_subjects') }}</textarea>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach($categories as $category)
                    <div class="rounded-xl border border-slate-200 p-3">
                        <label class="text-sm font-semibold text-slate-700">{{ str_replace('_', ' ', ucfirst($category)) }}</label>
                        <input type="number" name="scores[{{ $category }}]" min="0" max="100" value="{{ old('scores.'.$category, 0) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <textarea name="remarks[{{ $category }}]" rows="2" placeholder="Remarks" class="mt-2 w-full rounded-lg border-slate-300 text-sm">{{ old('remarks.'.$category) }}</textarea>
                    </div>
                @endforeach
            </div>
            <div class="text-right"><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save Assessment</button></div>
        </form>
    </div>
</x-app-layout>
