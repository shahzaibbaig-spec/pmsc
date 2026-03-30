<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryIssueLine extends Model
{
    protected $fillable = [
        'issue_id',
        'demand_line_id',
        'item_id',
        'quantity',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(InventoryIssue::class, 'issue_id');
    }

    public function demandLine(): BelongsTo
    {
        return $this->belongsTo(InventoryDemandLine::class, 'demand_line_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'issue_line_id');
    }
}
