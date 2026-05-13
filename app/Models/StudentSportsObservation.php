<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSportsObservation extends Model
{
    public const ISSUE_NAILS_NOT_CUT = 'nails_not_cut';
    public const ISSUE_HAIR_NOT_CUT = 'hair_not_cut';
    public const ISSUE_UNIFORM_NOT_NEAT = 'uniform_not_neat';
    public const ISSUE_SHOES_NOT_POLISHED = 'shoes_not_polished';
    public const ISSUE_NOT_CLEAN = 'not_clean';
    public const ISSUE_POOR_SPORTS_DISCIPLINE = 'poor_sports_discipline';

    public const SEVERITY_NORMAL = 'normal';
    public const SEVERITY_REPEATED = 'repeated';
    public const SEVERITY_SERIOUS = 'serious';

    public const STATUS_OPEN = 'open';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';

    /**
     * @var array<int, string>
     */
    public const ISSUE_LABELS = [
        self::ISSUE_NAILS_NOT_CUT => 'Nails Not Cut',
        self::ISSUE_HAIR_NOT_CUT => 'Hair Not Cut',
        self::ISSUE_UNIFORM_NOT_NEAT => 'Uniform Not Neat',
        self::ISSUE_SHOES_NOT_POLISHED => 'Shoes Not Polished',
        self::ISSUE_NOT_CLEAN => 'Not Clean',
        self::ISSUE_POOR_SPORTS_DISCIPLINE => 'Poor Sports Discipline',
    ];

    /**
     * @var array<int, string>
     */
    public const SEVERITIES = [
        self::SEVERITY_NORMAL,
        self::SEVERITY_REPEATED,
        self::SEVERITY_SERIOUS,
    ];

    /**
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_ACKNOWLEDGED,
        self::STATUS_RESOLVED,
    ];

    protected $fillable = [
        'student_id',
        'class_id',
        'sports_teacher_id',
        'session',
        'observation_date',
        'issue_type',
        'issue_label',
        'auto_message',
        'severity',
        'status',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'notified_principal_at',
        'notified_wardens_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'class_id' => 'integer',
            'sports_teacher_id' => 'integer',
            'resolved_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'observation_date' => 'date',
            'resolved_at' => 'datetime',
            'notified_principal_at' => 'datetime',
            'notified_wardens_at' => 'datetime',
        ];
    }

    public static function issueLabelFor(string $issueType): string
    {
        return self::ISSUE_LABELS[$issueType] ?? str_replace('_', ' ', ucfirst($issueType));
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function sportsTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sports_teacher_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
