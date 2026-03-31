<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    value="{{ old('title', $bank->title) }}"
                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                    required
                >
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-slate-700">Slug</label>
                <input
                    id="slug"
                    type="text"
                    name="slug"
                    value="{{ old('slug', $bank->slug) }}"
                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                    required
                >
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
            <textarea
                id="description"
                name="description"
                rows="4"
                class="mt-1 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
            >{{ old('description', $bank->description) }}</textarea>
        </div>

        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <input
                type="hidden"
                name="is_active"
                value="0"
            >
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $bank->is_active ?? true))
                class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
            >
            <span class="text-sm font-medium text-slate-700">Keep this question bank active</span>
        </label>

        <div class="flex flex-wrap gap-3">
            <button
                type="submit"
                class="inline-flex items-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
            >
                {{ $submitLabel }}
            </button>
            <a
                href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.index') }}"
                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Cancel
            </a>
        </div>
    </form>
</div>
