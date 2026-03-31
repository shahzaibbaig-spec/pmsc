<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CognitiveAssessment extends Model
{
    public const LEVEL_4_SLUG = 'cognitive-skills-assessment-test-level-4';

    public const LEVEL_4_TITLE = 'Cognitive Skills Assessment Test Level 4';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentSection::class, 'assessment_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentAttempt::class, 'assessment_id');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentStudentAssignment::class, 'assessment_id');
    }
}
