<div class="grid grid-cols-1 gap-4 md:grid-cols-4">
    <div>
        <label class="text-sm font-semibold text-slate-700">Section</label>
        <select name="kcat_section_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
            @foreach ($test->sections as $section)
                <option value="{{ $section->id }}" @selected(old('kcat_section_id', $question?->kcat_section_id) == $section->id)>{{ $section->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm font-semibold text-slate-700">Type</label>
        <select name="question_type" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
            @foreach ([
                'mcq',
                'image_mcq',
                'analogy',
                'synonym',
                'antonym',
                'odd_one_out',
                'sentence_completion',
                'number_series',
                'missing_number',
                'pattern_logic',
                'ratio_logic',
                'pattern_sequence',
                'matrix',
                'odd_shape',
                'shape_series',
                'rotation',
                'mirror_image',
                'folding',
                'cube_logic',
                'sequence',
            ] as $type)
                <option value="{{ $type }}" @selected(old('question_type', $question?->question_type ?? 'mcq') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm font-semibold text-slate-700">Difficulty</label>
        <select name="difficulty" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
            @foreach (['easy','medium','hard'] as $difficulty)
                <option value="{{ $difficulty }}" @selected(old('difficulty', $question?->difficulty ?? 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-sm font-semibold text-slate-700">Marks</label>
        <input type="number" min="1" name="marks" value="{{ old('marks', $question?->marks ?? 1) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>
    </div>
</div>

<div>
    <label class="text-sm font-semibold text-slate-700">Question Text</label>
    <textarea name="question_text" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" required>{{ old('question_text', $question?->question_text) }}</textarea>
    @error('question_text') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="text-sm font-semibold text-slate-700">Explanation</label>
    <textarea name="explanation" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">{{ old('explanation', $question?->explanation) }}</textarea>
</div>

<div>
    <label class="text-sm font-semibold text-slate-700">Sort Order</label>
    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $question?->sort_order ?? 0) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm">
</div>

<div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
    <h3 class="font-semibold text-slate-900">Options</h3>
    <p class="text-xs text-slate-500">Mark exactly one correct answer. Correct options are only visible to counselor/admin pages.</p>
    @php($options = old('options', $question?->options?->map(fn ($option) => ['option_text' => $option->option_text, 'is_correct' => $option->is_correct ? 1 : 0])->values()->all() ?? [['option_text' => '', 'is_correct' => 1], ['option_text' => '', 'is_correct' => 0], ['option_text' => '', 'is_correct' => 0], ['option_text' => '', 'is_correct' => 0]]))
    <div class="mt-4 grid grid-cols-1 gap-3">
        @foreach ($options as $index => $option)
            <div class="flex items-center gap-3">
                <input type="radio" name="correct_option" value="{{ $index }}" @checked((int) ($option['is_correct'] ?? 0) === 1) class="text-blue-600">
                <input name="options[{{ $index }}][option_text]" value="{{ $option['option_text'] ?? '' }}" placeholder="Option {{ $index + 1 }}" class="block w-full rounded-xl border-slate-300 text-sm">
                <input type="hidden" name="options[{{ $index }}][is_correct]" value="0">
            </div>
        @endforeach
    </div>
    @error('options') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div class="flex justify-end gap-2">
    <a href="{{ route('career-counselor.kcat.tests.show', $test) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</a>
    <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save Question</button>
</div>

<script>
    document.currentScript.closest('form')?.addEventListener('submit', (event) => {
        const form = event.currentTarget;
        const selected = form.querySelector('input[name="correct_option"]:checked');
        form.querySelectorAll('input[name$="[is_correct]"]').forEach((input) => input.value = '0');
        if (selected) {
            const hidden = form.querySelector(`input[name="options[${selected.value}][is_correct]"]`);
            if (hidden) hidden.value = '1';
        }
    });
</script>
