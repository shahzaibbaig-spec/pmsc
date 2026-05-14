<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherPerformanceEvent extends Model
{
    public const SOURCE_LESSON_OBSERVATION = 'lesson_observation';
    public const SOURCE_NOTEBOOK_OBSERVATION = 'notebook_observation';

    protected $fillable = [
        'teacher_id',
        'source_type',
        'source_id',
        'session',
        'score',
        'max_score',
        'percentage',
        'judgment',
        'remarks',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'teacher_id' => 'integer',
            'source_id' => 'integer',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'recorded_by' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
