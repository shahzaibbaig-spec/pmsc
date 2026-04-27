<?php

namespace App\Http\Requests\CareerCounselor;

use Illuminate\Foundation\Http\FormRequest;

class StoreCareerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create_career_profile') || $this->user()?->can('update_career_profile');
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'strengths' => ['nullable', 'string'],
            'weaknesses' => ['nullable', 'string'],
            'interests' => ['nullable', 'string'],
            'preferred_subjects' => ['nullable', 'string'],
            'career_goals' => ['nullable', 'string'],
            'parent_expectations' => ['nullable', 'string'],
            'recommended_career_paths' => ['nullable', 'string'],
            'counselor_notes' => ['nullable', 'string'],
        ];
    }
}
