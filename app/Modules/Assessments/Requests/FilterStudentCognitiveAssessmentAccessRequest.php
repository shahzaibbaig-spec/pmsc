<?php

namespace App\Modules\Assessments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterStudentCognitiveAssessmentAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->hasAnyRole(['Admin', 'Principal']) ?? false)
            && ($user?->can('manage_student_cognitive_assessment_access') ?? false);
    }

    public function rules(): array
    {
        return [
            'class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
            'search' => ['nullable', 'string', 'max:255'],
            'enabled_status' => ['nullable', Rule::in(['all', 'enabled', 'disabled'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
