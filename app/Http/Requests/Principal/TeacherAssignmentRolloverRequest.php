<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherAssignmentRolloverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Principal', 'Admin'])
            && $user->can('assign_teachers');
    }

    public function rules(): array
    {
        return [
            'from_session' => ['required', 'string', 'max:20'],
            'to_session' => ['required', 'string', 'max:20', 'different:from_session'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['required', 'integer', Rule::exists('teachers', 'id')],
            'overwrite' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $overwrite = $this->input('overwrite');

        $this->merge([
            'teacher_ids' => is_array($this->input('teacher_ids')) ? $this->input('teacher_ids') : [],
            'overwrite' => in_array($overwrite, [1, '1', true, 'true', 'on'], true),
        ]);
    }
}

