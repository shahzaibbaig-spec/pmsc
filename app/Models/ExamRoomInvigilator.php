<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamRoomInvigilator extends Model
{
    protected $fillable = [
        'exam_session_id',
        'room_id',
        'teacher_id',
    ];

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class, 'room_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
