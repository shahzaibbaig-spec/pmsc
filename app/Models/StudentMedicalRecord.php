<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Compatibility model for "student medical record" naming.
 * Uses the existing medical_referrals table as the visit record source.
 */
class StudentMedicalRecord extends MedicalReferral
{
    protected $table = 'medical_referrals';

    public function cbcReports(): HasMany
    {
        return $this->hasMany(StudentCbcReport::class, 'student_medical_record_id');
    }
}
