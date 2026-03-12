<?php

namespace App\Modules\Subjects\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleSubjectMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', Rule::exists('school_classes', 'id')],
            'student_id' => ['required', Rule::exists('students', 'id')],
            'subject_id' => ['required', Rule::exists('subjects', 'id')],
            'assigned' => ['required', 'boolean'],
        ];
    }
}

