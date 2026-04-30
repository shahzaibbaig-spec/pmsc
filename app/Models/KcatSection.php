<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KcatSection extends Model
{
    protected $fillable = ['kcat_test_id', 'name', 'code', 'description', 'sort_order', 'total_questions', 'total_marks'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'total_questions' => 'integer', 'total_marks' => 'integer'];
    }

    public function test(): BelongsTo { return $this->belongsTo(KcatTest::class, 'kcat_test_id'); }
    public function questions(): HasMany { return $this->hasMany(KcatQuestion::class)->orderBy('sort_order')->orderBy('id'); }
    public function scores(): HasMany { return $this->hasMany(KcatScore::class); }
}
