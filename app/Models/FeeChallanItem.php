<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeChallanItem extends Model
{
    protected $fillable = [
        'fee_challan_id',
        'fee_structure_id',
        'title',
        'fee_type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function challan(): BelongsTo
    {
        return $this->belongsTo(FeeChallan::class, 'fee_challan_id');
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }
}
