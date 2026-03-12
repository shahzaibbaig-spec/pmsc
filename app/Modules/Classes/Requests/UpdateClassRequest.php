<?php

namespace App\Modules\Classes\Requests;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Principal') ?? false;
    }

    public function rules(): array
    {
        /** @var SchoolClass $classRoom */
        $classRoom = $this->route('schoolClass');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('school_classes', 'name')
                    ->where(fn ($query) => $query->where('section', $this->input('section')))
                    ->ignore($classRoom->id),
            ],
            'section' => ['nullable', 'string', 'max:20'],
        ];
    }
}

