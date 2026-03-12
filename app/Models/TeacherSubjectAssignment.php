<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSubjectAssignment extends Model
{
    protected $fillable = [
        'session',
        'class_id',
        'class_section_id',
        'subject_id',
        'teacher_id',
        'group_name',
        'lessons_per_week',
    ];

    protected function casts(): array
    {
        return [
            'class_id' => 'integer',
            'class_section_id' => 'integer',
            'subject_id' => 'integer',
            'teacher_id' => 'integer',
            'lessons_per_week' => 'integer',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}

