<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">Career Counselor Sessions</h2></x-slot>
    <div class="mx-auto max-w-7xl py-8">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-blue-50"><tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Student</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Date</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-blue-700">Career Path</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sessions as $session)
                        <tr>
                            <td class="px-4 py-3 text-sm"><p class="font-semibold text-slate-900">{{ $session->student?->name }}</p><p class="text-xs text-slate-500">{{ $session->student?->student_id }}</p></td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $session->counseling_date?->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $session->recommended_career_path ?: '-' }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('career-counselor.sessions.show', $session) }}" class="text-sm font-semibold text-blue-700">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No Career Counselor sessions recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $sessions->links() }}</div>
        </div>
    </div>
</x-app-layout>
