<?php

namespace App\Modules\Medical\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentCbcReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Doctor')) {
            return $user->can('create_cbc_report');
        }

        return $user->hasAnyRole(['Principal', 'Admin']) && $user->can('view_all_cbc_reports');
    }

    public function rules(): array
    {
        return [
            'student_medical_record_id' => ['nullable', Rule::exists('medical_referrals', 'id')],
            'student_id' => ['required', Rule::exists('students', 'id')],
            'report_date' => ['required', 'date'],
            'session' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'machine_report_no' => ['nullable', 'string', 'max:100'],
            'hemoglobin' => ['nullable', 'numeric'],
            'rbc_count' => ['nullable', 'numeric'],
            'wbc_count' => ['nullable', 'numeric'],
            'platelet_count' => ['nullable', 'numeric'],
            'hematocrit_pcv' => ['nullable', 'numeric'],
            'mcv' => ['nullable', 'numeric'],
            'mch' => ['nullable', 'numeric'],
            'mchc' => ['nullable', 'numeric'],
            'neutrophils' => ['nullable', 'numeric'],
            'lymphocytes' => ['nullable', 'numeric'],
            'monocytes' => ['nullable', 'numeric'],
            'eosinophils' => ['nullable', 'numeric'],
            'basophils' => ['nullable', 'numeric'],
            'esr' => ['nullable', 'numeric'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
