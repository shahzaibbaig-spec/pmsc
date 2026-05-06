<?php

namespace App\Http\Requests\Kcat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreKcatTestRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_adaptive_enabled' => $this->boolean('is_adaptive_enabled'),
        ]);
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage_kcat_tests');
    }

    public function rules(): array
    {
        $isCreate = $this->routeIs('career-counselor.kcat.tests.store');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'grade_from' => ['nullable', 'integer', 'min:1', 'max:12'],
            'grade_to' => ['nullable', 'integer', 'min:1', 'max:12'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:draft,active,archived'],
            'is_adaptive_enabled' => ['nullable', 'boolean'],
            'questions_per_section' => ['nullable', 'integer', 'min:1', 'max:200'],
            'session' => ['nullable', 'string', 'max:20'],
            'question_count' => [$isCreate ? 'required' : 'nullable', 'integer', 'min:4', 'max:400'],
            'difficulty_level' => [
                $isCreate ? 'required' : 'nullable',
                Rule::in(['easy', 'medium', 'hard', 'auto']),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $gradeFrom = $this->filled('grade_from') ? (int) $this->input('grade_from') : null;
            $gradeTo = $this->filled('grade_to') ? (int) $this->input('grade_to') : null;

            if ($gradeFrom !== null && $gradeTo !== null && $gradeFrom > $gradeTo) {
                $validator->errors()->add('grade_from', 'Grade From must be less than or equal to Grade To.');
            }

            if (! $this->routeIs('career-counselor.kcat.tests.store')) {
                return;
            }

            $questionCount = (int) ($this->input('question_count') ?? 0);
            if ($questionCount > 0 && $questionCount % 4 !== 0) {
                $validator->errors()->add('question_count', 'Total questions must be a multiple of 4 for equal category distribution.');
            }
        });
    }
}
