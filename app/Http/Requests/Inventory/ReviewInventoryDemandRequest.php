<?php

namespace App\Http\Requests\Inventory;

use App\Models\InventoryDemandLine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReviewInventoryDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasAnyRole(['Admin', 'Principal'])
            && $user->can('review_inventory_demands');
    }

    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer', Rule::exists('inventory_demand_lines', 'id')],
            'lines.*.line_status' => ['required', Rule::in(['approved', 'partially_approved', 'rejected'])],
            'lines.*.approved_quantity' => ['nullable', 'integer', 'min:0'],
            'lines.*.remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $lines = (array) $this->input('lines', []);
            $lineIds = collect($lines)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values();

            $lineMap = InventoryDemandLine::query()
                ->whereIn('id', $lineIds->all())
                ->get(['id', 'requested_quantity'])
                ->keyBy('id');

            foreach ($lines as $index => $line) {
                $lineId = (int) ($line['id'] ?? 0);
                $lineStatus = (string) ($line['line_status'] ?? '');
                $approvedQuantity = $line['approved_quantity'] ?? null;
                $requestedQuantity = (int) ($lineMap[$lineId]->requested_quantity ?? 0);

                if (in_array($lineStatus, ['approved', 'partially_approved'], true)) {
                    if ($approvedQuantity === null || (int) $approvedQuantity <= 0) {
                        $validator->errors()->add(
                            "lines.$index.approved_quantity",
                            'Approved quantity is required for approved or partially approved lines.'
                        );
                    }
                }

                if ($approvedQuantity !== null && $requestedQuantity > 0 && (int) $approvedQuantity > $requestedQuantity) {
                    $validator->errors()->add(
                        "lines.$index.approved_quantity",
                        'Approved quantity cannot be greater than requested quantity.'
                    );
                }
            }
        });
    }
}
