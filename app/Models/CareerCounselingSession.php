<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerCounselingSession extends Model
{
    protected $fillable = [
        'student_id',
        'career_profile_id',
        'counselor_id',
        'session',
        'counseling_date',
        'discussion_topic',
        'student_interests',
        'academic_concerns',
        'recommended_subjects',
        'recommended_career_path',
        'counselor_advice',
        'private_notes',
        'follow_up_required',
        'follow_up_date',
        'status',
        'urgent_guidance_required',
        'urgent_reason',
        'urgent_marked_at',
        'urgent_marked_by',
        'visibility',
        'public_summary',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'counseling_date' => 'date',
            'follow_up_date' => 'date',
            'follow_up_required' => 'boolean',
            'urgent_guidance_required' => 'boolean',
            'urgent_marked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function careerProfile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class);
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function urgentMarkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'urgent_marked_by');
    }

    public function parentMeetings(): HasMany
    {
        return $this->hasMany(CareerParentMeeting::class, 'counseling_session_id');
    }
}
