<?php

namespace App\Modules\Timetable\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimetableConstraintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'string', 'max:20'],
            'max_periods_per_day_teacher' => ['required', 'integer', 'min:1', 'max:12'],
            'max_periods_per_week_teacher' => ['required', 'integer', 'min:1', 'max:60'],
            'max_periods_per_day_class' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}

