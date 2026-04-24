<?php

namespace App\Modules\Medical\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDirectMedicalVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->hasRole('Doctor') ?? false)
            && ($this->user()?->can('create_direct_medical_visit') ?? false);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', Rule::exists('students', 'id')],
            'visit_date' => ['required', 'date'],
            'problem' => ['required', 'string', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'prescription' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'session' => ['required', 'regex:/^\d{4}-\d{4}$/'],
        ];
    }
}
