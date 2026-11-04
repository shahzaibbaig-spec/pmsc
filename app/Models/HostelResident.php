<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelResident extends Model
{
    protected $fillable = [
        'hostel_id', 'name', 'date_of_birth', 'gender', 'room_group', 'is_active',
        'notes', 'import_key', 'created_by',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'is_active' => 'boolean', 'hostel_id' => 'integer'];
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function scopeForWarden(Builder $query, User $user): Builder
    {
        return $user->isWarden()
            ? $query->where('hostel_id', (int) ($user->hostel_id ?? 0))
            : $query;
    }
}
