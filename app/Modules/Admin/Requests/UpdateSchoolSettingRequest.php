<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'block_results_for_defaulters' => ['nullable', 'boolean'],
            'block_admit_card_for_defaulters' => ['nullable', 'boolean'],
            'block_id_card_for_defaulters' => ['nullable', 'boolean'],
        ];
    }
}
