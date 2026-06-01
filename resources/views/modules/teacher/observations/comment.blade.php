<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">{{ $typeLabel }} Comment</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Observation #{{ $observation->id }} from {{ optional($observation->observation_date)->format('d M Y') ?: '-' }}
                </p>
            </div>
            <a href="{{ route('teacher.dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="space-y-6 xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Observed Teacher</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $observation->observedTeacher?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Observer</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $observation->observer?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Session</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $observation->session }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Date</p>
                            <p class="mt-1 text-sm text-slate-900">{{ optional($observation->observation_date)->format('d M Y') ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Class</p>
                            <p class="mt-1 text-sm text-slate-900">{{ trim(($observation->classRoom?->name ?? '').' '.($observation->classRoom?->section ?? '')) ?: '-' }}</p>
                        </div>
                        @if ($type === 'lesson')
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-500">Subject / Topic</p>
                                <p class="mt-1 text-sm text-slate-900">{{ $observation->subject_topic ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-500">No. of Students</p>
                                <p class="mt-1 text-sm text-slate-900">{{ $observation->no_of_students ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-500">Performance</p>
                                <p class="mt-1 text-sm text-slate-900">{{ number_format((float) ($observation->performance_score ?? 0), 2) }}%</p>
                            </div>
                        @else
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-500">Subject</p>
                                <p class="mt-1 text-sm text-slate-900">{{ $observation->subject?->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-500">Total Students</p>
                                <p class="mt-1 text-sm text-slate-900">{{ $observation->total_students ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-500">Performance</p>
                                <p class="mt-1 text-sm text-slate-900">{{ number_format((float) ($observation->performance_score ?? 0), 2) }}%</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500">Status</p>
                            <p class="mt-1 text-sm text-slate-900">{{ ucfirst((string) $observation->status) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Observation Details</h3>
                    @if ($type === 'lesson')
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">Learning Objectives</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->learning_objectives ?: '-' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">Previous Targets</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->previous_targets ?: '-' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">What Went Well</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->what_went_well ?: '-' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase text-slate-500">Even Better If</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->even_better_if ?: '-' }}</p>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase text-slate-500">General Comments</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $observation->general_comments ?: '-' }}</p>
                        </div>
                    @endif

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-blue-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">
                                        {{ $type === 'lesson' ? 'Area' : 'Checklist Item' }}
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Response</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-blue-700">Comments</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($observation->items as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-slate-900">
                                            {{ $type === 'lesson' ? $item->area : $item->checklist_text }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ $type === 'lesson' ? ((int) $item->mark).' / '.((int) $item->max_mark) : strtoupper((string) $item->response) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ $item->comments ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No observation items found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Your Comment</h3>
                    <form method="POST" action="{{ route('teacher.observations.comment', ['type' => $type, 'id' => $observation->id]) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="teacher_comments" value="Comment" />
                            <textarea
                                id="teacher_comments"
                                name="teacher_comments"
                                rows="8"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Write your response to this observation..."
                            >{{ old('teacher_comments', $observation->teacher_comments ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('teacher_comments')" class="mt-2" />
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-primary-button>
                                Submit Comment
                            </x-primary-button>
                            @if ($observation->teacher_commented_at)
                                <p class="text-xs text-slate-500">
                                    Last saved at {{ $observation->teacher_commented_at->format('d M Y h:i A') }}
                                </p>
                            @endif
                        </div>
                    </form>
                </div>
            </section>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Pending Comments</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Your latest observations waiting for a reply.
                    </p>
                    <div class="mt-4 space-y-3">
                        @forelse ($pendingComments as $item)
                            <a href="{{ route('teacher.observations.comment', ['type' => $item['type'], 'id' => $item['id']]) }}" class="block rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50">
                                <p class="text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item['observer_name'] }} on {{ $item['date'] }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">No pending teacher comments right now.</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
