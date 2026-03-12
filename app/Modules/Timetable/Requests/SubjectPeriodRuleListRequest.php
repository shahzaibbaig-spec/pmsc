<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectPeriodRuleListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'session' => ['nullable', 'string', 'max:20'],
            'class_section_id' => ['nullable', 'integer', 'exists:class_sections,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}

