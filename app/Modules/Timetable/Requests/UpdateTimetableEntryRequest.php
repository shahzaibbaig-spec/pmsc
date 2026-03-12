<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimetableEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'entry_id' => ['nullable', 'integer', 'exists:timetable_entries,id'],
            'session' => ['required', 'string', 'max:20'],
            'class_section_id' => ['required', 'integer', 'exists:class_sections,id'],
            'day_of_week' => ['required', Rule::in(config('timetable.days', []))],
            'slot_index' => ['required', 'integer', 'min:1', 'max:20'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'validate_only' => ['nullable', 'boolean'],
        ];
    }
}
