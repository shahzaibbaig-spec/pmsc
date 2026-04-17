<?php

namespace App\Modules\Medical\Requests;

use App\Models\User;
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
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    $doctorId = (int) $value;
                    if ($doctorId <= 0) {
                        $fail('Please select a valid doctor.');
                        return;
                    }

                    $isDoctor = User::query()
                        ->whereKey($doctorId)
                        ->role('Doctor')
                        ->exists();

                    if (! $isDoctor) {
                        $fail('Selected doctor account is not valid.');
                    }
                },
            ],
            'illness_type' => ['required', Rule::in(['fever', 'headache', 'stomach_ache', 'other'])],
            'illness_other_text' => ['nullable', 'string', 'max:255', 'required_if:illness_type,other'],
        ];
    }
}
