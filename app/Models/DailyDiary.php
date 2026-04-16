<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyDiary extends Model
{
    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
        'session',
        'diary_date',
        'title',
        'homework_text',
        'instructions',
        'is_published',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'teacher_id' => 'integer',
            'class_id' => 'integer',
            'subject_id' => 'integer',
            'diary_date' => 'date',
            'is_published' => 'boolean',
            'created_by' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DailyDiaryAttachment::class);
    }
}

