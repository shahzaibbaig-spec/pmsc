<?php

namespace App\Http\Requests\Warden;

use Illuminate\Foundation\Http\FormRequest;

class WardenDisciplineFilterRequest extends FormRequest
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
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'incident_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
