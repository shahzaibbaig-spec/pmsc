<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;

class PrincipalApproveCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Principal']) ?? false;
    }

    public function rules(): array
    {
        return [
            'principal_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

