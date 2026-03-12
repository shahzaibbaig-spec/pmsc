<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportTimetableWorkbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'workbook' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
            'session' => ['nullable', 'string', 'regex:/^\d{4}-\d{4}$/'],
        ];
    }
}

