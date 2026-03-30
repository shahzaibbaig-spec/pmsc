<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockMovement extends Model
{
    protected $fillable = [
        'item_id',
        'issue_line_id',
        'demand_line_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'note',
        'moved_by',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'moved_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function issueLine(): BelongsTo
    {
        return $this->belongsTo(InventoryIssueLine::class, 'issue_line_id');
    }

    public function demandLine(): BelongsTo
    {
        return $this->belongsTo(InventoryDemandLine::class, 'demand_line_id');
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
