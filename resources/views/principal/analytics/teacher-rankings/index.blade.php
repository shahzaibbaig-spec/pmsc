<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Teacher Ranking &amp; CGPA</h2>
                <p class="mt-1 text-sm text-slate-500">Teacher rankings are split into Early Years, Middle School, and Senior School so each level is compared within its own academic structure.</p>
            </div>
            <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                {{ $selectedSession }} | {{ $selectedExamLabel }}
            </div>
        </div>
    </x-slot>

    @php
        $rankingGroups = $rankingGroups ?? [];
        $groupedRankings = $groupedRankings ?? [];
        $previewMode = (bool) ($previewMode ?? false);
        $showClasswiseState = collect(array_keys($rankingGroups))->mapWithKeys(fn ($group) => [$group => true])->all();
    @endphp

    <div class="py-8" x-data='@json(["showClasswise" => $showClasswiseState])'>
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

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please review the selected filters.</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!($schemaReady ?? true) && !empty($schemaMessage))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $schemaMessage }}
                </div>
            @elseif ($previewMode && !empty($schemaMessage))
                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">
                    {{ $schemaMessage }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('principal.analytics.teacher-rankings.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="session" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Session</label>
                        <select
                            id="session"
                            name="session"
                            class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach ($sessions as $session)
                                <option value="{{ $session }}" @selected($selectedSession === $session)>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="exam_type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Exam Type</label>
                        <select
                            id="exam_type"
                            name="exam_type"
                            class="mt-1 block min-h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach ($examTypes as $examType)
                                <option value="{{ $examType['value'] }}" @selected($selectedExamType === $examType['value'])>{{ $examType['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2 flex flex-wrap items-end gap-3">
                        <button
                            type="submit"
                            class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Apply Filters
                        </button>
                        <a
                            href="{{ route('principal.analytics.teacher-rankings.index') }}"
                            class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('principal.analytics.teacher-rankings.regenerate') }}">
                        @csrf
                        <input type="hidden" name="session" value="{{ $selectedSession }}">
                        <input type="hidden" name="exam_type" value="{{ $selectedExamType }}">
                        <button
                            type="submit"
                            @disabled(!($schemaReady ?? true))
                            class="inline-flex min-h-11 items-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Regenerate Rankings
                        </button>
                    </form>

                    @if (!($schemaReady ?? true))
                        <p class="text-sm text-slate-500">Migration required before grouped rankings can be stored on this server.</p>
                    @elseif ($previewMode)
                        <p class="text-sm text-slate-500">Viewing live calculated previews for any academic level that does not yet have a saved snapshot.</p>
                    @endif

                    <a
                        href="{{ route('principal.analytics.teachers.index') }}"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Open Teacher Analytics
                    </a>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($rankingGroups as $groupKey => $groupMeta)
                    @php
                        $groupData = $groupedRankings[$groupKey] ?? [];
                        $groupSummary = $groupData['summary'] ?? [];
                        $topTeacher = $groupSummary['top_teacher'] ?? null;
                        $averageCgpa = $groupSummary['average_teacher_cgpa'] ?? null;
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Top {{ $groupMeta['label'] }} Teacher</p>
                        <p class="mt-3 text-lg font-semibold text-slate-900">{{ $topTeacher['teacher_name'] ?? 'Not available yet' }}</p>
                        <p class="mt-1 text-sm text-slate-500">
                            @if ($topTeacher)
                                CGPA {{ number_format((float) $topTeacher['cgpa'], 2) }} / 6
                            @else
                                No ranking rows found for this academic level and scope.
                            @endif
                        </p>
                    </article>
                @endforeach

                @foreach ($rankingGroups as $groupKey => $groupMeta)
                    @php
                        $groupData = $groupedRankings[$groupKey] ?? [];
                        $groupSummary = $groupData['summary'] ?? [];
                        $averageCgpa = $groupSummary['average_teacher_cgpa'] ?? null;
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Average {{ $groupMeta['label'] }} CGPA</p>
                        <p class="mt-3 text-lg font-semibold text-slate-900">
                            {{ $averageCgpa !== null ? number_format((float) $averageCgpa, 2).' / 6' : 'N/A' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">Average teacher CGPA inside the {{ strtolower($groupMeta['label']) }} comparison group.</p>
                    </article>
                @endforeach
            </section>

            @foreach ($rankingGroups as $groupKey => $groupMeta)
                @php
                    $groupData = $groupedRankings[$groupKey] ?? [];
                    $overallRows = $groupData['overall'] ?? [];
                    $classwiseRows = $groupData['classwise'] ?? [];
                    $groupSummary = $groupData['summary'] ?? [];
                    $groupPreview = (bool) ($groupData['preview_mode'] ?? false);
                    $groupDataSource = (string) ($groupData['data_source'] ?? 'snapshot');
                    $metricHeader = $groupKey === 'early_years' ? 'Average Grade Point / Average %' : 'Average %';
                    $classwiseEmptyMessage = $groupPreview
                        ? 'No class-wise ranking data is available for this academic level and filter yet.'
                        : 'No saved class-wise ranking snapshot exists for this academic level and filter yet.';
                    $overallEmptyMessage = $groupPreview
                        ? 'No overall ranking data is available for this academic level and filter yet.'
                        : 'No saved overall ranking snapshot exists for this academic level and filter yet.';
                @endphp

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <header class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $groupMeta['label'] }} Teacher Ranking</h3>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $groupDataSource === 'snapshot' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">
                                        {{ $groupDataSource === 'snapshot' ? 'Saved Snapshot' : 'Live Preview' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Teachers in {{ $groupMeta['label'] }} are ranked only against other {{ $groupMeta['label'] }} teachers for this filter.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span>Total Ranked Teachers: {{ number_format((int) ($groupSummary['total_ranked_teachers'] ?? 0)) }}</span>
                                <button
                                    type="button"
                                    @click="showClasswise['{{ $groupKey }}'] = !showClasswise['{{ $groupKey }}']"
                                    class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    <span x-text="showClasswise['{{ $groupKey }}'] ? 'Hide Class-wise Table' : 'Show Class-wise Table'"></span>
                                </button>
                            </div>
                        </div>
                    </header>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Classes</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $metricHeader }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">CGPA / 6</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Result Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($overallRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">#{{ $row['rank_position'] ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <p class="font-medium text-slate-900">{{ $row['teacher_name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $row['teacher_code'] !== '' ? $row['teacher_code'] : '-' }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ $row['classes_label'] !== '' ? $row['classes_label'] : '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            @if ($groupKey === 'early_years')
                                                <p class="font-medium text-slate-900">GP {{ number_format((float) $row['cgpa'], 2) }}</p>
                                                <p class="text-xs text-slate-500">{{ number_format((float) $row['average_percentage'], 2) }}%</p>
                                            @else
                                                {{ number_format((float) $row['average_percentage'], 2) }}%
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['cgpa'], 2) }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((int) $row['student_count']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                            {{ $overallEmptyMessage }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div x-show="showClasswise['{{ $groupKey }}']" x-transition.opacity class="border-t border-slate-200">
                        <header class="border-b border-slate-200 px-5 py-4">
                            <h4 class="text-sm font-semibold text-slate-900">{{ $groupMeta['label'] }} Class-wise Ranking</h4>
                            <p class="mt-1 text-xs text-slate-500">Teachers are ranked separately inside each class for the {{ strtolower($groupMeta['label']) }} level.</p>
                        </header>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $metricHeader }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">CGPA / 6</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Result Count</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($classwiseRows as $row)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $row['class_name'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">#{{ $row['rank_position'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <p class="font-medium text-slate-900">{{ $row['teacher_name'] }}</p>
                                                <p class="text-xs text-slate-500">{{ $row['teacher_code'] !== '' ? $row['teacher_code'] : '-' }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                @if ($groupKey === 'early_years')
                                                    <p class="font-medium text-slate-900">GP {{ number_format((float) $row['cgpa'], 2) }}</p>
                                                    <p class="text-xs text-slate-500">{{ number_format((float) $row['average_percentage'], 2) }}%</p>
                                                @else
                                                    {{ number_format((float) $row['average_percentage'], 2) }}%
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['cgpa'], 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((int) $row['student_count']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                                {{ $classwiseEmptyMessage }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
