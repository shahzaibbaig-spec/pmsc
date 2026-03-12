<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableConstraint extends Model
{
    protected $fillable = [
        'session',
        'max_periods_per_day_teacher',
        'max_periods_per_week_teacher',
        'max_periods_per_day_class',
    ];

    protected function casts(): array
    {
        return [
            'max_periods_per_day_teacher' => 'integer',
            'max_periods_per_week_teacher' => 'integer',
            'max_periods_per_day_class' => 'integer',
        ];
    }
}

