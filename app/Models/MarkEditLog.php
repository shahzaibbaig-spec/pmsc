<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkEditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'mark_id',
        'old_marks',
        'new_marks',
        'old_grade',
        'new_grade',
        'edited_by',
        'edit_reason',
        'action_type',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'old_marks' => 'integer',
            'new_marks' => 'integer',
            'old_grade' => 'string',
            'new_grade' => 'string',
            'edited_at' => 'datetime',
        ];
    }

    public function mark(): BelongsTo
    {
        return $this->belongsTo(Mark::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
