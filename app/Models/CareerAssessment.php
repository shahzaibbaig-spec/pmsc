<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerAssessment extends Model
{
    protected $fillable = [
        'student_id',
        'counselor_id',
        'session',
        'assessment_date',
        'title',
        'overall_summary',
        'recommended_stream',
        'alternative_stream',
        'suggested_subjects',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['assessment_date' => 'date'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function counselor(): BelongsTo { return $this->belongsTo(User::class, 'counselor_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function scores(): HasMany { return $this->hasMany(CareerAssessmentScore::class); }
}
