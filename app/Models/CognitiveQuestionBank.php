<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CognitiveQuestionBank extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankQuestions(): HasMany
    {
        return $this->hasMany(CognitiveBankQuestion::class, 'question_bank_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
