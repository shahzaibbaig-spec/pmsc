<?php

namespace App\Modules\Results\Requests;

use App\Modules\Exams\Enums\ExamType;
use App\Services\AssessmentMarkingModeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigureAssessmentMarkingModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['Admin', 'Principal']);
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'session' => ['required', 'string', 'max:20'],
            'exam_type' => ['required', 'string', Rule::in(array_column(ExamType::options(), 'value'))],
            'marking_mode' => ['required', 'string', Rule::in([
                AssessmentMarkingModeService::MODE_NUMERIC,
                AssessmentMarkingModeService::MODE_GRADE,
            ])],
        ];
    }
}

