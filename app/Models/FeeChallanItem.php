<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeChallanItem extends Model
{
    protected $fillable = [
        'fee_challan_id',
        'fee_structure_id',
        'fee_installment_id',
        'student_arrear_id',
        'title',
        'fee_type',
        'amount',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
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

    public function installment(): BelongsTo
    {
        return $this->belongsTo(FeeInstallment::class, 'fee_installment_id');
    }

    public function arrear(): BelongsTo
    {
        return $this->belongsTo(StudentArrear::class, 'student_arrear_id');
    }
}
