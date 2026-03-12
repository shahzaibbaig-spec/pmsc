<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportComment extends Model
{
    protected $fillable = [
        'student_id',
        'session',
        'exam_type',
        'generated_by',
        'auto_comment',
        'final_comment',
        'is_edited',
    ];

    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

