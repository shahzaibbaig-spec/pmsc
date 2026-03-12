<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $fillable = [
        'day_of_week',
        'slot_index',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'slot_index' => 'integer',
        ];
    }
}

