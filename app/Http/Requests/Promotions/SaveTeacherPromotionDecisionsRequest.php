<?php

namespace App\Http\Requests\Promotions;

use App\Models\StudentPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTeacherPromotionDecisionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Teacher') ?? false;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.id' => ['required', 'integer', Rule::exists('student_promotions', 'id')],
            'rows.*.teacher_decision' => ['nullable', Rule::in(StudentPromotion::DECISIONS)],
            'rows.*.teacher_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

