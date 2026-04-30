<?php

namespace App\Http\Requests\Kcat;

use Illuminate\Foundation\Http\FormRequest;

class StoreKcatAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('assign_kcat_tests');
    }

    public function rules(): array
    {
        return [
            'kcat_test_id' => ['required', 'exists:kcat_tests,id'],
            'assigned_to_type' => ['required', 'in:student,class'],
            'student_id' => ['required_if:assigned_to_type,student', 'nullable', 'exists:students,id'],
            'class_id' => ['required_if:assigned_to_type,class', 'nullable', 'exists:school_classes,id'],
            'section' => ['nullable', 'string', 'max:80'],
            'session' => ['nullable', 'string', 'max:20'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
