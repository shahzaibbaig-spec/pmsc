<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollProfile extends Model
{
    protected $fillable = [
        'user_id',
        'basic_salary',
        'allowances',
        'deductions',
        'bank_name',
        'account_no',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'deductions' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allowancesRows(): HasMany
    {
        return $this->hasMany(PayrollAllowance::class);
    }

    public function deductionsRows(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
