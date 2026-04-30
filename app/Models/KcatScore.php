<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcatScore extends Model
{
    protected $fillable = ['kcat_attempt_id', 'kcat_section_id', 'section_code', 'raw_score', 'total_marks', 'percentage', 'band', 'remarks'];

    protected function casts(): array
    {
        return ['raw_score' => 'decimal:2', 'total_marks' => 'decimal:2', 'percentage' => 'decimal:2'];
    }

    public function attempt(): BelongsTo { return $this->belongsTo(KcatAttempt::class, 'kcat_attempt_id'); }
    public function section(): BelongsTo { return $this->belongsTo(KcatSection::class, 'kcat_section_id'); }
}
