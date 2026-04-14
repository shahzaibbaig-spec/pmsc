<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherResultEntryLog extends Model
{
    protected $fillable = [
        'teacher_id',
        'student_id',
        'class_id',
        'subject_id',
        'session',
        'exam_type',
        'old_marks',
        'new_marks',
        'old_grade',
        'new_grade',
        'action_type',
        'action_at',
        'acted_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'old_marks' => 'float',
            'new_marks' => 'float',
            'action_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}

