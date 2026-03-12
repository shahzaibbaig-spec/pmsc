<?php

namespace App\Modules\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherAnalyticsDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}

