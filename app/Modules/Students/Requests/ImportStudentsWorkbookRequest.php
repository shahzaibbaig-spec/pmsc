<?php

namespace App\Modules\Students\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentsWorkbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'workbook' => ['required', 'file', 'mimes:xls,xlsx,csv,txt', 'max:51200'],
            'update_existing' => ['nullable', 'boolean'],
        ];
    }
}

