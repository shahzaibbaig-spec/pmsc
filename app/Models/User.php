<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'must_change_password',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
        ];
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function principalMedicalReferrals(): HasMany
    {
        return $this->hasMany(MedicalReferral::class, 'principal_id');
    }

    public function doctorMedicalReferrals(): HasMany
    {
        return $this->hasMany(MedicalReferral::class, 'doctor_id');
    }

    public function createdFeeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class, 'created_by');
    }

    public function createdStudentFeeStructures(): HasMany
    {
        return $this->hasMany(StudentFeeStructure::class, 'created_by');
    }

    public function generatedFeeChallans(): HasMany
    {
        return $this->hasMany(FeeChallan::class, 'generated_by');
    }

    public function recordedFeePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'received_by');
    }

    public function assignedStudentSubjects(): HasMany
    {
        return $this->hasMany(StudentSubjectAssignment::class, 'assigned_by');
    }

    public function createdSubjectGroups(): HasMany
    {
        return $this->hasMany(SubjectGroup::class, 'created_by');
    }

    public function payrollProfile(): HasOne
    {
        return $this->hasOne(PayrollProfile::class);
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class, 'generated_by');
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function academicEvents(): HasMany
    {
        return $this->hasMany(AcademicEvent::class, 'created_by');
    }

    public function academicNotifications(): HasMany
    {
        return $this->hasMany(AcademicNotification::class);
    }

    public function approvedAdmitCardOverrides(): HasMany
    {
        return $this->hasMany(AdmitCardOverride::class, 'approved_by');
    }

    public function generatedExamSeatingPlans(): HasMany
    {
        return $this->hasMany(ExamSeatingPlan::class, 'generated_by');
    }

    public function markedExamAttendances(): HasMany
    {
        return $this->hasMany(ExamAttendance::class, 'marked_by');
    }

    public function createdPromotionCampaigns(): HasMany
    {
        return $this->hasMany(PromotionCampaign::class, 'created_by');
    }

    public function approvedPromotionCampaigns(): HasMany
    {
        return $this->hasMany(PromotionCampaign::class, 'approved_by');
    }

    public function reviewedInventoryDemands(): HasMany
    {
        return $this->hasMany(InventoryDemand::class, 'reviewed_by');
    }

    public function issuedInventoryRecords(): HasMany
    {
        return $this->hasMany(InventoryIssue::class, 'issued_by');
    }

    public function reviewedDeviceDeclarations(): HasMany
    {
        return $this->hasMany(TeacherDeviceDeclaration::class, 'reviewed_by');
    }

    public function inventoryStockMovements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'moved_by');
    }

    public function enabledCognitiveAssessmentAssignments(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentStudentAssignment::class, 'enabled_by');
    }

    public function disabledCognitiveAssessmentAssignments(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentStudentAssignment::class, 'disabled_by');
    }

    public function cognitiveAssessmentAttemptResets(): HasMany
    {
        return $this->hasMany(CognitiveAssessmentAttemptReset::class, 'reset_by');
    }

    public function preparedTeacherAcrs(): HasMany
    {
        return $this->hasMany(TeacherAcr::class, 'prepared_by');
    }

    public function reviewedTeacherAcrs(): HasMany
    {
        return $this->hasMany(TeacherAcr::class, 'reviewed_by');
    }

    public function markedTeacherAttendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class, 'marked_by');
    }

}
