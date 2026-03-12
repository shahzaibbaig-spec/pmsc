<?php

namespace App\Modules\Subjects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectGroupQueryRequest extends FormRequest
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
        ];
    }
}

