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
        'illness_type',
        'illness_other_text',
        'diagnosis',
        'prescription',
        'notes',
        'status',
        'referred_at',
        'consulted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
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
}

