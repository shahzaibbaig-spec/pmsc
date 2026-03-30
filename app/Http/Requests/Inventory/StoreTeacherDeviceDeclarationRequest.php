<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherDeviceDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole('Teacher')
            && $user->can('submit_device_declaration');
    }

    public function rules(): array
    {
        return [
            'device_type' => ['nullable', 'string', Rule::in(['chromebook', 'laptop', 'tablet', 'device'])],
            'serial_number' => ['required', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'teacher_note' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $serial = strtoupper(trim((string) $this->input('serial_number', '')));

        $this->merge([
            'serial_number' => $serial,
            'device_type' => $this->input('device_type', 'chromebook'),
        ]);
    }
}
