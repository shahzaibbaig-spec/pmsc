<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CognitiveAssessmentAttemptReset extends Model
{
    protected $fillable = [
        'attempt_id',
        'student_id',
        'reset_by',
        'reason',
        'reset_at',
    ];

    protected function casts(): array
    {
        return [
            'reset_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(CognitiveAssessmentAttempt::class, 'attempt_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function resetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reset_by');
    }
}
