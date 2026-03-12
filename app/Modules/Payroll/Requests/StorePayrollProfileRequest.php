<?php

namespace App\Modules\Payroll\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_payroll') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('payroll_profiles', 'user_id'),
            ],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_no' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
            'allowance_items' => ['nullable', 'array'],
            'allowance_items.*.title' => ['nullable', 'string', 'max:120'],
            'allowance_items.*.amount' => ['nullable', 'numeric', 'min:0'],
            'deduction_items' => ['nullable', 'array'],
            'deduction_items.*.title' => ['nullable', 'string', 'max:120'],
            'deduction_items.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
