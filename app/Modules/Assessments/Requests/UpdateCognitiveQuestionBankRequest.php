<?php

namespace App\Modules\Assessments\Requests;

use App\Models\CognitiveQuestionBank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCognitiveQuestionBankRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => trim((string) $this->input('slug')),
            'is_active' => $this->boolean('is_active'),
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
        /** @var CognitiveQuestionBank|null $bank */
        $bank = $this->route('bank');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cognitive_question_banks', 'slug')->ignore($bank?->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
