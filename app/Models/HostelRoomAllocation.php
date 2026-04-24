<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelRoomAllocation extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SHIFTED = 'shifted';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'hostel_room_id',
        'hostel_id',
        'student_id',
        'session',
        'allocated_from',
        'allocated_to',
        'status',
        'is_active',
        'remarks',
        'allocated_by',
    ];

    protected function casts(): array
    {
        return [
            'hostel_room_id' => 'integer',
            'hostel_id' => 'integer',
            'student_id' => 'integer',
            'allocated_from' => 'date',
            'allocated_to' => 'date',
            'is_active' => 'boolean',
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

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $statusQuery): void {
            $statusQuery->where('status', self::STATUS_ACTIVE)
                ->orWhere('is_active', true);
        });
    }

    public function scopeForWarden(Builder $query, User $user): Builder
    {
        if (! $user->isWarden()) {
            return $query;
        }

        $hostelId = (int) ($user->hostel_id ?? 0);

        return $hostelId > 0
            ? $query->where('hostel_id', $hostelId)
            : $query->whereRaw('1 = 0');
    }
}
