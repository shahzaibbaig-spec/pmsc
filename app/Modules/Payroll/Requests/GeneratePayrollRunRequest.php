<?php

namespace App\Modules\Payroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $hasFinanceRole = $user?->hasAnyRole(['Admin', 'Accountant']) ?? false;

        return $hasFinanceRole && (
            ($user?->can('generate_payroll') ?? false)
            || ($user?->can('generate_salary_sheet') ?? false)
        );
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }
}
