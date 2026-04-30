<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcatReportNote extends Model
{
    protected $fillable = [
        'kcat_attempt_id', 'counselor_id', 'strengths', 'development_areas',
        'counselor_recommendation', 'parent_summary', 'private_notes', 'visibility',
        'created_by', 'updated_by',
    ];

    public function attempt(): BelongsTo { return $this->belongsTo(KcatAttempt::class, 'kcat_attempt_id'); }
    public function counselor(): BelongsTo { return $this->belongsTo(User::class, 'counselor_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
