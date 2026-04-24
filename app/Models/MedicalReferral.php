<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalReferral extends Model
{
    protected $fillable = [
        'student_id',
        'principal_id',
        'doctor_id',
        'source_type',
        'referred_by',
        'added_by',
        'illness_type',
        'illness_other_text',
        'problem',
        'diagnosis',
        'prescription',
        'notes',
        'status',
        'visit_date',
        'session',
        'referred_at',
        'consulted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'referred_at' => 'datetime',
            'consulted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'principal_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function getIllnessLabelAttribute(): string
    {
        return match ($this->illness_type) {
            'fever' => 'Fever',
            'headache' => 'Headache',
            'stomach_ache' => 'Stomach Ache',
            'other' => 'Other',
            default => ucfirst((string) $this->illness_type),
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return match ((string) $this->source_type) {
            'doctor_direct' => 'Doctor Direct Visit',
            'principal_referral' => 'Principal Referral',
            default => 'Unknown',
        };
    }
}
