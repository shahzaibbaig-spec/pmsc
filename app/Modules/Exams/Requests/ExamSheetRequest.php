<?php

namespace App\Modules\Exams\Requests;

use App\Modules\Exams\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Teacher') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', Rule::exists('school_classes', 'id')],
            'subject_id' => ['required', Rule::exists('subjects', 'id')],
            'exam_type' => ['required', Rule::in(array_column(ExamType::options(), 'value'))],
        ];
    }
}

