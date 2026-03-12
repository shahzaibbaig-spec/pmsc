<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegenerateTimeSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'periods_per_day' => ['required', 'integer', 'min:1', 'max:12'],
            'start_time' => ['required', 'date_format:H:i'],
            'period_minutes' => ['required', 'integer', 'min:20', 'max:120'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:30'],
            'days' => ['nullable', 'array', 'min:1'],
            'days.*' => [Rule::in(config('timetable.days', []))],
        ];
    }
}

