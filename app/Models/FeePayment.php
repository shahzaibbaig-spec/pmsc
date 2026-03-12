<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = [
        'fee_challan_id',
        'amount_paid',
        'payment_date',
        'payment_method',
        'reference_no',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function challan(): BelongsTo
    {
        return $this->belongsTo(FeeChallan::class, 'fee_challan_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
