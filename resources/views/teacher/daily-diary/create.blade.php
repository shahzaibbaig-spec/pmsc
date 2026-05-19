<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Create Daily Diary Entry</h2>
                <p class="mt-1 text-sm text-slate-500">Share class-wise daily homework with students.</p>
                <div class="mt-3 inline-flex rounded-xl border border-slate-200 bg-white p-1 text-sm">
                    @can('create_daily_diary')
                        <a href="{{ route('teacher.daily-diary.create', ['session' => $selectedSession]) }}" class="rounded-lg bg-slate-900 px-3 py-1.5 font-semibold text-white">Post Diary Entry</a>
                    @endcan
                    @can('view_own_daily_diary_entries')
                        <a href="{{ route('teacher.daily-diary.my-entries', ['session' => $selectedSession]) }}" class="rounded-lg px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-100">My Diary Entries</a>
                    @endcan
                </div>
            </div>
            <a
                href="{{ route('teacher.daily-diary.my-entries') }}"
                class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to My Entries
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            @include('teacher.daily-diary._form', [
                'action' => route('teacher.daily-diary.store'),
                'method' => 'POST',
                'options' => $options,
                'selectedSession' => $selectedSession,
                'submitLabel' => 'Publish Diary',
                'dailyDiary' => null,
            ])
        </div>
    </div>
</x-app-layout>
