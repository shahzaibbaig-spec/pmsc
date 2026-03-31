<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CognitiveAssessmentResponse extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'bank_question_id',
        'selected_answer',
        'is_correct',
        'awarded_marks',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'awarded_marks' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(CognitiveAssessmentAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CognitiveAssessmentQuestion::class, 'question_id');
    }

    public function bankQuestion(): BelongsTo
    {
        return $this->belongsTo(CognitiveBankQuestion::class, 'bank_question_id');
    }

    public function responseKey(): ?string
    {
        if ($this->bank_question_id !== null) {
            return 'bank:'.$this->bank_question_id;
        }

        if ($this->question_id !== null) {
            return 'legacy:'.$this->question_id;
        }

        return null;
    }
}
