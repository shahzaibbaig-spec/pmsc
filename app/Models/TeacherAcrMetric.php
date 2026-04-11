<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAcrMetric extends Model
{
    protected $fillable = [
        'acr_id',
        'attendance_percentage',
        'teacher_cgpa',
        'pass_percentage',
        'student_improvement_percentage',
        'trainings_attended',
        'late_count',
        'discipline_flags',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'attendance_percentage' => 'float',
            'teacher_cgpa' => 'float',
            'pass_percentage' => 'float',
            'student_improvement_percentage' => 'float',
            'trainings_attended' => 'integer',
            'late_count' => 'integer',
            'discipline_flags' => 'integer',
            'meta' => 'array',
        ];
    }

    public function acr(): BelongsTo
    {
        return $this->belongsTo(TeacherAcr::class, 'acr_id');
    }
}
