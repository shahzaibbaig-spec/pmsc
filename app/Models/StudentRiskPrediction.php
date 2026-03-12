<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRiskPrediction extends Model
{
    protected $fillable = [
        'session',
        'student_id',
        'target_exam',
        'predicted_percentage',
        'risk_level',
        'confidence',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'predicted_percentage' => 'float',
            'confidence' => 'float',
            'explanation' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
