<?php

namespace App\Services;

use App\Models\InventoryDemand;
use App\Models\InventoryDemandLine;
use App\Models\InventoryIssue;
use App\Models\InventoryIssueLine;
use App\Models\InventoryItem;
use App\Models\InventoryStockMovement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryDemandService
{
    public function createDemand(int $teacherId, array $data): InventoryDemand
    {
        return DB::transaction(function () use ($teacherId, $data): InventoryDemand {
            $demand = InventoryDemand::query()->create([
                'teacher_id' => $teacherId,
                'request_date' => (string) $data['request_date'],
                'session' => Arr::get($data, 'session'),
                'status' => 'pending',
                'teacher_note' => Arr::get($data, 'teacher_note'),
            ]);

            $this->syncDemandLines($demand, (array) Arr::get($data, 'lines', []));

            return $demand->load(['teacher.user:id,name,email', 'lines.item:id,name']);
        });
    }

    public function updateDemand(InventoryDemand $demand, int $teacherId, array $data): InventoryDemand
    {
        if ((int) $demand->teacher_id !== $teacherId) {
            throw ValidationException::withMessages([
                'demand' => 'You can only update your own demand.',
            ]);
        }

        if ($demand->status !== 'pending') {
            throw ValidationException::withMessages([
                'demand' => 'Only pending demands can be updated.',
            ]);
        }

        return DB::transaction(function () use ($demand, $data): InventoryDemand {
            $demand->update([
                'request_date' => (string) $data['request_date'],
                'session' => Arr::get($data, 'session'),
                'teacher_note' => Arr::get($data, 'teacher_note'),
            ]);

            $demand->lines()->delete();
            $this->syncDemandLines($demand, (array) Arr::get($data, 'lines', []));

            return $demand->load(['teacher.user:id,name,email', 'lines.item:id,name']);
        });
    }

    public function reviewDemand(InventoryDemand $demand, array $data, int $reviewedBy): InventoryDemand
    {
        if ($demand->status === 'fulfilled') {
            throw ValidationException::withMessages([
                'demand' => 'This demand has already been fulfilled.',
            ]);
        }

        return DB::transaction(function () use ($demand, $data, $reviewedBy): InventoryDemand {
            $linePayload = collect((array) Arr::get($data, 'lines', []))
                ->map(function (array $line): array {
                    return [
                        'id' => (int) Arr::get($line, 'id'),
                        'line_status' => (string) Arr::get($line, 'line_status'),
                        'approved_quantity' => Arr::get($line, 'approved_quantity') !== null
                            ? (int) Arr::get($line, 'approved_quantity')
                            : null,
                        'remarks' => Arr::get($line, 'remarks'),
                    ];
                })
                ->keyBy('id');

            $demandLines = InventoryDemandLine::query()
                ->where('demand_id', $demand->id)
                ->lockForUpdate()
                ->get();

            foreach ($demandLines as $line) {
                if ($line->line_status === 'fulfilled') {
                    continue;
                }

                if (! $linePayload->has($line->id)) {
                    continue;
                }

                $payload = $linePayload->get($line->id);
                $approvedQuantity = $payload['approved_quantity'];

                if ($approvedQuantity !== null && $approvedQuantity > (int) $line->requested_quantity) {
                    throw ValidationException::withMessages([
                        'lines' => 'Approved quantity cannot be greater than requested quantity.',
                    ]);
                }

                $line->update([
                    'line_status' => $payload['line_status'],
                    'approved_quantity' => $payload['line_status'] === 'rejected'
                        ? 0
                        : $approvedQuantity,
                    'remarks' => $payload['remarks'],
                ]);
            }

            $freshLines = $demand->lines()->get();
            $statuses = $freshLines->pluck('line_status')->all();

            $demandStatus = 'partially_approved';
            if ($freshLines->isNotEmpty() && collect($statuses)->every(fn (string $status): bool => $status === 'rejected')) {
                $demandStatus = 'rejected';
            } elseif ($freshLines->isNotEmpty() && collect($statuses)->every(fn (string $status): bool => $status === 'approved')) {
                $demandStatus = 'approved';
            }

            $demand->update([
                'status' => $demandStatus,
                'review_note' => Arr::get($data, 'review_note'),
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ]);

            return $demand->load(['teacher.user:id,name,email', 'lines.item:id,name', 'reviewer:id,name']);
        });
    }

    public function fulfillDemand(InventoryDemand $demand, int $issuedBy, ?string $note = null): InventoryDemand
    {
        if (! in_array($demand->status, ['approved', 'partially_approved'], true)) {
            throw ValidationException::withMessages([
                'demand' => 'Only approved or partially approved demands can be fulfilled.',
            ]);
        }

        return DB::transaction(function () use ($demand, $issuedBy, $note): InventoryDemand {
            $lines = InventoryDemandLine::query()
                ->where('demand_id', $demand->id)
                ->lockForUpdate()
                ->get();

            $issueableLines = $lines
                ->filter(function (InventoryDemandLine $line): bool {
                    return in_array($line->line_status, ['approved', 'partially_approved'], true)
                        && (int) ($line->approved_quantity ?? 0) > 0
                        && $line->item_id !== null;
                })
                ->values();

            if ($issueableLines->isEmpty()) {
                throw ValidationException::withMessages([
                    'demand' => 'No approved inventory lines are available to fulfill.',
                ]);
            }

            $issue = InventoryIssue::query()->create([
                'teacher_id' => $demand->teacher_id,
                'demand_id' => $demand->id,
                'issue_date' => now()->toDateString(),
                'session' => $demand->session,
                'issued_by' => $issuedBy,
                'note' => $note,
            ]);

            foreach ($issueableLines as $line) {
                $quantity = (int) $line->approved_quantity;
                $item = InventoryItem::query()->lockForUpdate()->findOrFail($line->item_id);

                if ((int) $item->current_stock < $quantity) {
                    throw ValidationException::withMessages([
                        'demand' => 'Insufficient stock for item '.$item->name.'.',
                    ]);
                }

                $issueLine = InventoryIssueLine::query()->create([
                    'issue_id' => $issue->id,
                    'demand_line_id' => $line->id,
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'remarks' => $line->remarks,
                ]);

                InventoryStockMovement::query()->create([
                    'item_id' => $item->id,
                    'issue_line_id' => $issueLine->id,
                    'demand_line_id' => $line->id,
                    'movement_type' => 'out',
                    'quantity' => $quantity,
                    'reference_type' => 'inventory_issue',
                    'reference_id' => $issue->id,
                    'note' => 'Fulfillment for demand #'.$demand->id,
                    'moved_by' => $issuedBy,
                    'moved_at' => now(),
                ]);

                $item->decrement('current_stock', $quantity);
                $line->update(['line_status' => 'fulfilled']);
            }

            $hasUnfulfilledApproved = $lines
                ->fresh()
                ->contains(function (InventoryDemandLine $line): bool {
                    return in_array($line->line_status, ['approved', 'partially_approved', 'pending'], true);
                });

            $demand->update([
                'status' => $hasUnfulfilledApproved ? 'partially_approved' : 'fulfilled',
                'reviewed_by' => $demand->reviewed_by ?: $issuedBy,
                'reviewed_at' => $demand->reviewed_at ?: now(),
            ]);

            return $demand->load([
                'teacher.user:id,name,email',
                'lines.item:id,name,current_stock',
                'issues.lines.item:id,name',
            ]);
        });
    }

    private function syncDemandLines(InventoryDemand $demand, array $lines): void
    {
        foreach ($lines as $line) {
            $itemId = Arr::get($line, 'item_id');
            $requestedName = trim((string) Arr::get($line, 'requested_item_name', ''));

            if ($itemId === null && $requestedName === '') {
                throw ValidationException::withMessages([
                    'lines' => 'Each line must select an item or provide an Other item name.',
                ]);
            }

            InventoryDemandLine::query()->create([
                'demand_id' => $demand->id,
                'item_id' => $itemId !== null ? (int) $itemId : null,
                'requested_item_name' => $requestedName !== '' ? $requestedName : null,
                'requested_quantity' => max(1, (int) Arr::get($line, 'requested_quantity', 1)),
                'approved_quantity' => null,
                'line_status' => 'pending',
                'remarks' => Arr::get($line, 'remarks'),
            ]);
        }
    }
}
