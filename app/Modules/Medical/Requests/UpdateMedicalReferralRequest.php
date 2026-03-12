<?php

namespace App\Modules\Medical\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicalReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Doctor') ?? false;
    }

    public function rules(): array
    {
        return [
            'diagnosis' => ['required', 'string', 'max:2000'],
            'prescription' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['pending', 'completed'])],
        ];
    }
}

