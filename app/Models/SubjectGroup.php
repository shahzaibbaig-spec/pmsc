<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectGroup extends Model
{
    protected $fillable = [
        'session',
        'class_id',
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_group_subject',
            'subject_group_id',
            'subject_id'
        )->withTimestamps();
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentSubjectAssignment::class, 'subject_group_id');
    }
}

