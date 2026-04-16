<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyDiaryAttachment extends Model
{
    protected $fillable = [
        'daily_diary_id',
        'file_path',
        'file_name',
    ];

    protected function casts(): array
    {
        return [
            'daily_diary_id' => 'integer',
        ];
    }

    public function dailyDiary(): BelongsTo
    {
        return $this->belongsTo(DailyDiary::class);
    }
}

