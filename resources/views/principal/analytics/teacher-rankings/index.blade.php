<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Teacher Ranking &amp; CGPA</h2>
                <p class="mt-1 text-sm text-slate-500">CGPA out of 6 based on teacher-owned student results for the selected session scope.</p>
            </div>
            <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                {{ $selectedSession }} | {{ $selectedExamLabel }}
            </div>
        </div>
    </x-slot>

    @php
        $topTeacher = $summary['top_teacher'] ?? null;
        $averageSchoolTeacherCgpa = $summary['average_school_teacher_cgpa'] ?? null;
        $totalRankedTeachers = $summary['total_ranked_teachers'] ?? 0;
    @endphp

    <div class="py-8" x-data="{ showClasswise: true }">
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
                            class="inline-flex min-h-11 items-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100"
                        >
                            Regenerate Rankings
                        </button>
                    </form>

                    @if (!($schemaReady ?? true))
                        <p class="text-sm text-slate-500">Migration required before rankings can be generated on this server.</p>
                    @endif

                    <button
                        type="button"
                        @click="showClasswise = !showClasswise"
                        class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <span x-text="showClasswise ? 'Hide Class-wise Table' : 'Show Class-wise Table'"></span>
                    </button>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Top Ranked Teacher</p>
                    <p class="mt-3 text-lg font-semibold text-slate-900">{{ $topTeacher['teacher_name'] ?? 'Not generated yet' }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($topTeacher)
                            CGPA {{ number_format((float) $topTeacher['cgpa'], 2) }} / 6
                        @else
                            Regenerate rankings for this filter to populate the leaderboard.
                        @endif
                    </p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Average School Teacher CGPA</p>
                    <p class="mt-3 text-lg font-semibold text-slate-900">
                        {{ $averageSchoolTeacherCgpa !== null ? number_format((float) $averageSchoolTeacherCgpa, 2).' / 6' : 'N/A' }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">Based on stored overall ranking rows for the selected scope.</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Ranked Teachers</p>
                    <p class="mt-3 text-lg font-semibold text-slate-900">{{ number_format((int) $totalRankedTeachers) }}</p>
                    <p class="mt-1 text-sm text-slate-500">Teachers with numeric result data included in this ranking snapshot.</p>
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Overall Teacher CGPA Ranking</h3>
                    <p class="mt-1 text-xs text-slate-500">Weighted by student count across all included class results.</p>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Average %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">CGPA / 6</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($overallRankings as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">#{{ $row['rank_position'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <p class="font-medium text-slate-900">{{ $row['teacher_name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $row['teacher_code'] !== '' ? $row['teacher_code'] : '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['average_percentage'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['cgpa'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((int) $row['student_count']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No overall teacher ranking snapshot exists for the selected filters yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" x-show="showClasswise" x-transition.opacity>
                <header class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Class-wise Teacher CGPA Ranking</h3>
                    <p class="mt-1 text-xs text-slate-500">Ranked separately inside each class using CGPA, average %, pass %, student count, and teacher name.</p>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Average %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">CGPA / 6</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($classwiseRankings as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $row['class_name'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">#{{ $row['rank_position'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <p class="font-medium text-slate-900">{{ $row['teacher_name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $row['teacher_code'] !== '' ? $row['teacher_code'] : '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['average_percentage'], 2) }}%</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((float) $row['cgpa'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((int) $row['student_count']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No class-wise teacher ranking snapshot exists for the selected filters yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
