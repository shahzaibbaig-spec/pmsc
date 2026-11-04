<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class HostelRoom extends Model
{
    public const DEFAULT_CAPACITY = 1;

    protected $fillable = [
        'hostel_id',
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
            'hostel_id' => 'integer',
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

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
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

    public function scopeWithResidentCount(Builder $query): Builder
    {
        if (! Schema::hasTable('hostel_residents')) {
            return $query->selectRaw('0 as resident_beds');
        }

        return $query->selectSub(
            HostelResident::query()->selectRaw('COUNT(*)')
                ->whereColumn('hostel_residents.hostel_id', 'hostel_rooms.hostel_id')
                ->whereColumn('hostel_residents.room_group', 'hostel_rooms.room_name')
                ->where('is_active', true),
            'resident_beds'
        );
    }

    public function getOccupiedBedsAttribute($value): int
    {
        return (int) $value + (int) ($this->attributes['resident_beds'] ?? 0);
    }

    public function activeResidents(): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('hostel_residents')) {
            return collect();
        }

        return HostelResident::query()->where('hostel_id', $this->hostel_id)
            ->where('room_group', $this->room_name)->where('is_active', true)->orderBy('name')->get();
    }
}
