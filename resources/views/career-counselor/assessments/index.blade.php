<x-app-layout>
    <x-slot name="header"><div class="flex justify-between"><h2 class="text-xl font-semibold text-slate-900">Career Assessments</h2><a href="{{ route('career-counselor.assessments.create') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">New Assessment</a></div></x-slot>
    <div class="mx-auto max-w-7xl py-8">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-blue-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Student</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Date</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Stream</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assessments as $assessment)
                        <tr><td class="px-4 py-3 text-sm">{{ $assessment->student?->name }}<p class="text-xs text-slate-500">{{ $assessment->student?->student_id }}</p></td><td class="px-4 py-3 text-sm">{{ $assessment->assessment_date?->format('d M Y') }}</td><td class="px-4 py-3 text-sm">{{ $assessment->recommended_stream ?: '-' }}</td><td class="px-4 py-3 text-right"><a class="text-sm font-semibold text-blue-700" href="{{ route('career-counselor.assessments.show', $assessment) }}">View</a></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No assessments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $assessments->links() }}</div>
        </div>
    </div>
</x-app-layout>
