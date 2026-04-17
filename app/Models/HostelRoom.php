<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelRoom extends Model
{
    public const DEFAULT_CAPACITY = 1;

    protected $fillable = [
        'room_name',
        'floor_number',
        'capacity',
        'gender',
        'notes',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'floor_number' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'created_by' => 'integer',
        ];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HostelRoomAllocation::class);
    }

    public function activeAllocations(): HasMany
    {
        return $this->hasMany(HostelRoomAllocation::class)
            ->where('status', 'active');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(HostelLeaveRequest::class);
    }

    public function nightAttendanceRows(): HasMany
    {
        return $this->hasMany(HostelNightAttendance::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
