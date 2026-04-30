<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KcatTest extends Model
{
    protected $fillable = [
        'title', 'description', 'grade_from', 'grade_to', 'total_questions', 'total_marks',
        'duration_minutes', 'status', 'session', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['grade_from' => 'integer', 'grade_to' => 'integer', 'total_questions' => 'integer', 'total_marks' => 'integer', 'duration_minutes' => 'integer'];
    }

    public function sections(): HasMany { return $this->hasMany(KcatSection::class)->orderBy('sort_order')->orderBy('id'); }
    public function questions(): HasMany { return $this->hasMany(KcatQuestion::class)->orderBy('sort_order')->orderBy('id'); }
    public function assignments(): HasMany { return $this->hasMany(KcatAssignment::class); }
    public function attempts(): HasMany { return $this->hasMany(KcatAttempt::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
