<?php

namespace App\Modules\Medical\Services;

use App\Models\MedicalReferral;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Notifications\DoctorResponseSubmittedNotification;
use App\Notifications\DoctorDirectVisitCreatedNotification;
use App\Notifications\MedicalReferralCreatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MedicalService
{
    public function listAvailableDoctors(): array
    {
        return $this->activeDoctorQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(static fn (User $doctor): array => [
                'id' => (int) $doctor->id,
                'name' => (string) $doctor->name,
                'email' => (string) ($doctor->email ?? ''),
            ])
            ->values()
            ->all();
    }

    public function listClassOptions(): array
    {
        return SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(static fn (SchoolClass $classRoom): array => [
                'id' => (int) $classRoom->id,
                'name' => trim((string) $classRoom->name.' '.(string) $classRoom->section),
            ])
            ->values()
            ->all();
    }

    public function sessionOptions(int $backward = 1, int $forward = 1): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        $sessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
    }

    public function resolveSession(?string $session): string
    {
        $candidate = trim((string) $session);
        if (preg_match('/^\d{4}-\d{4}$/', $candidate) === 1) {
            return $candidate;
        }

        $sessions = $this->sessionOptions();

        return $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));
    }

    public function studentSearch(string $query): array
    {
        if (trim($query) === '') {
            return [];
        }

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('status', 'active')
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('student_id', 'like', "%{$query}%")
                    ->orWhere('father_name', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'student_id', 'name', 'father_name', 'class_id']);

        return $students->map(function (Student $student): array {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'father_name' => $student->father_name,
                'class_name' => trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')),
            ];
        })->values()->all();
    }

    public function createReferral(int $principalUserId, array $data): MedicalReferral
    {
        $doctorId = (int) ($data['doctor_id'] ?? 0);
        $doctor = $this->resolveDoctorForReferral($doctorId);

        if (! $doctor) {
            throw new RuntimeException('No active doctor user found to receive this referral.');
        }

        $referral = DB::transaction(function () use ($principalUserId, $doctor, $data): MedicalReferral {
            $problem = $this->problemTextFromReferralInput($data['illness_type'], $data['illness_other_text'] ?? null);

            $referral = MedicalReferral::query()->create([
                'student_id' => (int) $data['student_id'],
                'principal_id' => $principalUserId,
                'doctor_id' => (int) $doctor->id,
                'source_type' => 'principal_referral',
                'referred_by' => $principalUserId,
                'added_by' => $principalUserId,
                'illness_type' => $data['illness_type'],
                'illness_other_text' => $data['illness_other_text'] ?? null,
                'problem' => $problem,
                'status' => 'pending',
                'visit_date' => Carbon::today()->toDateString(),
                'session' => $this->resolveSession($data['session'] ?? null),
                'referred_at' => now(),
            ]);

            $referral->load('student:id,name');
            $doctor->notify(new MedicalReferralCreatedNotification($referral));

            return $referral;
        });

        return $referral;
    }

    public function createDirectVisit(array $data, User $doctor): MedicalReferral
    {
        if (! $doctor->hasRole('Doctor')) {
            throw new RuntimeException('Only doctor users can create direct visits.');
        }

        $visitDate = Carbon::parse((string) $data['visit_date'])->toDateString();
        $session = $this->resolveSession(isset($data['session']) ? (string) $data['session'] : null);
        $problem = trim((string) $data['problem']);

        return DB::transaction(function () use ($data, $doctor, $visitDate, $session, $problem): MedicalReferral {
            $referral = MedicalReferral::query()->create([
                'student_id' => (int) $data['student_id'],
                'principal_id' => null,
                'doctor_id' => (int) $doctor->id,
                'source_type' => 'doctor_direct',
                'referred_by' => null,
                'added_by' => (int) $doctor->id,
                'illness_type' => 'other',
                'illness_other_text' => Str::limit($problem, 255, ''),
                'problem' => $problem,
                'diagnosis' => $data['diagnosis'] ?? null,
                'prescription' => $data['prescription'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
                'visit_date' => $visitDate,
                'session' => $session,
                'referred_at' => Carbon::parse($visitDate)->startOfDay(),
                'consulted_at' => now(),
                'completed_at' => now(),
            ]);

            $referral->load($this->referralRelations());
            $this->notifyPrincipalsOfDirectVisit($referral, $doctor);

            return $referral;
        });
    }

    public function notifyPrincipalsOfDirectVisit(MedicalReferral $referral, User $doctor): void
    {
        $principalsAndAdmins = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', ['Principal', 'Admin']))
            ->where(function (Builder $query): void {
                $query->whereNull('status')
                    ->orWhereIn('status', ['active', 'Active', 'ACTIVE', 'enabled', 'Enabled', 'ENABLED', '1', 1]);
            })
            ->get(['id', 'name', 'email']);

        $principalsAndAdmins
            ->unique('id')
            ->each(fn (User $recipient) => $recipient->notify(new DoctorDirectVisitCreatedNotification($referral, $doctor)));
    }

    public function getPrincipalMedicalCases(array $filters): LengthAwarePaginator
    {
        return $this->referralsForPrincipal($filters);
    }

    public function referralsForPrincipal(array $filters): LengthAwarePaginator
    {
        $query = MedicalReferral::query()
            ->with($this->referralRelations());

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 10));
    }

    public function getDoctorCases(int $doctorUserId, array $filters): LengthAwarePaginator
    {
        return $this->referralsForDoctor($doctorUserId, $filters);
    }

    public function referralsForDoctor(int $doctorUserId, array $filters): LengthAwarePaginator
    {
        $query = MedicalReferral::query()
            ->with($this->referralRelations())
            ->where('doctor_id', $doctorUserId);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 10));
    }

    public function completeReferralDiagnosis(int $doctorUserId, MedicalReferral $referral, array $data): MedicalReferral
    {
        return $this->updateByDoctor($doctorUserId, $referral, $data);
    }

    public function updateByDoctor(int $doctorUserId, MedicalReferral $referral, array $data): MedicalReferral
    {
        if ((int) $referral->doctor_id !== $doctorUserId) {
            throw new RuntimeException('You are not authorized to update this referral.');
        }

        $updatedReferral = DB::transaction(function () use ($doctorUserId, $referral, $data): MedicalReferral {
            $status = $data['status'];
            $referral->update([
                'diagnosis' => $data['diagnosis'],
                'prescription' => $data['prescription'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $status,
                'visit_date' => $referral->visit_date ?? now()->toDateString(),
                'consulted_at' => now(),
                'completed_at' => $status === 'completed' ? now() : null,
            ]);

            User::query()
                ->whereKey($doctorUserId)
                ->first()?->unreadNotifications()
                ->where('data->referral_id', $referral->id)
                ->update(['read_at' => now()]);

            return $referral->fresh($this->referralRelations());
        });

        $principal = $updatedReferral->principal;
        if ($principal && (int) $principal->id !== $doctorUserId) {
            $principal->notify(new DoctorResponseSubmittedNotification($updatedReferral));
        }

        return $updatedReferral;
    }

    public function reportData(User $user, array $filters): array
    {
        $baseQuery = $this->reportBaseQuery($user, $filters);
        $summary = $this->reportSummary(clone $baseQuery);

        $records = $baseQuery
            ->with($this->referralRelations())
            ->orderByDesc('id')
            ->get();

        return [
            'data' => $records->map(fn (MedicalReferral $referral): array => $this->mapRow($referral))->values()->all(),
            'summary' => $summary,
        ];
    }

    public function reportListData(User $user, array $filters): array
    {
        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $baseQuery = $this->reportBaseQuery($user, $filters);
        $summary = $this->reportSummary(clone $baseQuery);

        $paginator = $baseQuery
            ->with($this->referralRelations())
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn (MedicalReferral $referral): array => $this->mapRow($referral))->values()->all(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ];
    }

    public function mapPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn (MedicalReferral $referral): array => $this->mapRow($referral))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ];
    }

    private function reportBaseQuery(User $user, array $filters): Builder
    {
        $query = MedicalReferral::query();

        if ($user->hasRole('Doctor')) {
            $query->where('doctor_id', $user->id);
        }

        if (($filters['report_type'] ?? 'monthly') === 'monthly') {
            $query->whereRaw('YEAR(COALESCE(visit_date, DATE(created_at))) = ?', [(int) $filters['year']])
                ->whereRaw('MONTH(COALESCE(visit_date, DATE(created_at))) = ?', [(int) ($filters['month'] ?? now()->month)]);
        } else {
            $query->whereRaw('YEAR(COALESCE(visit_date, DATE(created_at))) = ?', [(int) $filters['year']]);
        }

        if (! empty($filters['student_id'])) {
            $query->where('student_id', (int) $filters['student_id']);
        }

        return $query;
    }

    private function reportSummary(Builder $query): array
    {
        $row = $query
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN illness_type = 'fever' THEN 1 ELSE 0 END) as fever")
            ->selectRaw("SUM(CASE WHEN illness_type = 'headache' THEN 1 ELSE 0 END) as headache")
            ->selectRaw("SUM(CASE WHEN illness_type = 'stomach_ache' THEN 1 ELSE 0 END) as stomach_ache")
            ->selectRaw("SUM(CASE WHEN illness_type = 'other' THEN 1 ELSE 0 END) as other")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'fever' => (int) ($row->fever ?? 0),
            'headache' => (int) ($row->headache ?? 0),
            'stomach_ache' => (int) ($row->stomach_ache ?? 0),
            'other' => (int) ($row->other ?? 0),
        ];
    }

    private function referralRelations(): array
    {
        return [
            'student:id,student_id,name,class_id',
            'student.classRoom:id,name,section',
            'principal:id,name',
            'doctor:id,name',
            'referredBy:id,name',
            'addedBy:id,name',
            'cbcReports:id,student_medical_record_id,report_date,machine_report_no,doctor_id,created_at',
            'cbcReports.doctor:id,name',
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = (string) ($filters['search'] ?? '');
        $status = (string) ($filters['status'] ?? '');
        $sourceType = (string) ($filters['source_type'] ?? '');
        $doctorId = isset($filters['doctor_id']) ? (int) $filters['doctor_id'] : 0;
        $studentId = isset($filters['student_id']) ? (int) $filters['student_id'] : 0;
        $classId = isset($filters['class_id']) ? (int) $filters['class_id'] : 0;
        $session = trim((string) ($filters['session'] ?? ''));
        $hasCbcReport = isset($filters['has_cbc_report']) ? (int) $filters['has_cbc_report'] : null;
        $dateFrom = (string) ($filters['date_from'] ?? '');
        $dateTo = (string) ($filters['date_to'] ?? '');
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('illness_type', 'like', "%{$search}%")
                    ->orWhere('illness_other_text', 'like', "%{$search}%")
                    ->orWhere('problem', 'like', "%{$search}%")
                    ->orWhere('diagnosis', 'like', "%{$search}%")
                    ->orWhere('prescription', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search): void {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('principal', fn ($pq) => $pq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('doctor', fn ($dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($sourceType !== '') {
            $query->where('source_type', $sourceType);
        }

        if ($doctorId > 0) {
            $query->where('doctor_id', $doctorId);
        }

        if ($studentId > 0) {
            $query->where('student_id', $studentId);
        }

        if ($classId > 0) {
            $query->whereHas('student', fn (Builder $sq) => $sq->where('class_id', $classId));
        }

        if ($session !== '') {
            $query->where('session', $session);
        }

        if ($hasCbcReport === 1) {
            $query->whereHas('cbcReports');
        }

        if ($hasCbcReport === 0) {
            $query->whereDoesntHave('cbcReports');
        }

        if ($dateFrom !== '') {
            $query->whereDate(DB::raw('COALESCE(visit_date, DATE(created_at))'), '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate(DB::raw('COALESCE(visit_date, DATE(created_at))'), '<=', $dateTo);
        }

        if (! empty($year)) {
            $query->whereRaw('YEAR(COALESCE(visit_date, DATE(created_at))) = ?', [(int) $year]);
        }

        if (! empty($month)) {
            $query->whereRaw('MONTH(COALESCE(visit_date, DATE(created_at))) = ?', [(int) $month]);
        }
    }

    private function mapRow(MedicalReferral $referral): array
    {
        $problem = $referral->problem;
        if (! is_string($problem) || trim($problem) === '') {
            $problem = $this->problemTextFromReferralInput(
                (string) $referral->illness_type,
                $referral->illness_other_text
            );
        }

        return [
            'id' => $referral->id,
            'student_name' => $referral->student?->name,
            'student_db_id' => (int) $referral->student_id,
            'student_id' => $referral->student?->student_id,
            'class_name' => trim(($referral->student?->classRoom?->name ?? '').' '.($referral->student?->classRoom?->section ?? '')),
            'principal_name' => $referral->principal?->name,
            'doctor_name' => $referral->doctor?->name,
            'referred_by_name' => $referral->referredBy?->name,
            'added_by_name' => $referral->addedBy?->name,
            'source_type' => $referral->source_type ?? 'principal_referral',
            'source_label' => $referral->source_label,
            'illness_type' => $referral->illness_type,
            'illness_label' => $referral->illness_label,
            'illness_other_text' => $referral->illness_other_text,
            'problem' => $problem,
            'diagnosis' => $referral->diagnosis,
            'prescription' => $referral->prescription,
            'notes' => $referral->notes,
            'status' => $referral->status,
            'visit_date' => optional($referral->visit_date)->format('Y-m-d'),
            'session' => $referral->session,
            'cbc_reports_count' => $referral->cbcReports->count(),
            'cbc_reports' => $referral->cbcReports
                ->map(static fn ($report): array => [
                    'id' => (int) $report->id,
                    'report_date' => optional($report->report_date)->format('Y-m-d'),
                    'machine_report_no' => (string) ($report->machine_report_no ?? ''),
                    'doctor_name' => (string) ($report->doctor?->name ?? ''),
                ])
                ->values()
                ->all(),
            'referred_at' => optional($referral->referred_at ?? $referral->created_at)->format('Y-m-d H:i'),
            'created_at' => optional($referral->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function problemTextFromReferralInput(string $illnessType, ?string $illnessOtherText): string
    {
        if ($illnessType === 'other' && is_string($illnessOtherText) && trim($illnessOtherText) !== '') {
            return trim($illnessOtherText);
        }

        return match ($illnessType) {
            'fever' => 'Fever',
            'headache' => 'Headache',
            'stomach_ache' => 'Stomach ache',
            'other' => 'Other medical issue',
            default => Str::of($illnessType)->replace('_', ' ')->title()->toString(),
        };
    }

    private function resolveDoctorForReferral(int $doctorId): ?User
    {
        $query = $this->activeDoctorQuery();

        if ($doctorId > 0) {
            return $query->whereKey($doctorId)->first(['id', 'name', 'email']);
        }

        return $query->orderBy('id')->first(['id', 'name', 'email']);
    }

    private function activeDoctorQuery(): Builder
    {
        return User::query()
            ->role('Doctor')
            ->where(function (Builder $query): void {
                $query->whereNull('status')
                    ->orWhereIn('status', ['active', 'Active', 'ACTIVE', 'enabled', 'Enabled', 'ENABLED', '1', 1]);
            });
    }
}
