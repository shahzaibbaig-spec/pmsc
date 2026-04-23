<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultLock extends Model
{
    public const TYPE_SOFT = 'soft';

    public const TYPE_FINAL = 'final';

    protected $fillable = [
        'session',
        'class_id',
        'exam_id',
        'lock_type',
        'locked_by',
        'locked_at',
        'unlocked_at',
        'unlocked_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'class_id' => 'integer',
            'exam_id' => 'integer',
            'locked_by' => 'integer',
            'unlocked_by' => 'integer',
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }
}
