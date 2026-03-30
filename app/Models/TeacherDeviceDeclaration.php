<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDeviceDeclaration extends Model
{
    protected $fillable = [
        'teacher_id',
        'device_type',
        'serial_number',
        'brand',
        'model',
        'status',
        'asset_unit_id',
        'teacher_note',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function assetUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryAssetUnit::class, 'asset_unit_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
