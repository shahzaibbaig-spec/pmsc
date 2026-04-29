<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerParentMeeting extends Model
{
    protected $fillable = [
        'student_id',
        'career_profile_id',
        'counseling_session_id',
        'counselor_id',
        'session',
        'meeting_date',
        'parent_concerns',
        'parent_expectations',
        'counselor_recommendation',
        'agreed_action_plan',
        'next_meeting_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'next_meeting_date' => 'date',
        ];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function careerProfile(): BelongsTo { return $this->belongsTo(CareerProfile::class); }
    public function counselingSession(): BelongsTo { return $this->belongsTo(CareerCounselingSession::class); }
    public function counselor(): BelongsTo { return $this->belongsTo(User::class, 'counselor_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
