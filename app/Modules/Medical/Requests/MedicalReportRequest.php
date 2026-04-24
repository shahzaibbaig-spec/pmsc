<?php

namespace App\Modules\Medical\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Principal', 'Doctor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(['monthly', 'yearly'])],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
