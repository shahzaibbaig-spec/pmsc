<?php

namespace App\Modules\Medical\Services;

use App\Models\Student;
use App\Models\StudentCbcReport;
use App\Models\StudentMedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StudentCbcReportService
{
    /**
     * @param array<string,mixed> $data
     */
    public function createForMedicalRecord(StudentMedicalRecord $record, array $data, User $doctor): StudentCbcReport
    {
        if (! $doctor->hasRole('Doctor')) {
            throw new RuntimeException('Only doctor users can create CBC reports.');
        }

        if ((int) ($record->doctor_id ?? 0) !== (int) $doctor->id) {
            throw new RuntimeException('You can only add CBC reports to your assigned medical visits.');
        }

        return DB::transaction(function () use ($record, $data, $doctor): StudentCbcReport {
            $payload = $this->cbcPayload($data, $doctor);
            $payload['student_medical_record_id'] = (int) $record->id;
            $payload['student_id'] = (int) $record->student_id;
            $payload['doctor_id'] = (int) $doctor->id;

            return StudentCbcReport::query()->create($payload);
        });
    }

    /**
     * @param array<string,mixed> $data
     */
    public function createStandaloneForStudent(Student $student, array $data, User $doctor): StudentCbcReport
    {
        if (! $doctor->hasRole('Doctor')) {
            throw new RuntimeException('Only doctor users can create CBC reports.');
        }

        return DB::transaction(function () use ($student, $data, $doctor): StudentCbcReport {
            $payload = $this->cbcPayload($data, $doctor);
            $payload['student_medical_record_id'] = isset($data['student_medical_record_id']) ? (int) $data['student_medical_record_id'] : null;
            $payload['student_id'] = (int) $student->id;
            $payload['doctor_id'] = (int) $doctor->id;

            return StudentCbcReport::query()->create($payload);
        });
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateReport(StudentCbcReport $report, array $data, User $user): StudentCbcReport
    {
        if ($user->hasRole('Doctor') && (int) $report->doctor_id !== (int) $user->id) {
            throw new RuntimeException('You are not authorized to update this CBC report.');
        }

        if (! $user->hasAnyRole(['Doctor', 'Principal', 'Admin'])) {
            throw new RuntimeException('You are not authorized to update this CBC report.');
        }

        return DB::transaction(function () use ($report, $data, $user): StudentCbcReport {
            $payload = $this->cbcPayload($data, $user);
            $report->update($payload);

            return $report->fresh([
                'student:id,student_id,name,class_id,date_of_birth,age',
                'student.classRoom:id,name,section',
                'doctor:id,name',
                'medicalRecord:id,student_id,doctor_id,problem,diagnosis,prescription,visit_date,session',
            ]);
        });
    }

    /**
     * @param array<string,mixed> $filters
     * @return Collection<int,StudentCbcReport>
     */
    public function getStudentReports(Student $student, array $filters = []): Collection
    {
        $query = StudentCbcReport::query()
            ->with([
                'doctor:id,name',
                'medicalRecord:id,student_id,visit_date,problem,session',
            ])
            ->where('student_id', (int) $student->id)
            ->orderByDesc('report_date')
            ->orderByDesc('id');

        if (! empty($filters['session'])) {
            $query->where('session', (string) $filters['session']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('report_date', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('report_date', '<=', (string) $filters['date_to']);
        }

        return $query->get();
    }

    public function getReportForPrint(StudentCbcReport $report): StudentCbcReport
    {
        return $report->load([
            'student:id,student_id,name,class_id,date_of_birth,age',
            'student.classRoom:id,name,section',
            'doctor:id,name',
            'medicalRecord:id,student_id,problem,diagnosis,prescription,visit_date,session',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function cbcPayload(array $data, User $user): array
    {
        return [
            'session' => (string) $data['session'],
            'report_date' => (string) $data['report_date'],
            'machine_report_no' => $data['machine_report_no'] ?? null,
            'hemoglobin' => $data['hemoglobin'] ?? null,
            'rbc_count' => $data['rbc_count'] ?? null,
            'wbc_count' => $data['wbc_count'] ?? null,
            'platelet_count' => $data['platelet_count'] ?? null,
            'hematocrit_pcv' => $data['hematocrit_pcv'] ?? null,
            'mcv' => $data['mcv'] ?? null,
            'mch' => $data['mch'] ?? null,
            'mchc' => $data['mchc'] ?? null,
            'neutrophils' => $data['neutrophils'] ?? null,
            'lymphocytes' => $data['lymphocytes'] ?? null,
            'monocytes' => $data['monocytes'] ?? null,
            'eosinophils' => $data['eosinophils'] ?? null,
            'basophils' => $data['basophils'] ?? null,
            'esr' => $data['esr'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $data['created_by'] ?? (int) $user->id,
            'updated_by' => (int) $user->id,
        ];
    }
}
