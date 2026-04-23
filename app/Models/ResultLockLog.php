<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultLockLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'lock_type',
        'session',
        'class_id',
        'exam_id',
        'performed_by',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'class_id' => 'integer',
            'exam_id' => 'integer',
            'performed_by' => 'integer',
            'created_at' => 'datetime',
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

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
