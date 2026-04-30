<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KcatQuestion extends Model
{
    protected $fillable = [
        'kcat_test_id', 'kcat_section_id', 'question_type', 'difficulty', 'question_text',
        'question_image', 'explanation', 'marks', 'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['marks' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function test(): BelongsTo { return $this->belongsTo(KcatTest::class, 'kcat_test_id'); }
    public function section(): BelongsTo { return $this->belongsTo(KcatSection::class, 'kcat_section_id'); }
    public function options(): HasMany { return $this->hasMany(KcatQuestionOption::class)->orderBy('sort_order')->orderBy('id'); }
    public function answers(): HasMany { return $this->hasMany(KcatAnswer::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
