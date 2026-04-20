<?php

namespace App\Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Teacher') ?? false;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', Rule::exists('school_classes', 'id')],
            'date' => ['required', 'date_format:Y-m-d'],
            'session' => ['nullable', 'string', 'max:20'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'records.*.status' => ['required', Rule::in(['present', 'absent', 'leave'])],
        ];
    }
}
