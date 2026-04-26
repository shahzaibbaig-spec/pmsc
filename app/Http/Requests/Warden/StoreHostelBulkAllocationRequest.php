<?php

namespace App\Http\Requests\Warden;

use Illuminate\Foundation\Http\FormRequest;

class StoreHostelBulkAllocationRequest extends FormRequest
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
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'exists:students,id', 'distinct'],
            'hostel_id' => ['required', 'integer', 'exists:hostels,id'],
            'allocated_from' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
