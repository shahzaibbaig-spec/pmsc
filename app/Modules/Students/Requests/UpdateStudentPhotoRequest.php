<?php

namespace App\Modules\Students\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->hasAnyRole(['Admin', 'Principal']) ?? false);
    }

    public function rules(): array
    {
        return [
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'photo_capture' => ['nullable', 'string', 'max:8000000'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}

