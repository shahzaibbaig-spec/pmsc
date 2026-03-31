<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CognitiveAssessmentSectionQuestion extends Model
{
    protected $fillable = [
        'section_id',
        'bank_question_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CognitiveAssessmentSection::class, 'section_id');
    }

    public function bankQuestion(): BelongsTo
    {
        return $this->belongsTo(CognitiveBankQuestion::class, 'bank_question_id');
    }
}
