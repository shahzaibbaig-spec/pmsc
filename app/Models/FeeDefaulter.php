<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeDefaulter extends Model
{
    protected $fillable = [
        'student_id',
        'session',
        'total_due',
        'oldest_due_date',
        'is_active',
        'marked_at',
        'cleared_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'total_due' => 'decimal:2',
            'oldest_due_date' => 'date',
            'is_active' => 'boolean',
            'marked_at' => 'datetime',
            'cleared_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
