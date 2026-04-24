<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WardenHealthLog extends Model
{
    protected $fillable = [
        'report_id',
        'student_id',
        'condition',
        'temperature',
        'medication',
        'doctor_visit',
    ];

    protected function casts(): array
    {
        return [
            'report_id' => 'integer',
            'student_id' => 'integer',
            'temperature' => 'decimal:1',
            'doctor_visit' => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(WardenDailyReport::class, 'report_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

