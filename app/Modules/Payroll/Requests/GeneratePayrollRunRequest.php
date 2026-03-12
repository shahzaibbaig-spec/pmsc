<?php

namespace App\Modules\Payroll\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('generate_salary_sheet') ?? false;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }
}
