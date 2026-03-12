<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_section_ids' => ['required', 'array', 'min:1'],
            'class_section_ids.*' => ['required', 'integer', 'distinct', 'exists:class_sections,id'],
        ];
    }
}
