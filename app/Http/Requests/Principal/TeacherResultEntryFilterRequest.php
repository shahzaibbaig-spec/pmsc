<?php

namespace App\Http\Requests\Principal;

use App\Modules\Exams\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherResultEntryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Admin', 'Principal'])
            && (
                $user->can('view_teacher_result_entries')
                || $user->can('view_result_entry_logs')
            );
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'exam_type' => ['nullable', Rule::in(array_column(ExamType::options(), 'value'))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'teacher_id' => $this->filled('teacher_id') ? (int) $this->input('teacher_id') : null,
            'class_id' => $this->filled('class_id') ? (int) $this->input('class_id') : null,
            'subject_id' => $this->filled('subject_id') ? (int) $this->input('subject_id') : null,
            'session' => trim((string) $this->input('session', '')) ?: null,
            'exam_type' => trim((string) $this->input('exam_type', '')) ?: null,
            'date_from' => trim((string) $this->input('date_from', '')) ?: null,
            'date_to' => trim((string) $this->input('date_to', '')) ?: null,
        ]);
    }
}

