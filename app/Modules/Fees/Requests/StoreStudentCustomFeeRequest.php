<?php

namespace App\Modules\Fees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentCustomFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->hasAnyRole(['Admin', 'Accountant']) ?? false)
            && ($user?->can('edit_fee_structure') ?? false);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'session' => ['required', 'string', 'max:20'],
            'tuition_fee' => ['required', 'numeric', 'min:0'],
            'computer_fee' => ['required', 'numeric', 'min:0'],
            'exam_fee' => ['required', 'numeric', 'min:0'],
        ];
    }
}
