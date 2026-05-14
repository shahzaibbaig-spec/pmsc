<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionHeadAssignment extends Model
{
    public const SCOPE_EARLY_YEARS = 'early_years';
    public const SCOPE_MIDDLE_SCHOOL = 'middle_school';
    public const SCOPE_SENIOR_SCHOOL = 'senior_school';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const TYPE_EARLY_YEARS_SECTION_HEAD = 'Early Years Section Head';
    public const TYPE_MIDDLE_SCHOOL_SECTION_HEAD = 'Middle School Section Head';
    public const TYPE_SENIOR_SCHOOL_SECTION_HEAD = 'Senior School Section Head';

    /**
     * @var array<string, string>
     */
    public const TYPE_SCOPE_MAP = [
        self::TYPE_EARLY_YEARS_SECTION_HEAD => self::SCOPE_EARLY_YEARS,
        self::TYPE_MIDDLE_SCHOOL_SECTION_HEAD => self::SCOPE_MIDDLE_SCHOOL,
        self::TYPE_SENIOR_SCHOOL_SECTION_HEAD => self::SCOPE_SENIOR_SCHOOL,
    ];

    /**
     * @var array<string, string>
     */
    public const SCOPE_LABELS = [
        self::SCOPE_EARLY_YEARS => 'Early Years',
        self::SCOPE_MIDDLE_SCHOOL => 'Middle School',
        self::SCOPE_SENIOR_SCHOOL => 'Senior School',
    ];

    protected $fillable = [
        'teacher_id',
        'user_id',
        'section_head_type',
        'scope',
        'session',
        'status',
        'assigned_by',
        'assigned_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'teacher_id' => 'integer',
            'user_id' => 'integer',
            'assigned_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'assigned_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
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
