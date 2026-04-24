<?php

namespace App\Http\Requests\Warden;

use Illuminate\Foundation\Http\FormRequest;

class StoreWardenDailyReportRequest extends FormRequest
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
            'report_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'attendance.*.status' => ['required', 'in:present,absent,on_leave'],
            'attendance.*.remarks' => ['nullable', 'string', 'max:1000'],

            'discipline' => ['nullable', 'array'],
            'discipline.*.student_id' => ['nullable', 'integer', 'exists:students,id'],
            'discipline.*.issue_type' => ['nullable', 'string', 'max:255'],
            'discipline.*.severity' => ['nullable', 'in:low,medium,high'],
            'discipline.*.description' => ['nullable', 'string'],
            'discipline.*.action_taken' => ['nullable', 'string'],

            'health' => ['nullable', 'array'],
            'health.*.student_id' => ['nullable', 'integer', 'exists:students,id'],
            'health.*.condition' => ['nullable', 'string'],
            'health.*.temperature' => ['nullable', 'numeric', 'between:0,999.9'],
            'health.*.medication' => ['nullable', 'string'],
            'health.*.doctor_visit' => ['nullable', 'boolean'],
        ];
    }
}

