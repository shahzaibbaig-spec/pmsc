<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'unit',
        'current_stock',
        'minimum_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'integer',
            'minimum_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function demandLines(): HasMany
    {
        return $this->hasMany(InventoryDemandLine::class, 'item_id');
    }

    public function issueLines(): HasMany
    {
        return $this->hasMany(InventoryIssueLine::class, 'item_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'item_id');
    }

    public function assetUnits(): HasMany
    {
        return $this->hasMany(InventoryAssetUnit::class, 'item_id');
    }
}
