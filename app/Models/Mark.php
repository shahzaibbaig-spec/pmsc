<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mark extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'obtained_marks',
        'total_marks',
        'teacher_id',
        'session',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function editLogs(): HasMany
    {
        return $this->hasMany(MarkEditLog::class);
    }
}
