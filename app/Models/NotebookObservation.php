<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotebookObservation extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_COMMENTED = 'commented';

    public const RESPONSE_YES = 'yes';
    public const RESPONSE_NO = 'no';
    public const RESPONSE_NA = 'na';

    /**
     * @var array<int, string>
     */
    public const RESPONSES = [
        self::RESPONSE_YES,
        self::RESPONSE_NO,
        self::RESPONSE_NA,
    ];

    protected $fillable = [
        'observed_teacher_id',
        'observer_id',
        'observer_role',
        'session',
        'observation_date',
        'class_id',
        'class_section',
        'subject_id',
        'total_students',
        'notebooks_provided',
        'covered_notebooks',
        'uncovered_notebooks',
        'well_maintained',
        'general_comments',
        'total_yes',
        'total_no',
        'performance_score',
        'status',
        'teacher_comments',
        'teacher_commented_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'observed_teacher_id' => 'integer',
            'observer_id' => 'integer',
            'class_id' => 'integer',
            'subject_id' => 'integer',
            'total_students' => 'integer',
            'notebooks_provided' => 'integer',
            'covered_notebooks' => 'integer',
            'uncovered_notebooks' => 'integer',
            'well_maintained' => 'integer',
            'total_yes' => 'integer',
            'total_no' => 'integer',
            'performance_score' => 'decimal:2',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(NotebookObservationItem::class);
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
