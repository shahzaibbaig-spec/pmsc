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
        if ($this->input('total_marks') === '') {
            $this->merge(['total_marks' => null]);
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
