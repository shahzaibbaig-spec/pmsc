<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeBlockOverride extends Model
{
    public const TYPE_RESULT_CARD = 'result_card';

    public const TYPE_ADMIT_CARD = 'admit_card';

    public const TYPE_ID_CARD = 'id_card';

    protected $fillable = [
        'student_id',
        'session',
        'block_type',
        'is_allowed',
        'reason',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
