<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplineComplaint extends Model
{
    protected $fillable = [
        'student_id',
        'complaint_date',
        'description',
        'action_taken',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'complaint_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

