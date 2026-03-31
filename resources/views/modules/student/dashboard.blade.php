<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Student Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">Welcome, Student.</p>
                    <p class="text-sm text-gray-600 mt-2">Access attendance, marks, and report card information.</p>

                    @if(!empty($feeMessage))
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            {{ $feeMessage }}
                        </div>
                    @elseif(!empty($feeStatus))
                        <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-slate-700">Fee Status:</span>
                                @if(($feeStatus['is_defaulter'] ?? false) === true)
                                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                        Defaulter
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                        Clear
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Session</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $feeStatus['session'] ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Outstanding Due</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">Rs. {{ number_format((float) ($feeStatus['total_due'] ?? 0), 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Oldest Due Date</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $feeStatus['oldest_due_date'] ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!empty($latestChallan))
                        <div class="mt-4 rounded-md border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-slate-900">Latest Fee Challan</h3>
                                @if(($latestChallan['is_recently_generated'] ?? false) === true)
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                        New challan generated
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 grid gap-3 sm:grid-cols-4">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Challan #</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $latestChallan['challan_number'] ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Month</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $latestChallan['month'] ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Issue Date</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $latestChallan['issue_date'] ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Due Date</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $latestChallan['due_date'] ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">Rs. {{ number_format((float) ($latestChallan['total_amount'] ?? 0), 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Paid</p>
                                    <p class="mt-1 text-sm font-semibold text-emerald-700">Rs. {{ number_format((float) ($latestChallan['paid_amount'] ?? 0), 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Remaining</p>
                                    <p class="mt-1 text-sm font-semibold text-rose-700">Rs. {{ number_format((float) ($latestChallan['due_amount'] ?? 0), 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!empty($cognitiveAssessmentCard))
                        <div class="mt-4 rounded-md border border-sky-200 bg-sky-50 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">Assessment</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $cognitiveAssessmentCard['title'] }}</h3>
                                    <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ $cognitiveAssessmentCard['description'] }}</p>
                                </div>
                                <div class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-sky-700 shadow-sm">
                                    {{ $cognitiveAssessmentCard['status_label'] }}
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Assessment Type</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">Fully Auto-Checked</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Performance Band</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $cognitiveAssessmentCard['performance_band'] ?? 'Pending' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Overall Percentage</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ isset($cognitiveAssessmentCard['overall_percentage']) ? number_format((float) $cognitiveAssessmentCard['overall_percentage'], 2).'%' : 'Not graded yet' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <a
                                    href="{{ $cognitiveAssessmentCard['action_url'] }}"
                                    class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700"
                                >
                                    {{ $cognitiveAssessmentCard['action_label'] }}
                                </a>

                                @if(!empty($cognitiveAssessmentCard['submitted_at']))
                                    <p class="text-xs text-slate-500">Last submitted at {{ $cognitiveAssessmentCard['submitted_at'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <a
                            href="{{ route('student.results.index') }}"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            View My Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
