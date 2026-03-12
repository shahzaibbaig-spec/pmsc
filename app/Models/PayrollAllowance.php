<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAllowance extends Model
{
    protected $fillable = [
        'payroll_profile_id',
        'title',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payrollProfile(): BelongsTo
    {
        return $this->belongsTo(PayrollProfile::class);
    }
}
