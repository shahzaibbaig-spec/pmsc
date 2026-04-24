<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WardenDailyReport extends Model
{
    protected $fillable = [
        'hostel_id',
        'report_date',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hostel_id' => 'integer',
            'report_date' => 'date',
            'created_by' => 'integer',
        ];
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(WardenAttendance::class, 'report_id');
    }

    public function disciplineLogs(): HasMany
    {
        return $this->hasMany(WardenDisciplineLog::class, 'report_id');
    }

    public function healthLogs(): HasMany
    {
        return $this->hasMany(WardenHealthLog::class, 'report_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

