<?php

namespace App\Modules\Medical\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicalReferralListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('view_medical_requests') || $user->can('view_all_medical_records');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'completed'])],
            'source_type' => ['nullable', Rule::in(['principal_referral', 'doctor_direct'])],
            'doctor_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'student_id' => ['nullable', 'integer', Rule::exists('students', 'id')],
            'class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'has_cbc_report' => ['nullable', 'boolean'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
