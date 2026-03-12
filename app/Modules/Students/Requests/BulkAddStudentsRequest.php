<?php

namespace App\Modules\Students\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAddStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'string', 'max:1000000'],
            'update_existing' => ['nullable', 'boolean'],
        ];
    }
}

