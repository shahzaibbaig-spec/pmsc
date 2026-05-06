<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcatAnswer extends Model
{
    protected $fillable = [
        'kcat_attempt_id',
        'kcat_question_id',
        'selected_option_id',
        'answer_text',
        'is_correct',
        'marks_awarded',
        'difficulty_at_time',
        'answered_at',
        'response_time_seconds',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'marks_awarded' => 'decimal:2',
            'answered_at' => 'datetime',
            'response_time_seconds' => 'integer',
        ];
    }

    public function attempt(): BelongsTo { return $this->belongsTo(KcatAttempt::class, 'kcat_attempt_id'); }
    public function question(): BelongsTo { return $this->belongsTo(KcatQuestion::class, 'kcat_question_id'); }
    public function selectedOption(): BelongsTo { return $this->belongsTo(KcatQuestionOption::class, 'selected_option_id'); }
}
