<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInventoryDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole('Teacher')
            && $user->can('create_inventory_demand');
    }

    public function rules(): array
    {
        return [
            'request_date' => ['required', 'date'],
            'session' => ['nullable', 'string', 'max:20'],
            'teacher_note' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')],
            'lines.*.requested_item_name' => ['nullable', 'string', 'max:255'],
            'lines.*.requested_quantity' => ['required', 'integer', 'min:1'],
            'lines.*.remarks' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $lines = collect((array) $this->input('lines', []))
            ->map(function (array $line): array {
                $itemId = $line['item_id'] ?? null;
                if ($itemId === '' || $itemId === 'other') {
                    $itemId = null;
                }

                $line['item_id'] = $itemId;
                $line['requested_item_name'] = trim((string) ($line['requested_item_name'] ?? ''));

                return $line;
            })
            ->values()
            ->all();

        $this->merge(['lines' => $lines]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('lines', []) as $index => $line) {
                $itemId = $line['item_id'] ?? null;
                $requestedItemName = trim((string) ($line['requested_item_name'] ?? ''));

                if ($itemId === null && $requestedItemName === '') {
                    $validator->errors()->add(
                        "lines.$index.requested_item_name",
                        'Select a stationery item or enter an item name for Other.'
                    );
                }
            }
        });
    }
}
