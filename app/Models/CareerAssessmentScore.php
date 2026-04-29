<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerAssessmentScore extends Model
{
    protected $fillable = ['career_assessment_id', 'category', 'score', 'remarks'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CareerAssessment::class, 'career_assessment_id');
    }
}
