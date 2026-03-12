<?php

namespace App\Modules\Search\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Principal']) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
        ];
    }
}
