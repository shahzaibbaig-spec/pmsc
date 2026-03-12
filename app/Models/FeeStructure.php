<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    protected $fillable = [
        'session',
        'class_id',
        'title',
        'amount',
        'fee_type',
        'is_monthly',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_monthly' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StudentFeeAssignment::class);
    }

    public function challanItems(): HasMany
    {
        return $this->hasMany(FeeChallanItem::class);
    }
}
