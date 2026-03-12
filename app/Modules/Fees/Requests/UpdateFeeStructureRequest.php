<?php

namespace App\Modules\Fees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_fee_structure') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0'],
            'fee_type' => ['required', 'string', 'max:50'],
            'is_monthly' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
