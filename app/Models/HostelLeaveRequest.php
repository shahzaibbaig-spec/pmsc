<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelLeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'student_id',
        'hostel_room_id',
        'leave_from',
        'leave_to',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'returned_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'hostel_room_id' => 'integer',
            'leave_from' => 'datetime',
            'leave_to' => 'datetime',
            'requested_by' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'returned_at' => 'datetime',
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

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForWarden(Builder $query, User $user): Builder
    {
        if (! $user->isWarden()) {
            return $query;
        }

        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('hostelRoom', fn (Builder $roomQuery) => $roomQuery->where('hostel_id', $hostelId));
    }
}
