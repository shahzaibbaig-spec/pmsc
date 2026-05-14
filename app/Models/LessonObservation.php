<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonObservation extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_COMMENTED = 'commented';

    public const JUDGMENT_OUTSTANDING = 'Outstanding';
    public const JUDGMENT_GOOD = 'Good';
    public const JUDGMENT_ACCEPTABLE = 'Acceptable';
    public const JUDGMENT_UNACCEPTABLE = 'Unacceptable';

    protected $fillable = [
        'observed_teacher_id',
        'observer_id',
        'observer_role',
        'session',
        'observation_date',
        'school',
        'subject_topic',
        'class_id',
        'class_section',
        'no_of_students',
        'learning_objectives',
        'previous_targets',
        'what_went_well',
        'even_better_if',
        'progress_percentage',
        'overall_judgment',
        'total_marks',
        'max_marks',
        'performance_score',
        'status',
        'teacher_comments',
        'teacher_commented_at',
        'teacher_signature_acknowledged',
        'observer_signature_acknowledged',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'observed_teacher_id' => 'integer',
            'observer_id' => 'integer',
            'class_id' => 'integer',
            'no_of_students' => 'integer',
            'total_marks' => 'decimal:2',
            'max_marks' => 'decimal:2',
            'performance_score' => 'decimal:2',
            'progress_percentage' => 'decimal:2',
            'teacher_signature_acknowledged' => 'boolean',
            'observer_signature_acknowledged' => 'boolean',
            'teacher_commented_at' => 'datetime',
            'observation_date' => 'date',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function observedTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observed_teacher_id');
    }

    public function observer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observer_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LessonObservationItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
