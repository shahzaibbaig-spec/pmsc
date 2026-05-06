<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KcatQuestionOption extends Model
{
    protected $fillable = ['kcat_question_id', 'option_text', 'option_image', 'is_correct', 'sort_order'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'sort_order' => 'integer'];
    }

    protected $appends = [
        'option_image_url',
    ];

    public function question(): BelongsTo { return $this->belongsTo(KcatQuestion::class, 'kcat_question_id'); }

    public function getOptionImageUrlAttribute(): ?string
    {
        $path = trim((string) $this->option_image);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/', 'data:image/'])) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return null;
    }
}
