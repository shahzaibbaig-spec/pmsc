<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CognitiveAssessmentQuestion extends Model
{
    protected $fillable = [
        'section_id',
        'question_type',
        'difficulty_level',
        'question_text',
        'question_image',
        'options',
        'correct_answer',
        'explanation',
        'marks',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'marks' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CognitiveAssessmentSection::class, 'section_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentResponse::class, 'question_id');
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
}
