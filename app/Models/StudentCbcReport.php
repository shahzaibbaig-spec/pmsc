<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCbcReport extends Model
{
    protected $fillable = [
        'student_medical_record_id',
        'student_id',
        'doctor_id',
        'session',
        'report_date',
        'machine_report_no',
        'hemoglobin',
        'rbc_count',
        'wbc_count',
        'platelet_count',
        'hematocrit_pcv',
        'mcv',
        'mch',
        'mchc',
        'neutrophils',
        'lymphocytes',
        'monocytes',
        'eosinophils',
        'basophils',
        'esr',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'hemoglobin' => 'decimal:2',
            'rbc_count' => 'decimal:2',
            'wbc_count' => 'decimal:2',
            'platelet_count' => 'decimal:2',
            'hematocrit_pcv' => 'decimal:2',
            'mcv' => 'decimal:2',
            'mch' => 'decimal:2',
            'mchc' => 'decimal:2',
            'neutrophils' => 'decimal:2',
            'lymphocytes' => 'decimal:2',
            'monocytes' => 'decimal:2',
            'eosinophils' => 'decimal:2',
            'basophils' => 'decimal:2',
            'esr' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function studentMedicalRecord(): BelongsTo
    {
        return $this->belongsTo(StudentMedicalRecord::class, 'student_medical_record_id');
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalReferral::class, 'student_medical_record_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
