<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyDiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole('Teacher')
            && $user->can('edit_own_daily_diary');
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'session' => ['required', 'string', 'max:20'],
            'diary_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'homework_text' => ['required', 'string'],
            'instructions' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,docx,doc', 'max:10240'],
            'remove_attachment' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'session' => trim((string) $this->input('session', '')),
            'title' => trim((string) $this->input('title', '')) ?: null,
            'instructions' => trim((string) $this->input('instructions', '')) ?: null,
            'remove_attachment' => $this->has('remove_attachment') ? $this->boolean('remove_attachment') : false,
            'is_published' => $this->has('is_published') ? $this->boolean('is_published') : false,
        ]);
    }
}
