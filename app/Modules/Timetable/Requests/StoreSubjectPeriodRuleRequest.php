<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectPeriodRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_section_id' => ['required', 'integer', 'exists:class_sections,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'periods_per_week' => ['required', 'integer', 'min:1', 'max:20'],
            'id' => ['nullable', 'integer', Rule::exists('subject_period_rules', 'id')],
        ];
    }
}

