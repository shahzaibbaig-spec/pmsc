<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLearningProfile extends Model
{
    protected $fillable = [
        'student_id',
        'session',
        'strengths',
        'support_areas',
        'best_aptitude',
        'learning_pattern',
        'attendance_percentage',
        'overall_average',
        'subject_scores',
    ];

    protected function casts(): array
    {
        return [
            'attendance_percentage' => 'float',
            'overall_average' => 'float',
            'subject_scores' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

