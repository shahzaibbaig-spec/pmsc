<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'teacher_id',
        'user_id',
        'designation',
        'employee_code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class);
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    public function classTeacherClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'class_teacher_id');
    }

    public function classesAsClassTeacher(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'class_teacher_id');
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }

    public function examRoomInvigilatorAssignments(): HasMany
    {
        return $this->hasMany(ExamRoomInvigilator::class);
    }

    public function inventoryDemands(): HasMany
    {
        return $this->hasMany(InventoryDemand::class);
    }

    public function inventoryIssues(): HasMany
    {
        return $this->hasMany(InventoryIssue::class);
    }

    public function deviceDeclarations(): HasMany
    {
        return $this->hasMany(TeacherDeviceDeclaration::class);
    }

    public function issuedAssetUnits(): HasMany
    {
        return $this->hasMany(InventoryAssetUnit::class, 'issued_to_teacher_id');
    }

    public function acrs(): HasMany
    {
        return $this->hasMany(TeacherAcr::class);
    }

    public function teacherAttendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    public function dailyDiaries(): HasMany
    {
        return $this->hasMany(DailyDiary::class);
    }
}
