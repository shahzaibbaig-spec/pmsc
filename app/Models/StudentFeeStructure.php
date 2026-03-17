<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeStructure extends Model
{
    protected $fillable = [
        'student_id',
        'session',
        'tuition_fee',
        'computer_fee',
        'exam_fee',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tuition_fee' => 'decimal:2',
            'computer_fee' => 'decimal:2',
            'exam_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
