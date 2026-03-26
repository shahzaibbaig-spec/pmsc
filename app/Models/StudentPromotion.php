<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPromotion extends Model
{
    public const DECISION_PROMOTE = 'promote';
    public const DECISION_CONDITIONAL_PROMOTE = 'conditional_promote';
    public const DECISION_RETAIN = 'retain';

    public const DECISIONS = [
        self::DECISION_PROMOTE,
        self::DECISION_CONDITIONAL_PROMOTE,
        self::DECISION_RETAIN,
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXECUTED = 'executed';

    public const FINAL_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_EXECUTED,
    ];

    protected $fillable = [
        'promotion_campaign_id',
        'student_id',
        'from_class_id',
        'to_class_id',
        'final_percentage',
        'final_grade',
        'is_passed',
        'teacher_decision',
        'teacher_note',
        'principal_decision',
        'principal_note',
        'final_status',
    ];

    protected function casts(): array
    {
        return [
            'final_percentage' => 'decimal:2',
            'is_passed' => 'boolean',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromotionCampaign::class, 'promotion_campaign_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'from_class_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'to_class_id');
    }
}

