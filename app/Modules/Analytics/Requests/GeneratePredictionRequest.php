<?php

namespace App\Modules\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePredictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'target_exam' => ['required', Rule::in(['first_term', 'final_term'])],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ];
    }
}
