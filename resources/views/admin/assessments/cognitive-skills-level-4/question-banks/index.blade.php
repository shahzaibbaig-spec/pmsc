<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cognitive Skills Assessment Test Level 4 Question Banks
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Assessment Setup</p>
                        <h1 class="mt-2 text-2xl font-semibold text-slate-900">Reusable Question Banks</h1>
                        <p class="mt-2 max-w-3xl text-sm text-slate-600">
                            Create reusable questions for Cognitive Skills Assessment Test Level 4 and assign them into Verbal, Non-Verbal, Quantitative, and Spatial sections.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ route('admin.assessments.cognitive-skills-level-4-reports.index') }}"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            View Reports
                        </a>
                        <a
                            href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.create') }}"
                            class="inline-flex items-center rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700"
                        >
                            Create Question Bank
                        </a>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Title</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Slug</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Creator</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Questions</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($banks as $bank)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-900">{{ $bank->title }}</p>
                                        @if ($bank->description)
                                            <p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($bank->description, 90) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $bank->slug }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $bank->creator?->name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $bank->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $bank->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $bank->bank_questions_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a
                                                href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.show', $bank) }}"
                                                class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                View
                                            </a>
                                            <a
                                                href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.edit', $bank) }}"
                                                class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.destroy', $bank) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                                    onclick="return confirm('Delete this question bank?')"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                        No cognitive question banks have been created yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                {{ $banks->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
