<?php

namespace App\Http\Requests\Warden;

use Illuminate\Foundation\Http\FormRequest;

class StoreHostelLeaveRequest extends FormRequest
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
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'hostel_room_id' => ['nullable', 'integer', 'exists:hostel_rooms,id'],
            'leave_from' => ['required', 'date'],
            'leave_to' => ['required', 'date', 'after_or_equal:leave_from'],
            'reason' => ['required', 'string'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

