<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInstallmentPlan extends Model
{
    public const TYPE_MONTHLY = 'monthly';

    public const TYPE_QUARTERLY = 'quarterly';

    public const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'student_id',
        'session',
        'plan_name',
        'plan_type',
        'total_amount',
        'number_of_installments',
        'first_due_date',
        'custom_interval_days',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'first_due_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(FeeInstallment::class, 'fee_installment_plan_id')
            ->orderBy('installment_no');
    }
}

