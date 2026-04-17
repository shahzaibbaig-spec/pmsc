<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelNightAttendance extends Model
{
    protected $table = 'hostel_night_attendance';

    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_LATE_RETURN = 'late_return';

    protected $fillable = [
        'student_id',
        'hostel_room_id',
        'attendance_date',
        'status',
        'remarks',
        'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'hostel_room_id' => 'integer',
            'attendance_date' => 'date',
            'marked_by' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function hostelRoom(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'hostel_room_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
