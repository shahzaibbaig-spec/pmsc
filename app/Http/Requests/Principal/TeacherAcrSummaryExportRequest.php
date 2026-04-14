<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherAcrSummaryExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Admin', 'Principal'])
            && (
                $user->can('export_teacher_acr_summary')
                || $user->can('view_teacher_acr')
            );
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'status' => ['nullable', Rule::in(['all', 'draft', 'reviewed', 'finalized'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'session' => trim((string) $this->input('session', '')),
            'status' => trim((string) $this->input('status', '')) ?: 'all',
        ]);
    }
}

