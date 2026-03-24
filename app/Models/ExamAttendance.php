<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttendance extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LATE = 'late';

    /**
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_ABSENT,
        self::STATUS_LATE,
    ];

    protected $fillable = [
        'exam_session_id',
        'room_id',
        'student_id',
        'seat_assignment_id',
        'status',
        'remarks',
        'marked_by',
        'marked_at',
    ];

    protected function casts(): array
    {
        return [
            'marked_at' => 'datetime',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ExamRoom::class, 'room_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function seatAssignment(): BelongsTo
    {
        return $this->belongsTo(ExamSeatAssignment::class, 'seat_assignment_id');
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
