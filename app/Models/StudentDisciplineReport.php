<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDisciplineReport extends Model
{
    public const ISSUE_LATE_TO_CLASS = 'late_to_class';
    public const ISSUE_HOMEWORK_NOT_COMPLETED = 'homework_not_completed';
    public const ISSUE_CLASS_DISTURBANCE = 'class_disturbance';
    public const ISSUE_DISRESPECTFUL_BEHAVIOR = 'disrespectful_behavior';
    public const ISSUE_FIGHTING_AGGRESSION = 'fighting_aggression';
    public const ISSUE_BULLYING = 'bullying';
    public const ISSUE_ABUSIVE_LANGUAGE = 'abusive_language';
    public const ISSUE_UNIFORM_ISSUE = 'uniform_issue';
    public const ISSUE_MOBILE_PHONE_MISUSE = 'mobile_phone_misuse';
    public const ISSUE_CHEATING_DISHONESTY = 'cheating_dishonesty';
    public const ISSUE_LEAVING_CLASS_WITHOUT_PERMISSION = 'leaving_class_without_permission';
    public const ISSUE_REPEATED_NEGLIGENCE = 'repeated_negligence';
    public const ISSUE_OTHER = 'other';

    public const SEVERITY_NORMAL = 'normal';
    public const SEVERITY_REPEATED = 'repeated';
    public const SEVERITY_SERIOUS = 'serious';
    public const SEVERITY_URGENT = 'urgent';

    public const STATUS_OPEN = 'open';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';

    /**
     * @var array<string, string>
     */
    public const ISSUE_LABELS = [
        self::ISSUE_LATE_TO_CLASS => 'Late to Class',
        self::ISSUE_HOMEWORK_NOT_COMPLETED => 'Homework Not Completed',
        self::ISSUE_CLASS_DISTURBANCE => 'Class Disturbance',
        self::ISSUE_DISRESPECTFUL_BEHAVIOR => 'Disrespectful Behavior',
        self::ISSUE_FIGHTING_AGGRESSION => 'Fighting / Aggression',
        self::ISSUE_BULLYING => 'Bullying',
        self::ISSUE_ABUSIVE_LANGUAGE => 'Abusive Language',
        self::ISSUE_UNIFORM_ISSUE => 'Uniform Issue',
        self::ISSUE_MOBILE_PHONE_MISUSE => 'Mobile Phone Misuse',
        self::ISSUE_CHEATING_DISHONESTY => 'Cheating / Dishonesty',
        self::ISSUE_LEAVING_CLASS_WITHOUT_PERMISSION => 'Leaving Class Without Permission',
        self::ISSUE_REPEATED_NEGLIGENCE => 'Repeated Negligence',
        self::ISSUE_OTHER => 'Other',
    ];

    /**
     * @var array<int, string>
     */
    public const SEVERITIES = [
        self::SEVERITY_NORMAL,
        self::SEVERITY_REPEATED,
        self::SEVERITY_SERIOUS,
        self::SEVERITY_URGENT,
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
        'subject_id',
        'teacher_id',
        'session',
        'report_date',
        'issue_type',
        'issue_label',
        'severity',
        'description',
        'auto_message',
        'status',
        'principal_remarks',
        'warden_remarks',
        'psychiatrist_feedback',
        'psychiatrist_reviewed_by',
        'psychiatrist_reviewed_at',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'class_id' => 'integer',
            'subject_id' => 'integer',
            'teacher_id' => 'integer',
            'acknowledged_by' => 'integer',
            'psychiatrist_reviewed_by' => 'integer',
            'resolved_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'report_date' => 'date',
            'psychiatrist_reviewed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function psychiatristReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'psychiatrist_reviewed_by');
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
