<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryDemandLine extends Model
{
    protected $fillable = [
        'demand_id',
        'item_id',
        'requested_item_name',
        'requested_quantity',
        'approved_quantity',
        'line_status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'integer',
            'approved_quantity' => 'integer',
        ];
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(InventoryDemand::class, 'demand_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function issueLines(): HasMany
    {
        return $this->hasMany(InventoryIssueLine::class, 'demand_line_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'demand_line_id');
    }
}
