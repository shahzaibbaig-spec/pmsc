<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotebookObservationItem extends Model
{
    protected $fillable = [
        'notebook_observation_id',
        'checklist_text',
        'response',
        'comments',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'notebook_observation_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function notebookObservation(): BelongsTo
    {
        return $this->belongsTo(NotebookObservation::class);
    }
}
