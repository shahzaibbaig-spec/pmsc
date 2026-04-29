<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerProfile extends Model
{
    protected $fillable = [
        'student_id',
        'session',
        'current_class_id',
        'strengths',
        'weaknesses',
        'interests',
        'preferred_subjects',
        'career_goals',
        'parent_expectations',
        'recommended_career_paths',
        'counselor_notes',
        'visibility',
        'public_summary',
        'created_by',
        'updated_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function currentClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'current_class_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function counselingSessions(): HasMany
    {
        return $this->hasMany(CareerCounselingSession::class);
    }

    public function parentMeetings(): HasMany
    {
        return $this->hasMany(CareerParentMeeting::class);
    }
}
