<?php

namespace App\Modules\Subjects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentSubjectAssignmentMatrixQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_subject_assignments') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:60'],
        ];
    }
}
