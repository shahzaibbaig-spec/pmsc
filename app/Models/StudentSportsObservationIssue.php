<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSportsObservationIssue extends Model
{
    protected $fillable = [
        'student_sports_observation_id',
        'issue_type',
        'issue_label',
        'auto_message',
    ];

    protected function casts(): array
    {
        return [
            'student_sports_observation_id' => 'integer',
        ];
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(StudentSportsObservation::class, 'student_sports_observation_id');
    }
}

