<?php

namespace App\Http\Requests\Promotions;

use App\Models\StudentPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyPrincipalPromotionGroupActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Principal']) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', Rule::exists('students', 'id')],
            'decision' => ['required', Rule::in(StudentPromotion::DECISIONS)],
            'note' => [
                Rule::requiredIf(function (): bool {
                    return in_array((string) $this->input('decision'), [
                        StudentPromotion::DECISION_CONDITIONAL_PROMOTE,
                        StudentPromotion::DECISION_RETAIN,
                    ], true);
                }),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
