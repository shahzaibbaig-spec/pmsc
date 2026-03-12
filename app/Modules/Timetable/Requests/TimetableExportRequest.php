<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimetableExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'type' => ['nullable', Rule::in(['class', 'teacher'])],
            'class_section_id' => ['nullable', 'integer', 'exists:class_sections,id', 'required_if:type,class'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id', 'required_if:type,teacher'],
        ];
    }
}
