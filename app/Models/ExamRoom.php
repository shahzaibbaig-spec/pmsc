<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamRoom extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function seatAssignments(): HasMany
    {
        return $this->hasMany(ExamSeatAssignment::class);
    }

    public function invigilatorAssignments(): HasMany
    {
        return $this->hasMany(ExamRoomInvigilator::class, 'room_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ExamAttendance::class, 'room_id');
    }
}
