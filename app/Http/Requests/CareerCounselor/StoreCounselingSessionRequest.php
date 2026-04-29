<?php

namespace App\Http\Requests\CareerCounselor;

use Illuminate\Foundation\Http\FormRequest;

class StoreCounselingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create_counseling_session');
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'counseling_date' => ['required', 'date'],
            'discussion_topic' => ['nullable', 'string', 'max:255'],
            'student_interests' => ['nullable', 'string'],
            'academic_concerns' => ['nullable', 'string'],
            'recommended_subjects' => ['nullable', 'string'],
            'recommended_career_path' => ['nullable', 'string'],
            'counselor_advice' => ['nullable', 'string'],
            'private_notes' => ['nullable', 'string'],
            'visibility' => ['nullable', 'in:private,student,parent,student_parent'],
            'public_summary' => ['nullable', 'string'],
        ];
    }
}
