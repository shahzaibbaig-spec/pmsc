<?php

namespace App\Modules\Exams\Requests;

use App\Modules\Exams\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamSheetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->input('exam_id') === '') {
            $normalized['exam_id'] = null;
        }

        if ($this->input('sequence_number') === '') {
            $normalized['sequence_number'] = null;
        }

        if (trim((string) $this->input('topic')) === '') {
            $normalized['topic'] = null;
        }

        if ($this->input('exam_date') === '') {
            $normalized['exam_date'] = null;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->hasRole('Teacher') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', Rule::exists('school_classes', 'id')],
            'subject_id' => ['required', Rule::exists('subjects', 'id')],
            'exam_type' => ['required', Rule::in(array_column(ExamType::options(), 'value'))],
            'exam_id' => ['nullable', 'integer', Rule::exists('exams', 'id')],
            'topic' => ['nullable', 'string', 'max:255'],
            'sequence_number' => ['nullable', 'integer', Rule::in([1, 2, 3, 4])],
            'exam_date' => ['nullable', 'date'],
        ];
    }

}
