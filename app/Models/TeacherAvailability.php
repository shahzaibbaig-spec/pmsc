<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailability extends Model
{
    protected $table = 'teacher_availability';

    protected $fillable = [
        'teacher_id',
        'day_of_week',
        'slot_index',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'slot_index' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}

