<?php

namespace App\Modules\Subjects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignClassStudentSubjectsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('subject_ids') && $this->has('subjects')) {
            $this->merge([
                'subject_ids' => $this->input('subjects'),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage_subject_assignments') ?? false;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'session' => ['required', 'string', 'max:20'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ];
    }
}
