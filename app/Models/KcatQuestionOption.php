<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcatQuestionOption extends Model
{
    protected $fillable = ['kcat_question_id', 'option_text', 'option_image', 'is_correct', 'sort_order'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'sort_order' => 'integer'];
    }

    public function question(): BelongsTo { return $this->belongsTo(KcatQuestion::class, 'kcat_question_id'); }
}
