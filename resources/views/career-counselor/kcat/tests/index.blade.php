<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">KCAT Tests</h2>
            <a href="{{ route('career-counselor.kcat.tests.create') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Create Test</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl py-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-blue-50 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">
                    <tr><th class="px-4 py-3">Title</th><th class="px-4 py-3">Grades</th><th class="px-4 py-3">Mode</th><th class="px-4 py-3">Questions</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tests as $test)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $test->title }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $test->grade_from ?? '-' }} to {{ $test->grade_to ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $test->is_adaptive_enabled ? 'Adaptive' : 'Fixed' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $test->questions_count }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ ucfirst($test->status) }}</td>
                            <td class="px-4 py-3 text-right"><a class="font-semibold text-blue-700" href="{{ route('career-counselor.kcat.tests.show', $test) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No KCAT tests created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tests->links() }}</div>
    </div>
</x-app-layout>
