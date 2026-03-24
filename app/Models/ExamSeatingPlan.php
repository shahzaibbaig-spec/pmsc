<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSeatingPlan extends Model
{
    protected $fillable = [
        'exam_session_id',
        'class_ids',
        'is_randomized',
        'total_students',
        'total_rooms',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'class_ids' => 'array',
            'is_randomized' => 'boolean',
            'total_students' => 'integer',
            'total_rooms' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function seatAssignments(): HasMany
    {
        return $this->hasMany(ExamSeatAssignment::class)
            ->orderBy('exam_room_id')
            ->orderBy('seat_number');
    }
}
