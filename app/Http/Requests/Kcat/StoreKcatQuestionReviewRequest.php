<?php

namespace App\Http\Requests\Kcat;

use Illuminate\Foundation\Http\FormRequest;

class StoreKcatQuestionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('review_kcat_questions');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,approved,needs_revision,retired'],
            'difficulty_review' => ['nullable', 'in:easy,medium,hard'],
            'clarity_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'quality_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'issue_notes' => ['nullable', 'string'],
            'action_taken' => ['nullable', 'string', 'max:255'],
        ];
    }
}

