<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRankingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole('Principal')
            && $user->can('view_teacher_performance');
    }

    public function rules(): array
    {
        return [
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'exam_type' => [
                'nullable',
                Rule::in(['overall', 'class_test', 'bimonthly', 'bimonthly_test', 'first_term', 'final_term']),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $session = trim((string) $this->input('session', ''));
        $examType = trim((string) $this->input('exam_type', ''));

        $this->merge([
            'session' => $session !== '' ? $session : null,
            'exam_type' => $examType !== '' ? $examType : 'overall',
        ]);
    }
}
