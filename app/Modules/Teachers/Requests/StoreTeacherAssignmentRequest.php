<?php

namespace App\Modules\Teachers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', Rule::exists('teachers', 'id')],
            'class_id' => ['required', Rule::exists('school_classes', 'id')],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')],
            'is_class_teacher' => ['required', 'boolean'],
            'session' => ['required', 'string', 'max:20'],
        ];
    }
}

