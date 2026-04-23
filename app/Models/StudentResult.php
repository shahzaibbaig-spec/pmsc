<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentResult extends Model
{
    protected $table = 'student_results';

    protected $fillable = [
        'student_id',
        'session',
        'class_id',
        'exam_id',
        'is_locked',
        'subject_id',
        'exam_name',
        'total_marks',
        'obtained_marks',
        'grade',
        'result_date',
    ];

    protected function casts(): array
    {
        return [
            'class_id' => 'integer',
            'exam_id' => 'integer',
            'is_locked' => 'boolean',
            'result_date' => 'date',
        ];
    }

    public function scopeForSession(Builder $query, string $session): Builder
    {
        return $query->where('session', $session);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function getPercentageAttribute(): float
    {
        if ((int) $this->total_marks === 0) {
            return 0;
        }

        return round(((int) $this->obtained_marks / (int) $this->total_marks) * 100, 2);
    }
}
