<x-app-layout>
    @php
        $acr = $payload['acr'];
        $teacher = $payload['teacher'];
        $metrics = $payload['metrics'];
        $narrative = $payload['narrative'];
        $scores = $payload['scores'];
        $conductScore = data_get(collect($scores)->firstWhere('label', 'Conduct / classroom behavior'), 'score', 0);
        $principalScore = data_get(collect($scores)->firstWhere('label', 'Principal review score'), 'score', 0);
        $isFinalized = ($acr['status'] ?? '') === 'finalized';
        $isReviewed = ($acr['status'] ?? '') !== 'draft';
        $needsRefresh = (bool) ($acr['needs_refresh'] ?? false);
        $lastMetricsRefreshAt = $acr['last_metrics_refresh_at'] ?? null;
        $statusClasses = match ($acr['status'] ?? 'draft') {
            'reviewed' => 'bg-amber-100 text-amber-800',
            'finalized' => 'bg-emerald-100 text-emerald-800',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Teacher ACR Review</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $teacher['name'] }} | {{ $teacher['teacher_id'] }} | Session {{ $acr['session'] }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                    {{ $acr['status_label'] }}
                </span>
                @can('manage_teacher_acr')
                    @if ($isFinalized)
                        <form method="POST" action="{{ route('principal.acr.refresh', $acr['id']) }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100" onclick="return confirm('Refresh this ACR using the latest academic data? Principal remarks will be preserved.')">
                                Refresh ACR from Latest Results
                            </button>
                        </form>
                    @endif
                @endcan
                <a href="{{ route('principal.acr.print', $acr['id']) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Printable View
                </a>
                <a href="{{ route('principal.acr.index', ['session' => $acr['session']]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back to Register
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($isFinalized && $needsRefresh)
                <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <p class="font-semibold">Academic data has changed after finalization. Refresh is recommended.</p>
                    @if ($lastMetricsRefreshAt)
                        <p class="mt-1 text-xs text-amber-700">Latest metrics check: {{ $lastMetricsRefreshAt->format('d M Y, h:i A') }}</p>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please review the entered information.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</p>
                    <p class="mt-3 text-lg font-semibold text-slate-900">{{ $teacher['name'] }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $teacher['teacher_id'] }}{{ $teacher['employee_code'] ? ' | '.$teacher['employee_code'] : '' }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $teacher['designation'] ?: 'Teacher' }}</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Score</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">{{ number_format((float) $acr['total_score'], 2) }}</p>
                    <p class="mt-1 text-sm text-slate-500">Out of 100 weighted ACR points</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Final Grade</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $acr['final_grade'] ?: 'Pending review' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Calculated after principal review</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Audit</p>
                    <p class="mt-3 text-sm font-semibold text-slate-900">Prepared by: {{ $acr['prepared_by'] ?: 'System draft' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Reviewed by: {{ $acr['reviewed_by'] ?: 'Pending' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Finalized: {{ $acr['finalized_at'] ? $acr['finalized_at']->format('d M Y, h:i A') : 'Not yet' }}</p>
                </article>
            </section>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Score Breakdown</h3>
                        <p class="mt-1 text-sm text-slate-500">Auto-generated evidence remains visible here so the principal can review each weighted component before finalization.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Component</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Metric</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Weight</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($scores as $row)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $row['label'] }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $row['metric'] }}</td>
                                        <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ number_format((float) $row['score'], 2) }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ number_format((float) $row['weight'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Performance Metrics</h3>
                        <p class="mt-1 text-sm text-slate-500">These metrics were captured at draft-generation time and stored with the report for audit consistency.</p>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Attendance</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">{{ $metrics['attendance_percentage'] !== null ? number_format((float) $metrics['attendance_percentage'], 2).'%' : 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher CGPA</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">{{ $metrics['teacher_cgpa'] !== null ? number_format((float) $metrics['teacher_cgpa'], 2) : 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pass Percentage</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">{{ $metrics['pass_percentage'] !== null ? number_format((float) $metrics['pass_percentage'], 2).'%' : 'N/A' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Student Improvement</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">{{ $metrics['student_improvement_percentage'] !== null ? number_format((float) $metrics['student_improvement_percentage'], 2).'%' : 'Neutral baseline applied' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Trainings Attended</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">{{ $metrics['trainings_attended'] }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Classes in Scope</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ !empty($teacher['classes']) ? implode(', ', $teacher['classes']) : 'No class mapping stored' }}</p>
                        </div>
                    </div>

                    @if (!empty($metrics['notes']))
                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Automation Notes</p>
                            <ul class="mt-2 space-y-2 text-sm text-amber-800">
                                @foreach ($metrics['notes'] as $note)
                                    <li>{{ $note }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Principal Review</h3>
                    <p class="text-sm text-slate-500">Confidential remarks are restricted to principal and admin users and are not exposed to teachers by default.</p>
                </div>

                <form method="POST" action="{{ route('principal.acr.update', $acr['id']) }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="conduct_score" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Conduct Score (0-15)</label>
                            <input id="conduct_score" type="number" step="0.01" min="0" max="15" name="conduct_score" value="{{ old('conduct_score', $conductScore) }}" @disabled($isFinalized) class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:cursor-not-allowed disabled:bg-slate-100">
                        </div>
                        <div>
                            <label for="principal_score" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Principal Score (0-15)</label>
                            <input id="principal_score" type="number" step="0.01" min="0" max="15" name="principal_score" value="{{ old('principal_score', $principalScore) }}" @disabled($isFinalized) class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:cursor-not-allowed disabled:bg-slate-100">
                        </div>
                    </div>

                    <div>
                        <label for="strengths" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Strengths</label>
                        <textarea id="strengths" name="strengths" rows="4" @disabled($isFinalized) class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:cursor-not-allowed disabled:bg-slate-100">{{ old('strengths', $narrative['strengths']) }}</textarea>
                    </div>

                    <div>
                        <label for="areas_for_improvement" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Areas for Improvement</label>
                        <textarea id="areas_for_improvement" name="areas_for_improvement" rows="4" @disabled($isFinalized) class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:cursor-not-allowed disabled:bg-slate-100">{{ old('areas_for_improvement', $narrative['areas_for_improvement']) }}</textarea>
                    </div>

                    <div>
                        <label for="recommendations" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Recommendations</label>
                        <textarea id="recommendations" name="recommendations" rows="4" @disabled($isFinalized) class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:cursor-not-allowed disabled:bg-slate-100">{{ old('recommendations', $narrative['recommendations']) }}</textarea>
                    </div>

                    <div>
                        <label for="confidential_remarks" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Confidential Remarks</label>
                        <textarea id="confidential_remarks" name="confidential_remarks" rows="5" @disabled($isFinalized) class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:cursor-not-allowed disabled:bg-slate-100">{{ old('confidential_remarks', $narrative['confidential_remarks']) }}</textarea>
                    </div>

                    @can('manage_teacher_acr')
                        @if (! $isFinalized)
                            <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                                <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Save Principal Review
                                </button>

                                @if (! $isReviewed)
                                    <p class="text-sm text-slate-500">Finalize becomes available after saving principal review.</p>
                                @endif
                            </div>
                        @endif
                    @endcan
                </form>
            </section>

            @can('finalize_teacher_acr')
                @if (! $isFinalized)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Finalize ACR</h3>
                                <p class="mt-1 text-sm text-slate-500">Finalization locks the report for this session and preserves the reviewed scoring snapshot.</p>
                            </div>

                            @if ($isReviewed)
                                <form method="POST" action="{{ route('principal.acr.finalize', $acr['id']) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" onclick="return confirm('Finalize this ACR? This should only be done after review is complete.')">
                                        Finalize ACR
                                    </button>
                                </form>
                            @else
                                <p class="text-sm font-medium text-amber-700">Save principal review first to move this ACR out of draft status.</p>
                            @endif
                        </div>
                    </section>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
