<?php

namespace App\Modules\Results\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentResultIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Student') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['nullable', 'string', 'max:20'],
        ];
    }
}
