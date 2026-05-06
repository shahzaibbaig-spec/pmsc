<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KcatQuestion extends Model
{
    protected $fillable = [
        'kcat_test_id', 'kcat_section_id', 'question_type', 'difficulty', 'question_text',
        'question_image', 'explanation', 'marks', 'sort_order', 'is_active', 'review_status',
        'times_attempted', 'times_correct', 'average_response_time', 'discrimination_flag',
        'retired_at', 'retired_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'marks' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'times_attempted' => 'integer',
            'times_correct' => 'integer',
            'average_response_time' => 'decimal:2',
            'retired_at' => 'datetime',
        ];
    }

    public function test(): BelongsTo { return $this->belongsTo(KcatTest::class, 'kcat_test_id'); }
    public function section(): BelongsTo { return $this->belongsTo(KcatSection::class, 'kcat_section_id'); }
    public function options(): HasMany { return $this->hasMany(KcatQuestionOption::class)->orderBy('sort_order')->orderBy('id'); }
    public function answers(): HasMany { return $this->hasMany(KcatAnswer::class); }
    public function reviews(): HasMany { return $this->hasMany(KcatQuestionReview::class)->latest(); }
    public function latestReview(): HasOne { return $this->hasOne(KcatQuestionReview::class)->latestOfMany(); }
    public function retiredBy(): BelongsTo { return $this->belongsTo(User::class, 'retired_by'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
