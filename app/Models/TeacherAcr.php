<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TeacherAcr extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'teacher_id',
        'session',
        'attendance_score',
        'academic_score',
        'improvement_score',
        'conduct_score',
        'pd_score',
        'principal_score',
        'total_score',
        'final_grade',
        'strengths',
        'areas_for_improvement',
        'recommendations',
        'confidential_remarks',
        'status',
        'prepared_by',
        'reviewed_by',
        'reviewed_at',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_score' => 'float',
            'academic_score' => 'float',
            'improvement_score' => 'float',
            'conduct_score' => 'float',
            'pd_score' => 'float',
            'principal_score' => 'float',
            'total_score' => 'float',
            'reviewed_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function metric(): HasOne
    {
        return $this->hasOne(TeacherAcrMetric::class, 'acr_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
