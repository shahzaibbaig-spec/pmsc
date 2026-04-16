<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Create Daily Diary Entry</h2>
                <p class="mt-1 text-sm text-slate-500">Share class-wise daily homework with students.</p>
            </div>
            <a
                href="{{ route('teacher.daily-diary.index') }}"
                class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to Diary List
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

