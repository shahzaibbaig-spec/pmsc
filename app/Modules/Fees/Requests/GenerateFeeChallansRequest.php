<?php

namespace App\Modules\Fees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateFeeChallansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('generate_fee_challans') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'due_date' => ['required', 'date'],
        ];
    }
}
