<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeAssignment extends Model
{
    protected $fillable = [
        'student_id',
        'fee_structure_id',
        'session',
        'custom_amount',
        'is_active',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'custom_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
