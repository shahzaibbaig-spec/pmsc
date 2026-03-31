<?php

namespace App\Modules\Exams\Requests;

use App\Models\Mark;
use App\Services\ClassAssessmentModeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMarkEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Teacher') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('obtained_marks') === '') {
            $this->merge(['obtained_marks' => null]);
        }

        if ($this->input('grade') === '') {
            $this->merge(['grade' => null]);
        }
    }

    public function rules(): array
    {
        $assessmentModeService = app(ClassAssessmentModeService::class);
        $usesGradeSystem = $this->usesGradeSystem($assessmentModeService);

        return [
            'obtained_marks' => $usesGradeSystem
                ? ['nullable']
                : ['required', 'integer', 'min:0'],
            'grade' => $usesGradeSystem
                ? ['required', 'string', Rule::in($assessmentModeService->gradeCodes())]
                : ['nullable'],
            'edit_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $assessmentModeService = app(ClassAssessmentModeService::class);
            $usesGradeSystem = $this->usesGradeSystem($assessmentModeService);
            $mark = $this->route('mark');

            if (! $mark instanceof Mark) {
                return;
            }

            if ($usesGradeSystem) {
                if ($this->filled('obtained_marks')) {
                    $validator->errors()->add('obtained_marks', 'Numeric marks are not allowed for grade-based classes.');
                }

                return;
            }

            if ($this->filled('grade')) {
                $validator->errors()->add('grade', 'Grade is only allowed for PG, Prep, Nursery, and Class 1.');
            }

            $totalMarks = (int) ($mark->total_marks ?? $mark->exam?->total_marks ?? 0);
            $obtainedMarks = $this->input('obtained_marks');
            if ($obtainedMarks !== null && $totalMarks > 0 && (int) $obtainedMarks > $totalMarks) {
                $validator->errors()->add('obtained_marks', 'Obtained marks must be between 0 and total marks.');
            }
        });
    }

    private function usesGradeSystem(ClassAssessmentModeService $assessmentModeService): bool
    {
        $mark = $this->route('mark');

        if (! $mark instanceof Mark) {
            return false;
        }

        $mark->loadMissing('exam.classRoom:id,name,section');

        return $assessmentModeService->classUsesGradeSystem($mark->exam?->classRoom);
    }
}
