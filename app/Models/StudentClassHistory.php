<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClassHistory extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROMOTED = 'promoted';
    public const STATUS_RETAINED = 'retained';
    public const STATUS_CONDITIONAL_PROMOTED = 'conditional_promoted';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_PROMOTED,
        self::STATUS_RETAINED,
        self::STATUS_CONDITIONAL_PROMOTED,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'student_id',
        'class_id',
        'session',
        'status',
        'joined_on',
        'left_on',
    ];

    protected function casts(): array
    {
        return [
            'joined_on' => 'date',
            'left_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}

