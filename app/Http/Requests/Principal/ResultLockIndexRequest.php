<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;

class ResultLockIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Admin', 'Principal'])
            && $user->can('generate_results');
    }

    public function rules(): array
    {
        return [
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'session' => trim((string) $this->input('session', '')) ?: null,
            'class_id' => $this->filled('class_id') ? (int) $this->input('class_id') : null,
            'exam_id' => $this->filled('exam_id') ? (int) $this->input('exam_id') : null,
        ]);
    }
}
