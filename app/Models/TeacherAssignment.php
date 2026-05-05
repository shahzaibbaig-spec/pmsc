<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
        'is_class_teacher',
        'session',
        'copied_from_assignment_id',
        'copied_by',
        'copied_at',
    ];

    protected function casts(): array
    {
        return [
            'is_class_teacher' => 'boolean',
            'copied_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->schoolClass();
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function copiedFromAssignment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copied_from_assignment_id');
    }

    public function copiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'copied_by');
    }
}
