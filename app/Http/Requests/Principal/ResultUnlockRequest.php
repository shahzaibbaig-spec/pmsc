<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;

class ResultUnlockRequest extends FormRequest
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
            'session' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'session' => trim((string) $this->input('session', '')),
            'class_id' => (int) $this->input('class_id'),
            'exam_id' => $this->filled('exam_id') ? (int) $this->input('exam_id') : null,
            'reason' => trim((string) $this->input('reason', '')),
        ]);
    }
}
