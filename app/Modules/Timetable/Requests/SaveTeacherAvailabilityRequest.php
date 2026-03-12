<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTeacherAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.day_of_week' => ['required', Rule::in(config('timetable.days', []))],
            'records.*.slot_index' => ['required', 'integer', 'min:1', 'max:20'],
            'records.*.is_available' => ['required', 'boolean'],
        ];
    }
}

