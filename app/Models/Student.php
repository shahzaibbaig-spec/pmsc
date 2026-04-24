<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'qr_token',
        'name',
        'father_name',
        'class_id',
        'date_of_birth',
        'age',
        'gender',
        'contact',
        'address',
        'photo_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'student_subject');
    }

    public function sessionSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')
            ->withPivot('session')
            ->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function dailyAttendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(StudentResult::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class);
    }

    public function medicalReferrals(): HasMany
    {
        return $this->hasMany(MedicalReferral::class);
    }

    public function cbcReports(): HasMany
    {
        return $this->hasMany(StudentCbcReport::class);
    }

    public function disciplineComplaints(): HasMany
    {
        return $this->hasMany(DisciplineComplaint::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function studentSubjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function subjectMatrixAssignments(): HasMany
    {
        return $this->hasMany(StudentSubjectAssignment::class);
    }

    public function performanceFeatures(): HasMany
    {
        return $this->hasMany(StudentPerformanceFeature::class);
    }

    public function riskPredictions(): HasMany
    {
        return $this->hasMany(StudentRiskPrediction::class);
    }

    public function learningProfiles(): HasMany
    {
        return $this->hasMany(StudentLearningProfile::class);
    }

    public function reportComments(): HasMany
    {
        return $this->hasMany(ReportComment::class);
    }

    public function feeAssignments(): HasMany
    {
        return $this->hasMany(StudentFeeAssignment::class);
    }

    public function customFeeStructures(): HasMany
    {
        return $this->hasMany(StudentFeeStructure::class);
    }

    public function feeChallans(): HasMany
    {
        return $this->hasMany(FeeChallan::class);
    }

    public function cognitiveAssessmentAttempts(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentAttempt::class);
    }

    public function cognitiveAssessmentAssignments(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentStudentAssignment::class);
    }

    public function cognitiveAssessmentAttemptResets(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentAttemptReset::class);
    }

    public function feeInstallmentPlans(): HasMany
    {
        return $this->hasMany(FeeInstallmentPlan::class);
    }

    public function feeInstallments(): HasMany
    {
        return $this->hasMany(FeeInstallment::class);
    }

    public function arrears(): HasMany
    {
        return $this->hasMany(StudentArrear::class);
    }

    public function feeDefaulters(): HasMany
    {
        return $this->hasMany(FeeDefaulter::class);
    }

    public function feeReminders(): HasMany
    {
        return $this->hasMany(FeeReminder::class);
    }

    public function feeBlockOverrides(): HasMany
    {
        return $this->hasMany(FeeBlockOverride::class);
    }

    public function admitCardOverrides(): HasMany
    {
        return $this->hasMany(AdmitCardOverride::class);
    }

    public function examSeatAssignments(): HasMany
    {
        return $this->hasMany(ExamSeatAssignment::class);
    }

    public function examAttendances(): HasMany
    {
        return $this->hasMany(ExamAttendance::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(StudentPromotion::class);
    }

    public function classHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function hostelRoomAllocations(): HasMany
    {
        return $this->hasMany(HostelRoomAllocation::class);
    }

    public function activeHostelRoomAllocation(): HasOne
    {
        return $this->hasOne(HostelRoomAllocation::class)
            ->where(function (Builder $query): void {
                $query->where('status', HostelRoomAllocation::STATUS_ACTIVE)
                    ->orWhere('is_active', true);
            });
    }

    public function hostelAllocation(): HasOne
    {
        return $this->hasOne(HostelRoomAllocation::class)
            ->where(function (Builder $query): void {
                $query->where('status', HostelRoomAllocation::STATUS_ACTIVE)
                    ->orWhere('is_active', true);
            });
    }

    public function scopeForWarden(Builder $query, User $user): Builder
    {
        if (! $user->isWarden()) {
            return $query;
        }

        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('hostelAllocation', function (Builder $allocationQuery) use ($hostelId): void {
            $allocationQuery
                ->where('hostel_id', $hostelId)
                ->where(function (Builder $statusQuery): void {
                    $statusQuery->where('status', HostelRoomAllocation::STATUS_ACTIVE)
                        ->orWhere('is_active', true);
                });
        });
    }

    public function hostelLeaveRequests(): HasMany
    {
        return $this->hasMany(HostelLeaveRequest::class);
    }

    public function hostelNightAttendances(): HasMany
    {
        return $this->hasMany(HostelNightAttendance::class);
    }

    public function wardenAttendanceRows(): HasMany
    {
        return $this->hasMany(WardenAttendance::class);
    }

    public function wardenDisciplineLogs(): HasMany
    {
        return $this->hasMany(WardenDisciplineLog::class);
    }

    public function wardenHealthLogs(): HasMany
    {
        return $this->hasMany(WardenHealthLog::class);
    }
}
