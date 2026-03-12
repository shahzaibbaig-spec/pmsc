<?php

namespace App\Modules\Subjects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_subject_assignments') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

