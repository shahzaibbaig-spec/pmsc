<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CognitiveBankQuestion extends Model
{
    protected $fillable = [
        'question_bank_id',
        'skill',
        'question_type',
        'difficulty_level',
        'question_text',
        'question_image',
        'explanation',
        'options',
        'correct_answer',
        'marks',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'marks' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(CognitiveQuestionBank::class, 'question_bank_id');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(
            CognitiveAssessmentSection::class,
            'cognitive_assessment_section_questions',
            'bank_question_id',
            'section_id'
        )
            ->withPivot(['id', 'sort_order'])
            ->withTimestamps();
    }

    public function sectionAssignments(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentSectionQuestion::class, 'bank_question_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentResponse::class, 'bank_question_id');
    }

    public function getQuestionImageUrlAttribute(): ?string
    {
        $path = trim((string) $this->question_image);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function shortPrompt(): string
    {
        $prompt = trim((string) $this->question_text);

        if ($prompt !== '') {
            return Str::limit($prompt, 100);
        }

        return 'Image-based '.str_replace('_', ' ', $this->question_type).' question';
    }
}
