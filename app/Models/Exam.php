<?php

namespace App\Models;

use App\Modules\Exams\Enums\ExamType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'class_id',
        'subject_id',
        'exam_type',
        'session',
        'total_marks',
        'teacher_id',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_type' => ExamType::class,
            'locked_at' => 'datetime',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}

