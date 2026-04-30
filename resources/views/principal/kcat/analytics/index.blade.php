<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-slate-900">KCAT Grade-wise Summary</h2></x-slot>

    <div class="mx-auto max-w-6xl py-8">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-blue-50 text-left text-xs font-semibold uppercase tracking-wide text-blue-700"><tr><th class="px-4 py-3">Class</th><th class="px-4 py-3">Attempts</th><th class="px-4 py-3">Average</th><th class="px-4 py-3">Needs Support</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($summary as $row)
                        <tr><td class="px-4 py-3 font-semibold">{{ $row['class_name'] }}</td><td class="px-4 py-3">{{ $row['attempts'] }}</td><td class="px-4 py-3">{{ $row['average'] }}%</td><td class="px-4 py-3">{{ $row['needs_support'] }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No KCAT analytics available yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
