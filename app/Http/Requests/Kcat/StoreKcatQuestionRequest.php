<?php

namespace App\Http\Requests\Kcat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreKcatQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage_kcat_questions');
    }

    public function rules(): array
    {
        return [
            'kcat_section_id' => ['required', 'exists:kcat_sections,id'],
            'question_type' => ['required', 'string', 'in:mcq,image_mcq,matrix,sequence,analogy,odd_one_out'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'question_text' => ['required_without:question_image', 'nullable', 'string'],
            'question_image' => ['nullable', 'image'],
            'explanation' => ['nullable', 'string'],
            'marks' => ['required', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.option_text' => ['nullable', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $correct = collect($this->input('options', []))->filter(fn ($option) => (bool) ($option['is_correct'] ?? false))->count();
                if ($correct !== 1) {
                    $validator->errors()->add('options', 'Exactly one option must be marked correct.');
                }
            },
        ];
    }
}
