<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReviewTeacherDeviceDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Admin', 'Principal'])
            && $user->can('review_device_declarations');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['verify', 'reject', 'link'])],
            'asset_unit_id' => ['nullable', 'integer', Rule::exists('inventory_asset_units', 'id')],
            'admin_note' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $action = (string) $this->input('action');
            $adminNote = trim((string) $this->input('admin_note', ''));
            $assetUnitId = $this->input('asset_unit_id');

            if ($action === 'reject' && $adminNote === '') {
                $validator->errors()->add('admin_note', 'Admin note is required when rejecting a declaration.');
            }

            if ($action === 'link' && ($assetUnitId === null || $assetUnitId === '')) {
                $validator->errors()->add('asset_unit_id', 'Select an asset unit to link this declaration.');
            }
        });
    }
}
