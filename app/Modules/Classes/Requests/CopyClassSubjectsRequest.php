<?php

namespace App\Modules\Classes\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyClassSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole('Principal')
            && $user->can('assign_subjects');
    }

    public function rules(): array
    {
        return [
            'source_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'target_class_id' => [
                'required',
                'integer',
                'different:source_class_id',
                Rule::exists('school_classes', 'id'),
            ],
            'copy_mode' => ['required', Rule::in(['copy_missing_only', 'replace_target_subjects'])],
        ];
    }
}
