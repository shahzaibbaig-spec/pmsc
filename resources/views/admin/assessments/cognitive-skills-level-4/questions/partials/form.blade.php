@php
    $resolvedOptions = old('options', $question->options ?? ['', '']);
    if (!is_array($resolvedOptions) || count($resolvedOptions) < 2) {
        $resolvedOptions = ['', ''];
    }
    $recommendedTypes = array_values($imageRecommendedTypes);
@endphp

<div
    x-data="cognitiveBankQuestionForm({
        options: @js(array_values($resolvedOptions)),
        initialType: @js(old('question_type', $question->question_type ?: 'mcq')),
        initialImageUrl: @js($question->question_image_url),
        initialCorrectAnswer: @js(old('correct_answer', $question->correct_answer)),
        recommendedTypes: @js($recommendedTypes),
    })"
    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
>
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            Question bank: <span class="font-semibold text-slate-900">{{ $bank->title }}</span>
        </div>

        <input type="hidden" name="question_bank_id" value="{{ $bank->id }}">

        <div class="grid gap-6 md:grid-cols-3">
            <div>
                <label for="skill" class="block text-sm font-medium text-slate-700">Skill</label>
                <select id="skill" name="skill" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                    <option value="">Select skill</option>
                    @foreach ($skillOptions as $skill => $label)
                        <option value="{{ $skill }}" @selected(old('skill', $question->skill) === $skill)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="question_type" class="block text-sm font-medium text-slate-700">Question Type</label>
                <select id="question_type" name="question_type" x-model="questionType" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                    @foreach ($questionTypes as $questionType)
                        <option value="{{ $questionType }}">{{ strtoupper(str_replace('_', ' ', $questionType)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="difficulty_level" class="block text-sm font-medium text-slate-700">Difficulty Level</label>
                <input
                    id="difficulty_level"
                    type="text"
                    name="difficulty_level"
                    value="{{ old('difficulty_level', $question->difficulty_level) }}"
                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
            </div>
        </div>

        <template x-if="imageRecommended">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                This question type works best with an uploaded image. Non-verbal and spatial reasoning questions such as matrix, pattern, shape rotation, and mirror image usually need one.
            </div>
        </template>

        <div>
            <label for="question_text" class="block text-sm font-medium text-slate-700">Question Text</label>
            <textarea
                id="question_text"
                name="question_text"
                rows="4"
                class="mt-1 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
            >{{ old('question_text', $question->question_text) }}</textarea>
        </div>

        <div class="grid gap-6 md:grid-cols-[1fr_1fr]">
            <div>
                <label for="question_image" class="block text-sm font-medium text-slate-700">Question Image</label>
                <input
                    id="question_image"
                    type="file"
                    name="question_image"
                    accept="image/*"
                    @change="previewSelectedImage"
                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
                <p class="mt-2 text-xs text-slate-500">Images are stored on the public disk under <code>storage/app/public/cognitive-questions</code>.</p>
            </div>

            <div>
                <p class="block text-sm font-medium text-slate-700">Image Preview</p>
                <div class="mt-1 flex min-h-48 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                    <template x-if="imagePreview">
                        <img :src="imagePreview" alt="Question preview" class="max-h-44 rounded-2xl object-contain">
                    </template>
                    <template x-if="!imagePreview">
                        <p class="text-sm text-slate-500">No image uploaded yet.</p>
                    </template>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="block text-sm font-medium text-slate-700">Options</label>
                    <button
                        type="button"
                        @click="addOption"
                        class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Add Option
                    </button>
                </div>
                <div class="mt-3 space-y-3">
                    <template x-for="(option, index) in options" :key="index">
                        <div class="flex items-center gap-3">
                            <input
                                type="text"
                                :name="`options[${index}]`"
                                x-model="options[index]"
                                class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                required
                            >
                            <button
                                type="button"
                                @click="removeOption(index)"
                                class="inline-flex items-center rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                            >
                                Remove
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label for="correct_answer" class="block text-sm font-medium text-slate-700">Correct Answer</label>
                    <select
                        id="correct_answer"
                        name="correct_answer"
                        x-model="correctAnswer"
                        class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        required
                    >
                        <option value="">Select the correct answer</option>
                        <template x-for="option in cleanedOptions" :key="option">
                            <option :value="option" x-text="option"></option>
                        </template>
                    </select>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="marks" class="block text-sm font-medium text-slate-700">Marks</label>
                        <input
                            id="marks"
                            type="number"
                            name="marks"
                            min="1"
                            value="{{ old('marks', $question->marks ?? 1) }}"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            required
                        >
                    </div>

                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-slate-700">Sort Order</label>
                        <input
                            id="sort_order"
                            type="number"
                            name="sort_order"
                            value="{{ old('sort_order', $question->sort_order ?? 0) }}"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        >
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <input type="hidden" name="is_active" value="0">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $question->is_active ?? true))
                        class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                    >
                    <span class="text-sm font-medium text-slate-700">Keep this question active</span>
                </label>
            </div>
        </div>

        <div>
            <label for="explanation" class="block text-sm font-medium text-slate-700">Explanation</label>
            <textarea
                id="explanation"
                name="explanation"
                rows="4"
                class="mt-1 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
            >{{ old('explanation', $question->explanation) }}</textarea>
            <p class="mt-2 text-xs text-slate-500">This stays hidden during the attempt and can be shown later on review screens.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <button
                type="submit"
                class="inline-flex items-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
            >
                {{ $submitLabel }}
            </button>
            <a
                href="{{ route('admin.assessments.cognitive-skills-level-4.question-banks.show', $bank) }}"
                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
    function cognitiveBankQuestionForm(config) {
        return {
            options: [...(config.options || ['', ''])],
            questionType: config.initialType || 'mcq',
            imagePreview: config.initialImageUrl || null,
            correctAnswer: config.initialCorrectAnswer || '',
            recommendedTypes: config.recommendedTypes || [],

            get cleanedOptions() {
                return this.options
                    .map((option) => String(option || '').trim())
                    .filter((option) => option !== '');
            },

            get imageRecommended() {
                return this.recommendedTypes.includes(this.questionType);
            },

            addOption() {
                this.options.push('');
            },

            removeOption(index) {
                if (this.options.length <= 2) {
                    return;
                }

                if (String(this.options[index] || '').trim() === this.correctAnswer) {
                    this.correctAnswer = '';
                }

                this.options.splice(index, 1);
            },

            previewSelectedImage(event) {
                const file = event.target.files?.[0] || null;
                if (!file) {
                    return;
                }

                this.imagePreview = URL.createObjectURL(file);
            },
        };
    }
</script>
