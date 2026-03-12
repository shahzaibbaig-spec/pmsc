<?php

namespace App\Modules\Classes\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignClassSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', Rule::exists('subjects', 'id')],
        ];
    }
}

