<?php

namespace App\Http\Requests\Kcat;

use Illuminate\Foundation\Http\FormRequest;

class StoreKcatReportNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage_kcat_report_notes');
    }

    public function rules(): array
    {
        return [
            'strengths' => ['nullable', 'string'],
            'development_areas' => ['nullable', 'string'],
            'counselor_recommendation' => ['nullable', 'string'],
            'parent_summary' => ['nullable', 'string'],
            'private_notes' => ['nullable', 'string'],
            'visibility' => ['required', 'in:private,student,parent,student_parent'],
        ];
    }
}
