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
        'exam_group',
        'exam_label',
        'topic',
        'sequence_number',
        'session',
        'marking_mode',
        'total_marks',
        'teacher_id',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_type' => ExamType::class,
            'sequence_number' => 'integer',
            'total_marks' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    protected $appends = [
        'display_name',
    ];

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

    public function resultLocks(): HasMany
    {
        return $this->hasMany(ResultLock::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $label = trim((string) $this->exam_label);
        if ($label !== '') {
            return $label;
        }

        $type = $this->exam_type instanceof ExamType
            ? $this->exam_type
            : ExamType::tryFrom((string) $this->exam_type);

        if ($type === null) {
            return str_replace('_', ' ', ucfirst((string) $this->exam_type));
        }

        if ($type === ExamType::ClassTest) {
            $topic = trim((string) $this->topic);
            return $topic !== '' ? 'Class Test - '.$topic : 'Class Test';
        }

        if ($type === ExamType::BimonthlyTest) {
            return match ((int) ($this->sequence_number ?? 0)) {
                1 => '1st Bimonthly',
                2 => '2nd Bimonthly',
                3 => '3rd Bimonthly',
                4 => '4th Bimonthly',
                default => 'Bimonthly',
            };
        }

        if ($type === ExamType::FirstTerm) {
            return 'Midterm';
        }

        if ($type === ExamType::FinalTerm) {
            return 'Final Term';
        }

        return $type->label();
    }
}
