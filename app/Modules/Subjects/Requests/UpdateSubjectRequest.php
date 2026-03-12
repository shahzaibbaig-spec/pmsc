<?php

namespace App\Modules\Subjects\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        $subjectId = (int) $this->route('subject')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')->ignore($subjectId)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('subjects', 'code')->ignore($subjectId)],
        ];
    }
}

