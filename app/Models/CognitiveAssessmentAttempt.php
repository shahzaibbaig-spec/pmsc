<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CognitiveAssessmentAttempt extends Model
{
    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_AUTO_SUBMITTED = 'auto_submitted';

    public const STATUS_GRADED = 'graded';

    public const STATUS_RESET = 'reset';

    protected $fillable = [
        'assessment_id',
        'student_id',
        'status',
        'verbal_score',
        'non_verbal_score',
        'quantitative_score',
        'spatial_score',
        'overall_score',
        'overall_percentage',
        'performance_band',
        'started_at',
        'expires_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'verbal_score' => 'integer',
            'non_verbal_score' => 'integer',
            'quantitative_score' => 'integer',
            'spatial_score' => 'integer',
            'overall_score' => 'integer',
            'overall_percentage' => 'decimal:2',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CognitiveAssessment::class, 'assessment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentResponse::class, 'attempt_id');
    }

    public function resets(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentAttemptReset::class, 'attempt_id')
            ->latest('reset_at')
            ->latest('id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lessThanOrEqualTo(now());
    }
}
