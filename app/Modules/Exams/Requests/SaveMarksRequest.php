<?php

namespace App\Modules\Exams\Requests;

use App\Modules\Exams\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMarksRequest extends FormRequest
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
            'total_marks' => ['required', 'integer', 'min:1', 'max:1000'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'records.*.obtained_marks' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

