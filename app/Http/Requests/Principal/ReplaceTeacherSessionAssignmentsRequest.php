<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReplaceTeacherSessionAssignmentsRequest extends FormRequest
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
            'session' => ['required', 'string', 'max:20'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['required', 'integer', 'distinct', Rule::exists('school_classes', 'id')],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['required', 'integer', 'distinct', Rule::exists('subjects', 'id')],
            'class_teacher_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'class_ids' => is_array($this->input('class_ids')) ? $this->input('class_ids') : [],
            'subject_ids' => is_array($this->input('subject_ids')) ? $this->input('subject_ids') : [],
        ]);
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

