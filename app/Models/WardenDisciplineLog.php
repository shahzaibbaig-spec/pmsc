<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WardenDisciplineLog extends Model
{
    protected $fillable = [
        'report_id',
        'student_id',
        'issue_type',
        'severity',
        'description',
        'action_taken',
    ];

    protected function casts(): array
    {
        return [
            'report_id' => 'integer',
            'student_id' => 'integer',
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

