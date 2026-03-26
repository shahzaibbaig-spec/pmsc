<?php

namespace App\Http\Requests\Promotions;

use App\Models\StudentPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrincipalReviewPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Principal']) ?? false;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['required', 'integer', Rule::exists('student_promotions', 'id')],
            'rows.*.principal_decision' => ['nullable', Rule::in(StudentPromotion::DECISIONS)],
            'rows.*.principal_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

