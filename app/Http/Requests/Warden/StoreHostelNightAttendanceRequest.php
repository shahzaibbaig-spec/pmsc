<?php

namespace App\Http\Requests\Warden;

use Illuminate\Foundation\Http\FormRequest;

class StoreHostelNightAttendanceRequest extends FormRequest
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
            'attendance_date' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'rows.*.hostel_room_id' => ['nullable', 'integer', 'exists:hostel_rooms,id'],
            'rows.*.status' => ['required', 'in:present,absent,on_leave,late_return'],
            'rows.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

