<?php

namespace App\Http\Requests\Principal;

use App\Models\TeacherAcr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherAcrIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Admin', 'Principal'])
            && $user->can('view_teacher_acr');
    }

    public function rules(): array
    {
        return [
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                TeacherAcr::STATUS_DRAFT,
                TeacherAcr::STATUS_REVIEWED,
                TeacherAcr::STATUS_FINALIZED,
            ])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'session' => trim((string) $this->input('session', '')) ?: null,
            'search' => trim((string) $this->input('search', '')) ?: null,
            'status' => trim((string) $this->input('status', '')) ?: null,
        ]);
    }
}
