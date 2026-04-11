<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTeacherAcrRequest extends FormRequest
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
            'session' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $teacherId = $this->input('teacher_id');

        $this->merge([
            'session' => trim((string) $this->input('session', '')),
            'teacher_id' => $teacherId === '' ? null : $teacherId,
        ]);
    }
}
