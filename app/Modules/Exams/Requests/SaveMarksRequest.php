<?php

namespace App\Modules\Exams\Requests;

use App\Services\AssessmentMarkingModeService;
use App\Services\ClassAssessmentModeService;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Teacher') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->input('total_marks') === '') {
            $normalized['total_marks'] = null;
        }

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

    public function rules(): array
    {
        $markingModeService = app(AssessmentMarkingModeService::class);
        $assessmentModeService = app(ClassAssessmentModeService::class);
        $usesGradeSystem = $this->usesGradeSystem($markingModeService);

        return [
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', Rule::exists('school_classes', 'id')],
            'subject_id' => ['required', Rule::exists('subjects', 'id')],
            'exam_type' => ['required', Rule::in(array_column(ExamType::options(), 'value'))],
            'exam_id' => ['nullable', 'integer', Rule::exists('exams', 'id')],
            'topic' => ['nullable', 'string', 'max:255'],
            'sequence_number' => ['nullable', 'integer', Rule::in([1, 2, 3, 4])],
            'exam_date' => ['nullable', 'date'],
            'total_marks' => $usesGradeSystem
                ? ['nullable']
                : ['required', 'integer', 'min:1', 'max:1000'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'records.*.obtained_marks' => $usesGradeSystem
                ? ['nullable']
                : ['nullable', 'numeric', 'min:0'],
            'records.*.grade' => $usesGradeSystem
                ? ['nullable', 'string', Rule::in($assessmentModeService->gradeCodes())]
                : ['nullable'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $markingModeService = app(AssessmentMarkingModeService::class);
            $assessmentModeService = app(ClassAssessmentModeService::class);
            $usesGradeSystem = $this->usesGradeSystem($markingModeService);
            $totalMarks = is_numeric($this->input('total_marks')) ? (float) $this->input('total_marks') : null;
            $examType = trim((string) $this->input('exam_type'));
            $hasExamId = $this->filled('exam_id');

            if (! $hasExamId && $examType === ExamType::ClassTest->value && trim((string) $this->input('topic')) === '') {
                $validator->errors()->add('topic', 'Class Test Topic is required.');
            }

            if (! $hasExamId && $examType === ExamType::BimonthlyTest->value) {
                $sequence = $this->input('sequence_number');
                if (! in_array((int) $sequence, [1, 2, 3, 4], true)) {
                    $validator->errors()->add('sequence_number', 'Select bimonthly number from 1st to 4th.');
                }
            }

            if (
                ! $hasExamId
                && in_array($examType, [ExamType::ClassTest->value, ExamType::BimonthlyTest->value], true)
                && trim((string) $this->input('exam_date')) === ''
            ) {
                $validator->errors()->add('exam_date', 'Exam Date is required for Class Test and Bimonthly.');
            }

            foreach ((array) $this->input('records', []) as $index => $row) {
                $grade = $assessmentModeService->normalizeGrade(is_string($row['grade'] ?? null) ? $row['grade'] : null);
                $marks = $row['obtained_marks'] ?? null;

                if ($usesGradeSystem) {
                    if ($marks !== null && $marks !== '') {
                        $validator->errors()->add("records.$index.obtained_marks", 'Marks are not allowed for grade-based classes.');
                    }

                    if ($grade !== null && ! $assessmentModeService->isValidGrade($grade)) {
                        $validator->errors()->add("records.$index.grade", 'The selected grade is invalid.');
                    }

                    continue;
                }

                if ($grade !== null) {
                    $validator->errors()->add("records.$index.grade", 'Grade is only allowed for PG, Prep, Nursery, and Class 1.');
                }

                if ($marks !== null && $marks !== '' && $totalMarks !== null && is_numeric($marks) && (float) $marks > $totalMarks) {
                    $validator->errors()->add("records.$index.obtained_marks", 'Obtained marks must be between 0 and total marks.');
                }
            }
        });
    }

    private function usesGradeSystem(AssessmentMarkingModeService $markingModeService): bool
    {
        $classId = (int) $this->input('class_id');
        $subjectId = (int) $this->input('subject_id');
        $session = trim((string) $this->input('session'));
        $examType = trim((string) $this->input('exam_type'));

        if ($classId <= 0 || $session === '' || $examType === '') {
            return false;
        }

        $mode = $markingModeService->resolveMarkingModeForExamContext(
            $classId,
            $session,
            $examType,
            $subjectId > 0 ? $subjectId : null
        );

        return $mode === AssessmentMarkingModeService::MODE_GRADE;
    }
}
