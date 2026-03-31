<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentResult extends Model
{
    protected $table = 'student_results';

    protected $fillable = [
        'student_id',
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
            'result_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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
