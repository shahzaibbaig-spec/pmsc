<?php

namespace App\Modules\Medical\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', Rule::exists('students', 'id')],
            'illness_type' => ['required', Rule::in(['fever', 'headache', 'stomach_ache', 'other'])],
            'illness_other_text' => ['nullable', 'string', 'max:255', 'required_if:illness_type,other'],
        ];
    }
}

