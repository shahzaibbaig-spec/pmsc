<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonObservationItem extends Model
{
    protected $fillable = [
        'lesson_observation_id',
        'area',
        'standard_text',
        'mark',
        'max_mark',
        'comments',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'lesson_observation_id' => 'integer',
            'mark' => 'integer',
            'max_mark' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function lessonObservation(): BelongsTo
    {
        return $this->belongsTo(LessonObservation::class);
    }
}
