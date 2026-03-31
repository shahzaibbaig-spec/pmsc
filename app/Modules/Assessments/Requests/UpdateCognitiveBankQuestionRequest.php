<?php

namespace App\Modules\Assessments\Requests;

use App\Models\CognitiveAssessmentSection;
use App\Models\CognitiveBankQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCognitiveBankQuestionRequest extends FormRequest
{
    private const IMAGE_RECOMMENDED_TYPES = ['matrix', 'pattern', 'shape_rotation', 'mirror_image'];

    protected function prepareForValidation(): void
    {
        /** @var CognitiveBankQuestion|null $question */
        $question = $this->route('question');

        $options = collect($this->input('options', []))
            ->map(fn ($option): string => trim((string) $option))
            ->filter(fn (string $option): bool => $option !== '')
            ->values()
            ->all();

        $this->merge([
            'question_bank_id' => $this->input('question_bank_id', $question?->question_bank_id),
            'options' => $options,
            'correct_answer' => trim((string) $this->input('correct_answer')),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', $question?->sort_order ?? 0),
        ]);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->hasRole('Admin') ?? false)
            && ($user?->can('manage_cognitive_question_banks') ?? false);
    }

    public function rules(): array
    {
        return [
            'question_bank_id' => ['required', 'integer', Rule::exists('cognitive_question_banks', 'id')],
            'skill' => ['required', Rule::in([
                CognitiveAssessmentSection::SKILL_VERBAL,
                CognitiveAssessmentSection::SKILL_NON_VERBAL,
                CognitiveAssessmentSection::SKILL_QUANTITATIVE,
                CognitiveAssessmentSection::SKILL_SPATIAL,
            ])],
            'question_type' => ['required', 'string', 'max:100'],
            'difficulty_level' => ['nullable', 'string', 'max:50'],
            'question_text' => ['nullable', 'string'],
            'question_image' => ['nullable', 'image', 'max:5120'],
            'explanation' => ['nullable', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string'],
            'correct_answer' => ['required', 'string'],
            'marks' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var CognitiveBankQuestion|null $question */
            $question = $this->route('question');

            $options = collect($this->input('options', []))
                ->map(fn ($option): string => trim((string) $option))
                ->values();
            $correctAnswer = trim((string) $this->input('correct_answer'));

            if ($options->isNotEmpty() && ! $options->contains($correctAnswer)) {
                $validator->errors()->add('correct_answer', 'The correct answer must match one of the provided options.');
            }

            if (in_array((string) $this->input('question_type'), self::IMAGE_RECOMMENDED_TYPES, true)
                && ! $this->hasFile('question_image')
                && empty($question?->question_image)
                && trim((string) $this->input('question_text')) === '') {
                $validator->errors()->add('question_text', 'Add question text or upload a question image for image-based reasoning types.');
            }
        });
    }
}
