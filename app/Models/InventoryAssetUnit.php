<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAssetUnit extends Model
{
    protected $fillable = [
        'item_id',
        'serial_number',
        'brand',
        'model',
        'status',
        'issued_to_teacher_id',
        'issued_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function issuedToTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'issued_to_teacher_id');
    }

    public function deviceDeclarations(): HasMany
    {
        return $this->hasMany(TeacherDeviceDeclaration::class, 'asset_unit_id');
    }
}
