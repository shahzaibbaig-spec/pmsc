<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelRoomAllocation extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SHIFTED = 'shifted';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'hostel_room_id',
        'student_id',
        'allocated_from',
        'allocated_to',
        'status',
        'remarks',
        'allocated_by',
    ];

    protected function casts(): array
    {
        return [
            'hostel_room_id' => 'integer',
            'student_id' => 'integer',
            'allocated_from' => 'date',
            'allocated_to' => 'date',
            'allocated_by' => 'integer',
        ];
    }

    public function hostelRoom(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'hostel_room_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
