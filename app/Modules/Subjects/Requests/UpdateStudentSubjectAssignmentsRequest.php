<?php

namespace App\Modules\Subjects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentSubjectAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_subject_assignments') ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'session' => ['required', 'string', 'max:20'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
        ];
    }
}
