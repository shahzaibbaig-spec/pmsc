<?php

namespace App\Modules\Assessments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignSectionQuestionsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'section_id' => $this->input('section_id', $this->route('section')?->id),
            'bank_question_ids' => collect($this->input('bank_question_ids', []))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->values()
                ->all(),
            'sort_orders' => collect($this->input('sort_orders', []))
                ->mapWithKeys(fn ($sortOrder, $questionId): array => [(string) $questionId => (int) $sortOrder])
                ->all(),
        ]);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->hasRole('Admin') ?? false)
            && ($user?->can('manage_cognitive_assessment_setup') ?? false);
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', Rule::exists('cognitive_assessment_sections', 'id')],
            'bank_question_ids' => ['required', 'array', 'min:1'],
            'bank_question_ids.*' => ['required', 'integer', Rule::exists('cognitive_bank_questions', 'id')],
            'sort_orders' => ['nullable', 'array'],
            'sort_orders.*' => ['nullable', 'integer'],
        ];
    }
}
