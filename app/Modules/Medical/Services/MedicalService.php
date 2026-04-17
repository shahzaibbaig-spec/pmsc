<?php

namespace App\Modules\Medical\Services;

use App\Models\MedicalReferral;
use App\Models\Student;
use App\Models\User;
use App\Notifications\DoctorResponseSubmittedNotification;
use App\Notifications\MedicalReferralCreatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
            $referral = MedicalReferral::query()->create([
                'student_id' => (int) $data['student_id'],
                'principal_id' => $principalUserId,
                'doctor_id' => (int) $doctor->id,
                'illness_type' => $data['illness_type'],
                'illness_other_text' => $data['illness_other_text'] ?? null,
                'status' => 'pending',
                'referred_at' => now(),
            ]);

            $referral->load('student:id,name');
            $doctor->notify(new MedicalReferralCreatedNotification($referral));

            return $referral;
        });

        return $referral;
    }

    public function referralsForPrincipal(array $filters): LengthAwarePaginator
    {
        $query = MedicalReferral::query()
            ->with([
                'student:id,student_id,name,class_id',
                'student.classRoom:id,name,section',
                'principal:id,name',
                'doctor:id,name',
            ]);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 10));
    }

    public function referralsForDoctor(int $doctorUserId, array $filters): LengthAwarePaginator
    {
        $query = MedicalReferral::query()
            ->with([
                'student:id,student_id,name,class_id',
                'student.classRoom:id,name,section',
                'principal:id,name',
                'doctor:id,name',
            ])
            ->where('doctor_id', $doctorUserId);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 10));
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
                'consulted_at' => now(),
                'completed_at' => $status === 'completed' ? now() : null,
            ]);

            User::query()
                ->whereKey($doctorUserId)
                ->first()?->unreadNotifications()
                ->where('data->referral_id', $referral->id)
                ->update(['read_at' => now()]);

            return $referral->fresh(['student:id,name', 'principal:id,name', 'doctor:id,name']);
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
            $query->whereYear('created_at', (int) $filters['year'])
                ->whereMonth('created_at', (int) ($filters['month'] ?? now()->month));
        } else {
            $query->whereYear('created_at', (int) $filters['year']);
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
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = (string) ($filters['search'] ?? '');
        $status = (string) ($filters['status'] ?? '');
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('illness_type', 'like', "%{$search}%")
                    ->orWhere('illness_other_text', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search): void {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('doctor', fn ($dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if (! empty($year)) {
            $query->whereYear('created_at', (int) $year);
        }

        if (! empty($month)) {
            $query->whereMonth('created_at', (int) $month);
        }
    }

    private function mapRow(MedicalReferral $referral): array
    {
        return [
            'id' => $referral->id,
            'student_name' => $referral->student?->name,
            'student_id' => $referral->student?->student_id,
            'class_name' => trim(($referral->student?->classRoom?->name ?? '').' '.($referral->student?->classRoom?->section ?? '')),
            'principal_name' => $referral->principal?->name,
            'doctor_name' => $referral->doctor?->name,
            'illness_type' => $referral->illness_type,
            'illness_label' => $referral->illness_label,
            'illness_other_text' => $referral->illness_other_text,
            'diagnosis' => $referral->diagnosis,
            'prescription' => $referral->prescription,
            'notes' => $referral->notes,
            'status' => $referral->status,
            'referred_at' => optional($referral->referred_at ?? $referral->created_at)->format('Y-m-d H:i'),
            'created_at' => optional($referral->created_at)->format('Y-m-d H:i'),
        ];
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
