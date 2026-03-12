<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherTimetableApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Principal', 'Teacher']) ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ];
    }
}
