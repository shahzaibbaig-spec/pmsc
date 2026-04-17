<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Daily Diary Detail</h2>
                <p class="mt-1 text-sm text-slate-500">Read-only view of diary content and instructions.</p>
            </div>
            <a href="{{ route('warden.daily-diary.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Diary List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">{{ $dailyDiary->title ?: 'Untitled Diary Entry' }}</h3>
                <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                    <p><span class="font-semibold text-slate-900">Date:</span> {{ optional($dailyDiary->diary_date)->format('d M Y') }}</p>
                    <p><span class="font-semibold text-slate-900">Session:</span> {{ $dailyDiary->session }}</p>
                    <p><span class="font-semibold text-slate-900">Class:</span> {{ trim(($dailyDiary->classRoom?->name ?? '').' '.($dailyDiary->classRoom?->section ?? '')) }}</p>
                    <p><span class="font-semibold text-slate-900">Subject:</span> {{ $dailyDiary->subject?->name }}</p>
                    <p><span class="font-semibold text-slate-900">Teacher:</span> {{ $dailyDiary->teacher?->user?->name ?? 'Teacher' }}</p>
                    <p>
                        <span class="font-semibold text-slate-900">Status:</span>
                        <span class="{{ $dailyDiary->is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} ms-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                            {{ $dailyDiary->is_published ? 'Published' : 'Draft' }}
                        </span>
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Homework</h4>
                <div class="mt-3 text-sm leading-6 text-slate-800 whitespace-pre-line">{{ $dailyDiary->homework_text }}</div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Instructions</h4>
                <div class="mt-3 text-sm leading-6 text-slate-800 whitespace-pre-line">{{ $dailyDiary->instructions ?: 'No additional instructions provided.' }}</div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Attachments</h4>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    @forelse ($dailyDiary->attachments as $attachment)
                        <li class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <p class="font-medium text-slate-900">{{ $attachment->file_name ?: 'Attachment #'.$attachment->id }}</p>
                            <p class="text-xs text-slate-500 break-all">{{ $attachment->file_path }}</p>
                        </li>
                    @empty
                        <li class="text-slate-500">No attachments available for this diary entry.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
