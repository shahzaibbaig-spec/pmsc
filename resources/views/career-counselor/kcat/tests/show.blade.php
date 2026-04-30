<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">{{ $test->title }}</h2>
                <p class="text-sm text-slate-500">{{ ucfirst($test->status) }} | {{ $test->total_questions }} questions | {{ $test->total_marks }} marks</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('career-counselor.kcat.questions.create', $test) }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add Question</a>
                <a href="{{ route('career-counselor.kcat.attempts.manual-entry', $test) }}" class="rounded-xl border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700">Manual Entry</a>
                <a href="{{ route('career-counselor.kcat.tests.edit', $test) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('career-counselor.kcat.tests.activate', $test) }}">@csrf<button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Activate</button></form>
                <form method="POST" action="{{ route('career-counselor.kcat.tests.archive', $test) }}">@csrf<button class="rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-white">Archive</button></form>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-900">Add Section</h3>
            <form method="POST" action="{{ route('career-counselor.kcat.sections.store', $test) }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                @csrf
                <input name="name" placeholder="Section name" class="rounded-xl border-slate-300 text-sm" required>
                <input name="code" placeholder="section_code" class="rounded-xl border-slate-300 text-sm" required>
                <input name="sort_order" type="number" min="0" placeholder="Sort" class="rounded-xl border-slate-300 text-sm">
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add</button>
            </form>
        </section>

        @foreach ($test->sections as $section)
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $section->name }}</h3>
                        <p class="text-xs text-slate-500">{{ $section->code }} | {{ $section->total_questions }} questions | {{ $section->total_marks }} marks</p>
                    </div>
                    <form method="POST" action="{{ route('career-counselor.kcat.sections.destroy', $section) }}">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600">Delete Section</button></form>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($section->questions as $question)
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $question->sort_order }}. {{ $question->question_text }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $question->question_type }} | {{ $question->difficulty }} | {{ $question->marks }} mark(s)</p>
                            </div>
                            <div class="flex gap-3">
                                <a href="{{ route('career-counselor.kcat.questions.edit', $question) }}" class="text-sm font-semibold text-blue-700">Edit</a>
                                <form method="POST" action="{{ route('career-counselor.kcat.questions.destroy', $question) }}">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600">Deactivate</button></form>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-5 text-sm text-slate-500">No questions in this section.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</x-app-layout>
