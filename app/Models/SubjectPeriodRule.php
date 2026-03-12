<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectPeriodRule extends Model
{
    protected $fillable = [
        'session',
        'class_section_id',
        'subject_id',
        'periods_per_week',
    ];

    protected function casts(): array
    {
        return [
            'periods_per_week' => 'integer',
        ];
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}

