<?php

namespace App\Modules\Classes\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('school_classes', 'name')->where(fn ($query) => $query->where('section', $this->input('section'))),
            ],
            'section' => ['nullable', 'string', 'max:20'],
        ];
    }
}

