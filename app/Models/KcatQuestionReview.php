<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcatQuestionReview extends Model
{
    protected $fillable = [
        'kcat_question_id',
        'reviewed_by',
        'status',
        'difficulty_review',
        'clarity_score',
        'quality_score',
        'issue_notes',
        'action_taken',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'clarity_score' => 'integer',
            'quality_score' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(KcatQuestion::class, 'kcat_question_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

