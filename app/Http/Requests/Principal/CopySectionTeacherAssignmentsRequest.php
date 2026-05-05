<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopySectionTeacherAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Principal', 'Admin'])
            && (
                $user->can('copy_teacher_assignments')
                || $user->can('manage_teacher_assignments')
                || $user->can('assign_teachers')
            );
    }

    public function rules(): array
    {
        return [
            'source_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'source_section' => ['nullable', 'string', 'max:20'],
            'target_class_id' => [
                'required',
                'integer',
                'different:source_class_id',
                Rule::exists('school_classes', 'id'),
            ],
            'target_section' => ['nullable', 'string', 'max:20'],
            'session' => ['required', 'string', 'max:20'],
            'copy_mode' => ['required', Rule::in(['copy_missing_only', 'replace_target_allocations'])],
        ];
    }
}
