<?php

namespace App\Modules\Results\Requests;

use App\Modules\Exams\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentResultPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', Rule::exists('students', 'id')],
            'session' => ['required', 'string', 'max:20'],
            'exam_type' => ['required', Rule::in(array_column(ExamType::options(), 'value'))],
        ];
    }
}

