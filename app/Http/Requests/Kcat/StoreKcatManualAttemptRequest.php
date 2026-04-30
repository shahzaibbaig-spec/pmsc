<?php

namespace App\Http\Requests\Kcat;

use Illuminate\Foundation\Http\FormRequest;

class StoreKcatManualAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manually_enter_kcat_attempt');
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'answers' => ['required', 'array'],
        ];
    }
}
