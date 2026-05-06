<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcatStreamRecommendation extends Model
{
    protected $fillable = [
        'kcat_attempt_id',
        'stream_name',
        'match_score',
        'confidence_band',
        'reasoning_summary',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'decimal:2',
            'rank' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(KcatAttempt::class, 'kcat_attempt_id');
    }
}

