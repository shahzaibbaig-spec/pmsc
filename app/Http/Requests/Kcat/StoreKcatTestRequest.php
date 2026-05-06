<?php

namespace App\Http\Requests\Kcat;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
