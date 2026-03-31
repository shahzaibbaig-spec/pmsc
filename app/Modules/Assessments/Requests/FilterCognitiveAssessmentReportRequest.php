<?php

namespace App\Modules\Assessments\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterCognitiveAssessmentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->hasAnyRole(['Admin', 'Principal']) ?? false)
            && ($user?->can('view_cognitive_assessment_reports') ?? false);
    }

    public function rules(): array
    {
        return [
            'class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
            'student_id' => ['nullable', 'integer', Rule::exists('students', 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
