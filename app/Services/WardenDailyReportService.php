<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Models\WardenAttendance;
use App\Models\WardenDailyReport;
use App\Models\WardenDisciplineLog;
use App\Models\WardenHealthLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WardenDailyReportService
{
    public function createOrGetReport(int $hostelId, string $date, int $userId): WardenDailyReport
    {
        $reportDate = Carbon::parse($date)->toDateString();

        return DB::transaction(function () use ($hostelId, $reportDate, $userId): WardenDailyReport {
            $existing = WardenDailyReport::query()
                ->where('hostel_id', $hostelId)
                ->whereDate('report_date', $reportDate)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof WardenDailyReport) {
                return $existing;
            }

            return WardenDailyReport::query()->create([
                'hostel_id' => $hostelId,
                'report_date' => $reportDate,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * @return EloquentCollection<int, Student>
     */
    public function getHostelStudents(User $user): EloquentCollection
    {
        return Student::query()
            ->forWarden($user)
            ->with(['classRoom:id,name,section', 'hostelAllocation:id,student_id,hostel_room_id,hostel_id'])
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'name', 'student_id', 'class_id']);
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function saveAttendance(int $reportId, array $data, User $user): void
    {
        $report = $this->getReportForWarden($reportId, $user);
        $rows = collect($data)
            ->filter(fn ($row): bool => is_array($row) && ! empty($row['student_id']))
            ->values();

        if ($rows->isEmpty()) {
            return;
        }

        $studentIds = $rows->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->assertStudentsBelongToWardenHostel($studentIds, $user);

        $payload = $rows
            ->map(function (array $row) use ($report): array {
                return [
                    'report_id' => (int) $report->id,
                    'student_id' => (int) $row['student_id'],
                    'status' => (string) $row['status'],
                    'remarks' => $this->nullableString($row['remarks'] ?? null),
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            })
            ->all();

        DB::transaction(function () use ($payload): void {
            WardenAttendance::query()->upsert(
                $payload,
                ['report_id', 'student_id'],
                ['status', 'remarks', 'updated_at']
            );
        });
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    public function saveDiscipline(int $reportId, array $entries, User $user): void
    {
        $report = $this->getReportForWarden($reportId, $user);
        $rows = collect($entries)
            ->filter(function ($row): bool {
                return is_array($row)
                    && ! empty($row['student_id'])
                    && trim((string) ($row['issue_type'] ?? '')) !== ''
                    && trim((string) ($row['description'] ?? '')) !== '';
            })
            ->values();

        if ($rows->isEmpty()) {
            return;
        }

        $studentIds = $rows->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->assertStudentsBelongToWardenHostel($studentIds, $user);

        $now = now();
        $payload = $rows
            ->map(function (array $row) use ($report, $now): array {
                return [
                    'report_id' => (int) $report->id,
                    'student_id' => (int) $row['student_id'],
                    'issue_type' => trim((string) $row['issue_type']),
                    'severity' => (string) $row['severity'],
                    'description' => trim((string) $row['description']),
                    'action_taken' => $this->nullableString($row['action_taken'] ?? null),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        DB::transaction(function () use ($payload): void {
            WardenDisciplineLog::query()->insert($payload);
        });
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    public function saveHealth(int $reportId, array $entries, User $user): void
    {
        $report = $this->getReportForWarden($reportId, $user);
        $rows = collect($entries)
            ->filter(function ($row): bool {
                return is_array($row)
                    && ! empty($row['student_id'])
                    && trim((string) ($row['condition'] ?? '')) !== '';
            })
            ->values();

        if ($rows->isEmpty()) {
            return;
        }

        $studentIds = $rows->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->assertStudentsBelongToWardenHostel($studentIds, $user);

        $now = now();
        $payload = $rows
            ->map(function (array $row) use ($report, $now): array {
                return [
                    'report_id' => (int) $report->id,
                    'student_id' => (int) $row['student_id'],
                    'condition' => trim((string) $row['condition']),
                    'temperature' => isset($row['temperature']) && $row['temperature'] !== ''
                        ? (float) $row['temperature']
                        : null,
                    'medication' => $this->nullableString($row['medication'] ?? null),
                    'doctor_visit' => (bool) ($row['doctor_visit'] ?? false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        DB::transaction(function () use ($payload): void {
            WardenHealthLog::query()->insert($payload);
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveDailyReport(User $user, array $data): WardenDailyReport
    {
        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0) {
            throw new RuntimeException('Your account is not assigned to a hostel.');
        }

        return DB::transaction(function () use ($user, $hostelId, $data): WardenDailyReport {
            $report = $this->createOrGetReport(
                $hostelId,
                (string) ($data['report_date'] ?? now()->toDateString()),
                (int) $user->id
            );

            $notes = $this->nullableString($data['notes'] ?? null);
            if ($notes !== null) {
                $report->forceFill(['notes' => $notes])->save();
            }

            $this->saveAttendance((int) $report->id, (array) ($data['attendance'] ?? []), $user);
            $this->saveDiscipline((int) $report->id, (array) ($data['discipline'] ?? []), $user);
            $this->saveHealth((int) $report->id, (array) ($data['health'] ?? []), $user);

            return $report->refresh();
        });
    }

    public function getReportForWarden(int $reportId, User $user): WardenDailyReport
    {
        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0) {
            throw new RuntimeException('Your account is not assigned to a hostel.');
        }

        $report = WardenDailyReport::query()
            ->whereKey($reportId)
            ->where('hostel_id', $hostelId)
            ->first();

        if (! $report instanceof WardenDailyReport) {
            throw new RuntimeException('Daily report not found for your hostel.');
        }

        return $report;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{reports:LengthAwarePaginator,date:?string}
     */
    public function getReportList(User $user, array $filters = []): array
    {
        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0) {
            throw new RuntimeException('Your account is not assigned to a hostel.');
        }

        $date = trim((string) ($filters['date'] ?? '')) ?: null;
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(10, min(100, $perPage));

        $reports = WardenDailyReport::query()
            ->with(['createdBy:id,name'])
            ->where('hostel_id', $hostelId)
            ->when($date !== null, fn ($query) => $query->whereDate('report_date', $date))
            ->withCount([
                'attendance as present_count' => fn ($query) => $query->where('status', 'present'),
                'attendance as absent_count' => fn ($query) => $query->where('status', 'absent'),
                'disciplineLogs as discipline_count',
                'healthLogs as health_count',
            ])
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'reports' => $reports,
            'date' => $date,
        ];
    }

    private function assertStudentsBelongToWardenHostel(array $studentIds, User $user): void
    {
        if ($studentIds === []) {
            return;
        }

        $allowedStudentIds = Student::query()
            ->forWarden($user)
            ->whereIn('id', $studentIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if (count($allowedStudentIds) !== count(array_unique($studentIds))) {
            throw new RuntimeException('Unauthorized student access detected for this hostel.');
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}

