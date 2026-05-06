<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KcatAttempt extends Model
{
    protected $fillable = [
        'kcat_assignment_id', 'kcat_test_id', 'student_id', 'counselor_id', 'session',
        'started_at', 'submitted_at', 'duration_seconds', 'status', 'total_score', 'percentage',
        'band', 'recommended_stream', 'recommendation_summary', 'is_adaptive', 'current_section_id',
        'current_difficulty', 'adaptive_state', 'counselor_override_stream',
        'counselor_override_reason', 'override_by', 'override_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'total_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'is_adaptive' => 'boolean',
            'adaptive_state' => 'array',
            'override_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo { return $this->belongsTo(KcatAssignment::class, 'kcat_assignment_id'); }
    public function test(): BelongsTo { return $this->belongsTo(KcatTest::class, 'kcat_test_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function counselor(): BelongsTo { return $this->belongsTo(User::class, 'counselor_id'); }
    public function currentSection(): BelongsTo { return $this->belongsTo(KcatSection::class, 'current_section_id'); }
    public function overrideBy(): BelongsTo { return $this->belongsTo(User::class, 'override_by'); }
    public function answers(): HasMany { return $this->hasMany(KcatAnswer::class); }
    public function scores(): HasMany { return $this->hasMany(KcatScore::class); }
    public function streamRecommendations(): HasMany { return $this->hasMany(KcatStreamRecommendation::class)->orderBy('rank')->orderByDesc('match_score'); }
    public function reportNotes(): HasMany { return $this->hasMany(KcatReportNote::class); }
    public function latestReportNote(): HasOne { return $this->hasOne(KcatReportNote::class)->latestOfMany(); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
