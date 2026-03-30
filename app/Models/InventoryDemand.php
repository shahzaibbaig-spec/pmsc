<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryDemand extends Model
{
    protected $fillable = [
        'teacher_id',
        'request_date',
        'session',
        'status',
        'teacher_note',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryDemandLine::class, 'demand_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(InventoryIssue::class, 'demand_id');
    }
}
