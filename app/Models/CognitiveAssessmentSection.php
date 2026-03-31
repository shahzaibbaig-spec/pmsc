<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CognitiveAssessmentSection extends Model
{
    public const SKILL_VERBAL = 'verbal';

    public const SKILL_NON_VERBAL = 'non_verbal';

    public const SKILL_QUANTITATIVE = 'quantitative';

    public const SKILL_SPATIAL = 'spatial';

    protected $fillable = [
        'assessment_id',
        'skill',
        'title',
        'duration_seconds',
        'total_marks',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'total_marks' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CognitiveAssessment::class, 'assessment_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentQuestion::class, 'section_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function questionAssignments(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentSectionQuestion::class, 'section_id')
            ->with('bankQuestion.questionBank')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function bankQuestions(): BelongsToMany
    {
        return $this->belongsToMany(
            CognitiveBankQuestion::class,
            'cognitive_assessment_section_questions',
            'section_id',
            'bank_question_id'
        )
            ->withPivot(['id', 'sort_order'])
            ->withTimestamps()
            ->orderBy('cognitive_assessment_section_questions.sort_order')
            ->orderBy('cognitive_bank_questions.sort_order')
            ->orderBy('cognitive_bank_questions.id');
    }

    public function resolvedQuestions(): Collection
    {
        if ($this->relationLoaded('questionAssignments') && $this->questionAssignments->isNotEmpty()) {
            return $this->questionAssignments
                ->pluck('bankQuestion')
                ->filter();
        }

        if ($this->relationLoaded('bankQuestions') && $this->bankQuestions->isNotEmpty()) {
            return $this->bankQuestions;
        }

        if ($this->relationLoaded('questions')) {
            return $this->questions;
        }

        $assignedQuestions = $this->questionAssignments()
            ->with('bankQuestion.questionBank')
            ->get()
            ->pluck('bankQuestion')
            ->filter();

        if ($assignedQuestions->isNotEmpty()) {
            return $assignedQuestions;
        }

        return $this->questions()->get();
    }

    public function skillLabel(): string
    {
        return match ($this->skill) {
            self::SKILL_VERBAL => 'Verbal Reasoning',
            self::SKILL_NON_VERBAL => 'Non-Verbal Reasoning',
            self::SKILL_QUANTITATIVE => 'Quantitative Reasoning',
            self::SKILL_SPATIAL => 'Spatial Reasoning',
            default => ucfirst(str_replace('_', ' ', (string) $this->skill)),
        };
    }
}
