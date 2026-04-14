<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'assignment_session' => ['nullable', 'string', 'max:20'],
            'assignment_class_ids' => ['nullable', 'array'],
            'assignment_class_ids.*' => ['required', 'integer', 'distinct', Rule::exists('school_classes', 'id')],
            'assignment_subject_ids' => ['nullable', 'array'],
            'assignment_subject_ids.*' => ['required', 'integer', 'distinct', Rule::exists('subjects', 'id')],
            'class_teacher_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assignment_class_ids' => is_array($this->input('assignment_class_ids')) ? $this->input('assignment_class_ids') : [],
            'assignment_subject_ids' => is_array($this->input('assignment_subject_ids')) ? $this->input('assignment_subject_ids') : [],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $role = strtolower(trim((string) $this->input('role')));
            if ($role !== 'teacher') {
                return;
            }

            $classIds = collect($this->input('assignment_class_ids', []))
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            $subjectIds = collect($this->input('assignment_subject_ids', []))
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            $classTeacherClassId = $this->input('class_teacher_class_id');
            $classTeacherClassId = $classTeacherClassId !== null && $classTeacherClassId !== ''
                ? (int) $classTeacherClassId
                : null;

            if ($classTeacherClassId !== null && ! $classIds->contains($classTeacherClassId)) {
                $validator->errors()->add(
                    'class_teacher_class_id',
                    'The class teacher class must be one of the selected classes.'
                );
            }

            if ($subjectIds->isNotEmpty() && $classIds->isEmpty()) {
                $validator->errors()->add(
                    'assignment_class_ids',
                    'Please select at least one class when subject assignments are provided.'
                );
            }

            if ($classIds->isNotEmpty() && $subjectIds->isEmpty() && $classTeacherClassId === null) {
                $validator->errors()->add(
                    'assignment_subject_ids',
                    'Please select at least one subject or choose a class teacher class.'
                );
            }
        });
    }
}
