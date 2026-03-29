<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkTeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Principal', 'Admin'])
            && $user->can('assign_teachers');
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', Rule::exists('teachers', 'id')],
            'session' => ['required', 'string', 'max:20'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['required', 'integer', 'distinct', Rule::exists('school_classes', 'id')],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['required', 'integer', 'distinct', Rule::exists('subjects', 'id')],
            'class_teacher_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $classTeacherClassId = $this->input('class_teacher_class_id');
            if ($classTeacherClassId === null || $classTeacherClassId === '') {
                return;
            }

            $classIds = collect($this->input('class_ids', []))
                ->map(static fn ($id): int => (int) $id)
                ->all();

            if (! in_array((int) $classTeacherClassId, $classIds, true)) {
                $validator->errors()->add(
                    'class_teacher_class_id',
                    'The class teacher class must be one of the selected classes.'
                );
            }
        });
    }
}
