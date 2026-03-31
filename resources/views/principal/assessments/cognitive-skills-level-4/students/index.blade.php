<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cognitive Skills Assessment Test Level 4 Student Access
        </h2>
    </x-slot>

    @php
        $visibleRows = collect($rows->items());
        $enabledCount = $visibleRows->where('is_enabled', true)->count();
        $completedCount = $visibleRows->filter(fn ($row) => ($row['attempt']?->overall_percentage) !== null)->count();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Principal Controls</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $assessment->title }}</h3>
                        <p class="mt-2 max-w-3xl text-sm text-slate-600">
                            Student access is hidden by default. Enable this internal assessment only for eligible students in Grades 8 to 12, reset attempts when needed, and review internal cognitive profile reports after completion.
                        </p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Students on Page</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $visibleRows->count() }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-emerald-700">Enabled</p>
                            <p class="mt-1 text-xl font-semibold text-emerald-900">{{ $enabledCount }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Completed Profiles</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $completedCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('principal.assessments.cognitive-skills-level-4.students.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label for="class_id" class="block text-sm font-medium text-slate-700">Class</label>
                        <select id="class_id" name="class_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">All eligible classes</option>
                            @foreach ($classes as $classRoom)
                                @php
                                    $label = trim((string) ($classRoom->name.' '.($classRoom->section ?? '')));
                                @endphp
                                <option value="{{ $classRoom->id }}" @selected((string) $filters['class_id'] === (string) $classRoom->id)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="search" class="block text-sm font-medium text-slate-700">Search</label>
                        <input id="search" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Student name or ID" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div>
                        <label for="enabled_status" class="block text-sm font-medium text-slate-700">Status</label>
                        <select id="enabled_status" name="enabled_status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="all" @selected($filters['enabled_status'] === 'all')>All</option>
                            <option value="enabled" @selected($filters['enabled_status'] === 'enabled')>Enabled</option>
                            <option value="disabled" @selected($filters['enabled_status'] === 'disabled')>Disabled</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Apply
                        </button>
                        <a href="{{ route('principal.assessments.cognitive-skills-level-4.students.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                @forelse ($rows as $row)
                    @php
                        $student = $row['student'];
                        $attempt = $row['attempt'];
                        $assignment = $row['assignment'];
                        $enabledBadgeClass = $row['is_enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700';
                        $attemptBadgeClass = match ($attempt?->status) {
                            'graded' => 'bg-emerald-100 text-emerald-700',
                            'in_progress' => 'bg-amber-100 text-amber-700',
                            'auto_submitted' => 'bg-rose-100 text-rose-700',
                            'reset' => 'bg-indigo-100 text-indigo-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp

                    <div x-data="{ panel: '' }" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">{{ $row['class_label'] }}</p>
                                    <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ $student->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $student->student_id }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $enabledBadgeClass }}">
                                        {{ $row['is_enabled'] ? 'Enabled for Student Panel' : 'Hidden from Student Panel' }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $attemptBadgeClass }}">
                                        {{ $row['attempt_status_label'] }}
                                    </span>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Overall Score</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $row['completed_score'] ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Overall Percentage</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $row['overall_percentage'] !== null ? number_format((float) $row['overall_percentage'], 2).'%' : '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Performance Band</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $row['performance_band'] ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-xs uppercase tracking-wide text-slate-500">Latest Attempt</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $attempt ? '#'.$attempt->id : 'Not started' }}</p>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    <p><span class="font-semibold text-slate-900">Assignment Note:</span> {{ $assignment?->principal_note ?: 'No note recorded yet.' }}</p>
                                    <p class="mt-1">
                                        <span class="font-semibold text-slate-900">Audit:</span>
                                        @if ($assignment?->enabled_at)
                                            Enabled {{ $assignment->enabled_at->format('Y-m-d H:i') }} by {{ $assignment->enabledBy?->name ?? 'User' }}.
                                        @else
                                            Not enabled yet.
                                        @endif
                                        @if ($assignment?->disabled_at)
                                            Disabled {{ $assignment->disabled_at->format('Y-m-d H:i') }} by {{ $assignment->disabledBy?->name ?? 'User' }}.
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 xl:max-w-sm xl:justify-end">
                                <button type="button" @click="panel = panel === 'enable' ? '' : 'enable'" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Enable
                                </button>
                                <button type="button" @click="panel = panel === 'disable' ? '' : 'disable'" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Disable
                                </button>
                                <button type="button" @click="panel = panel === 'reset' ? '' : 'reset'" class="inline-flex items-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                                    Reset
                                </button>
                                @if ($row['report_available'])
                                    <a href="{{ route('principal.assessments.cognitive-skills-level-4.reports.show', $attempt) }}" class="inline-flex items-center rounded-xl border border-sky-300 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:bg-sky-100">
                                        View Report
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div x-show="panel === 'enable'" x-cloak class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                            <form method="POST" action="{{ route('principal.assessments.cognitive-skills-level-4.students.enable', [$assessment, $student]) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="enable-note-{{ $student->id }}" class="block text-sm font-medium text-slate-700">Principal Note</label>
                                    <textarea id="enable-note-{{ $student->id }}" name="principal_note" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('principal_note', $assignment?->principal_note) }}</textarea>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                        Confirm Enable
                                    </button>
                                    <button type="button" @click="panel = ''" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div x-show="panel === 'disable'" x-cloak class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <form method="POST" action="{{ route('principal.assessments.cognitive-skills-level-4.students.disable', [$assessment, $student]) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="disable-note-{{ $student->id }}" class="block text-sm font-medium text-slate-700">Principal Note</label>
                                    <textarea id="disable-note-{{ $student->id }}" name="principal_note" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('principal_note', $assignment?->principal_note) }}</textarea>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                        Confirm Disable
                                    </button>
                                    <button type="button" @click="panel = ''" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div x-show="panel === 'reset'" x-cloak class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <form method="POST" action="{{ route('principal.assessments.cognitive-skills-level-4.students.reset', [$assessment, $student]) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="reset-reason-{{ $student->id }}" class="block text-sm font-medium text-slate-700">Reset Reason</label>
                                    <textarea id="reset-reason-{{ $student->id }}" name="reason" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">{{ old('reason') }}</textarea>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="inline-flex items-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700">
                                        Confirm Reset
                                    </button>
                                    <button type="button" @click="panel = ''" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Cancel
                                    </button>
                                </div>
                                <p class="text-xs text-slate-500">Reset preserves audit history, marks the latest attempt as reset, and keeps the student enabled for a fresh retake.</p>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                        <h3 class="text-lg font-semibold text-slate-900">No eligible students matched the selected filters</h3>
                        <p class="mt-2 text-sm text-slate-600">Adjust the class, search term, or enabled status to review another part of the student access list.</p>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
