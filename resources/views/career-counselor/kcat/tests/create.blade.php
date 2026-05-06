<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">{{ $test ? 'Edit KCAT Test' : 'Create KCAT Test' }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl py-8">
        <form method="POST" action="{{ $test ? route('career-counselor.kcat.tests.update', $test) : route('career-counselor.kcat.tests.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @if ($test) @method('PUT') @endif
            <div>
                <label class="text-sm font-semibold text-slate-700">Title</label>
                <input name="title" value="{{ old('title', $test?->title) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-semibold text-slate-700">Description</label>
                <textarea name="description" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $test?->description) }}</textarea>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div><label class="text-sm font-semibold text-slate-700">Grade From</label><input type="number" min="1" max="12" name="grade_from" value="{{ old('grade_from', $test?->grade_from ?? 7) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"></div>
                <div><label class="text-sm font-semibold text-slate-700">Grade To</label><input type="number" min="1" max="12" name="grade_to" value="{{ old('grade_to', $test?->grade_to ?? 12) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"></div>
                <div><label class="text-sm font-semibold text-slate-700">Duration</label><input type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes', $test?->duration_minutes) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"></div>
                <div><label class="text-sm font-semibold text-slate-700">Status</label><select name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"><option value="draft" @selected(old('status', $test?->status) === 'draft')>Draft</option><option value="active" @selected(old('status', $test?->status) === 'active')>Active</option><option value="archived" @selected(old('status', $test?->status) === 'archived')>Archived</option></select></div>
            </div>
            @if (! $test)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Total Questions</label>
                        <input id="question_count" type="number" min="4" max="400" step="4" name="question_count" value="{{ old('question_count', 40) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
                        <p id="per_section_preview" class="mt-1 text-xs text-slate-500">Each category will get equal questions.</p>
                        @error('question_count') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Difficulty Level</label>
                        <select name="difficulty_level" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
                            <option value="auto" @selected(old('difficulty_level', 'auto') === 'auto')>Auto by class level</option>
                            <option value="easy" @selected(old('difficulty_level') === 'easy')>Easy (Grade 7-8)</option>
                            <option value="medium" @selected(old('difficulty_level') === 'medium')>Medium (Grade 9-10)</option>
                            <option value="hard" @selected(old('difficulty_level') === 'hard')>Hard (Grade 11-12)</option>
                        </select>
                        @error('difficulty_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Questions Per Section</label>
                    <input type="number" min="1" max="200" name="questions_per_section" value="{{ old('questions_per_section', $test?->questions_per_section ?? 10) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3">
                    <input id="is_adaptive_enabled" type="checkbox" name="is_adaptive_enabled" value="1" class="rounded border-slate-300 text-blue-600" @checked(old('is_adaptive_enabled', $test?->is_adaptive_enabled ?? false))>
                    <label for="is_adaptive_enabled" class="text-sm font-semibold text-slate-700">Enable Adaptive Mode for this test</label>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('career-counselor.kcat.tests.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</a>
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
            </div>
        </form>
    </div>

    @if (! $test)
        <script>
            (() => {
                const totalInput = document.getElementById('question_count');
                const preview = document.getElementById('per_section_preview');
                if (!totalInput || !preview) {
                    return;
                }

                const render = () => {
                    const total = Number(totalInput.value || 0);
                    if (!Number.isFinite(total) || total <= 0) {
                        preview.textContent = 'Each category will get equal questions.';
                        return;
                    }

                    if (total % 4 !== 0) {
                        preview.textContent = 'Total questions must be divisible by 4 for equal category distribution.';
                        return;
                    }

                    preview.textContent = `Each category will get ${total / 4} questions.`;
                };

                totalInput.addEventListener('input', render);
                render();
            })();
        </script>
    @endif
</x-app-layout>
