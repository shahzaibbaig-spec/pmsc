<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePrincipalPromotionCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Principal']) ?? false;
    }

    public function rules(): array
    {
        return [
            'from_session' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
            'to_session' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/', 'different:from_session'],
            'class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
        ];
    }
}
