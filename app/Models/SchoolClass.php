<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $fillable = [
        'name',
        'section',
        'status',
        'class_teacher_id',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'class_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'class_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'class_subject',
            'class_id',
            'subject_id'
        );
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ClassSection::class, 'class_id');
    }

    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    public function teacherSubjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class, 'class_id');
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class, 'class_id');
    }

    public function feeChallans(): HasMany
    {
        return $this->hasMany(FeeChallan::class, 'class_id');
    }

    public function subjectGroups(): HasMany
    {
        return $this->hasMany(SubjectGroup::class, 'class_id');
    }
}
