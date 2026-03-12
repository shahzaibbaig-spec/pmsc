<?php

namespace App\Modules\Fees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordFeePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->hasAnyRole(['Admin', 'Accountant']) ?? false)
            && ($user?->can('record_fee_payment') ?? false);
    }

    public function rules(): array
    {
        return [
            'challan_id' => ['required', 'integer', Rule::exists('fee_challans', 'id')],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
