<x-app-layout>
    <x-slot name="header"><div class="flex justify-between"><h2 class="text-xl font-semibold text-slate-900">Career Counselor Reports</h2><a target="_blank" href="{{ route('principal.career-reports.print') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Print</a></div></x-slot>
    <div class="mx-auto max-w-7xl space-y-6 py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">Grade-wise Assessment Summary</h3><div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">@foreach($gradeSummary as $row)<div class="rounded-xl bg-blue-50 p-3 text-sm"><b>{{ $row['class_name'] }}</b><p>{{ $row['total'] }} assessments</p></div>@endforeach</div></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">Student/Parent Visible Career Recommendations</h3><div class="mt-3 space-y-3">@forelse($visibleRecommendations as $row)<div class="rounded-xl border border-slate-200 p-3 text-sm"><b>{{ $row->student?->name }}</b><p>{{ $row->public_summary ?: $row->recommended_career_path }}</p><p class="text-xs text-slate-500">Visible to: {{ str_replace('_', ' / ', $row->visibility) }}</p></div>@empty<p class="text-sm text-slate-500">No visible recommendations.</p>@endforelse</div></section>
    </div>
</x-app-layout>
