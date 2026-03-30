<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryIssue extends Model
{
    protected $fillable = [
        'teacher_id',
        'demand_id',
        'issue_date',
        'session',
        'issued_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(InventoryDemand::class, 'demand_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryIssueLine::class, 'issue_id');
    }
}
