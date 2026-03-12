<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentPerformanceFeature extends Model
{
    protected $fillable = [
        'session',
        'student_id',
        'attendance_rate',
        'avg_class_test',
        'avg_bimonthly',
        'avg_first_term',
        'trend_slope',
        'last_assessment_score',
    ];

    protected function casts(): array
    {
        return [
            'attendance_rate' => 'float',
            'avg_class_test' => 'float',
            'avg_bimonthly' => 'float',
            'avg_first_term' => 'float',
            'trend_slope' => 'float',
            'last_assessment_score' => 'float',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(StudentRiskPrediction::class, 'student_id', 'student_id')
            ->where('session', $this->session);
    }
}
