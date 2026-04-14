<?php

namespace App\Http\Requests\Principal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignClassTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Principal', 'Admin'])
            && $user->can('assign_teachers');
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', Rule::exists('teachers', 'id')],
            'class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'session' => ['required', 'string', 'max:20'],
        ];
    }
}

