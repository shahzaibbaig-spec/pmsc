<?php

namespace App\Http\Requests\Warden;

use Illuminate\Foundation\Http\FormRequest;

class WardenStudentRecordFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'session' => ['nullable', 'string', 'max:20'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
