<?php

namespace App\Modules\Assessments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnableStudentCognitiveAssessmentRequest extends FormRequest
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
            'principal_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
