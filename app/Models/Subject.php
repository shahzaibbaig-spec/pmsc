<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_subject');
    }

    public function sessionStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_subjects')
            ->withPivot('session')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(StudentResult::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function classRooms(): BelongsToMany
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'class_subject',
            'subject_id',
            'class_id'
        );
    }

    public function subjectGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            SubjectGroup::class,
            'subject_group_subject',
            'subject_id',
            'subject_group_id'
        )->withTimestamps();
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function subjectPeriodRules(): HasMany
    {
        return $this->hasMany(SubjectPeriodRule::class);
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    public function teacherSubjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }
}
