<?php

namespace App\Http\Requests\Warden;

use Illuminate\Foundation\Http\FormRequest;

class WardenDailyDiaryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'is_published' => ['nullable', 'in:0,1'],
        ];
    }
}
