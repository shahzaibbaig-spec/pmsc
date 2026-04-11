<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherAcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Admin', 'Principal'])
            && $user->can('manage_teacher_acr');
    }

    public function rules(): array
    {
        return [
            'conduct_score' => ['required', 'numeric', 'min:0', 'max:15'],
            'principal_score' => ['required', 'numeric', 'min:0', 'max:15'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'areas_for_improvement' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'confidential_remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'strengths' => $this->normalizeText('strengths'),
            'areas_for_improvement' => $this->normalizeText('areas_for_improvement'),
            'recommendations' => $this->normalizeText('recommendations'),
            'confidential_remarks' => $this->normalizeText('confidential_remarks'),
        ]);
    }

    private function normalizeText(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
