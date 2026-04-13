<?php

namespace App\Services;

use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TeacherAttendanceService
{
    public function markManualAttendance(
        int $teacherId,
        string $date,
        string $status,
        ?string $remarks,
        int $userId
    ): object {
        $attendanceDate = $this->normalizeDate($date);
        $normalizedStatus = $this->normalizeStatus($status);
        $normalizedRemarks = $this->nullableText($remarks);

        return DB::transaction(function () use (
            $teacherId,
            $attendanceDate,
            $normalizedStatus,
            $normalizedRemarks,
            $userId
        ): object {
            return TeacherAttendance::query()->updateOrCreate(
                [
                    'teacher_id' => $teacherId,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'status' => $normalizedStatus,
                    'remarks' => $normalizedRemarks,
                    'marked_by' => $userId,
                    'source' => TeacherAttendance::SOURCE_MANUAL,
                ]
            );
        });
    }

    public function updateManualAttendance(int $attendanceId, array $data, int $userId): object
    {
        $attendanceDate = $this->normalizeDate((string) ($data['attendance_date'] ?? ''));
        $normalizedStatus = $this->normalizeStatus((string) ($data['status'] ?? ''));
        $normalizedRemarks = $this->nullableText($data['remarks'] ?? null);
        $teacherId = (int) ($data['teacher_id'] ?? 0);

        return DB::transaction(function () use (
            $attendanceId,
            $teacherId,
            $attendanceDate,
            $normalizedStatus,
            $normalizedRemarks,
            $userId
        ): object {
            /** @var TeacherAttendance $existing */
            $existing = TeacherAttendance::query()
                ->lockForUpdate()
                ->findOrFail($attendanceId);

            $targetTeacherId = $teacherId > 0 ? $teacherId : (int) $existing->teacher_id;

            $updated = TeacherAttendance::query()->updateOrCreate(
                [
                    'teacher_id' => $targetTeacherId,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'status' => $normalizedStatus,
                    'remarks' => $normalizedRemarks,
                    'marked_by' => $userId,
                    'source' => TeacherAttendance::SOURCE_MANUAL,
                ]
            );

            if ((int) $updated->id !== (int) $existing->id) {
                $existing->delete();
            }

            return $updated;
        });
    }

    public function getTeacherAttendanceSummary(int $teacherId, string $session): array
    {
        $summary = $this->getAttendanceSummariesForTeachers([$teacherId], $session);

        return $summary[$teacherId] ?? $this->emptySummary();
    }

    public function calculateAttendancePercentage(int $teacherId, string $session): float
    {
        $summary = $this->getTeacherAttendanceSummary($teacherId, $session);

        return round((float) ($summary['attendance_percentage'] ?? 0), 2);
    }

    /**
     * @param array<int, int> $teacherIds
     * @return array<int, array<string, mixed>>
     */
    public function getAttendanceSummariesForTeachers(array $teacherIds, string $session): array
    {
        $teacherIds = collect($teacherIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($teacherIds === []) {
            return [];
        }

        [$sessionStart, $sessionEnd] = $this->sessionDateRange($session);

        $rows = TeacherAttendance::query()
            ->whereIn('teacher_id', $teacherIds)
            ->whereBetween('attendance_date', [$sessionStart->toDateString(), $sessionEnd->toDateString()])
            ->select(['teacher_id', 'attendance_date', 'status', 'source'])
            ->get();

        $grouped = $rows->groupBy('teacher_id');
        $summaries = [];

        foreach ($teacherIds as $teacherId) {
            /** @var Collection<int, TeacherAttendance> $teacherRows */
            $teacherRows = $grouped->get($teacherId, collect());
            $totalDays = $teacherRows->count();
            $presentDays = $teacherRows->where('status', TeacherAttendance::STATUS_PRESENT)->count();
            $lateDays = $teacherRows->where('status', TeacherAttendance::STATUS_LATE)->count();
            $absentDays = $teacherRows->where('status', TeacherAttendance::STATUS_ABSENT)->count();
            $leaveDays = $teacherRows->where('status', TeacherAttendance::STATUS_LEAVE)->count();
            $presentEquivalentDays = $presentDays + $lateDays;

            $summaries[$teacherId] = [
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'absent_days' => $absentDays,
                'leave_days' => $leaveDays,
                'present_equivalent_days' => $presentEquivalentDays,
                'attendance_percentage' => $totalDays > 0
                    ? round(($presentEquivalentDays * 100.0) / $totalDays, 2)
                    : null,
                'source' => 'teacher_attendance',
                'notes' => $totalDays > 0
                    ? []
                    : ['No teacher attendance entries were found for this session.'],
                'session_start' => $sessionStart->toDateString(),
                'session_end' => $sessionEnd->toDateString(),
            ];
        }

        return $summaries;
    }

    private function normalizeDate(string $date): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            throw new RuntimeException('Invalid attendance date.');
        }
    }

    private function normalizeStatus(string $status): string
    {
        $candidate = strtolower(trim($status));
        $allowed = [
            TeacherAttendance::STATUS_PRESENT,
            TeacherAttendance::STATUS_ABSENT,
            TeacherAttendance::STATUS_LEAVE,
            TeacherAttendance::STATUS_LATE,
        ];

        if (! in_array($candidate, $allowed, true)) {
            throw new RuntimeException('Invalid attendance status.');
        }

        return $candidate;
    }

    private function nullableText(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function sessionDateRange(string $session): array
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $session, $matches)) {
            throw new RuntimeException('Invalid session format.');
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];

        if ($endYear !== ($startYear + 1)) {
            throw new RuntimeException('Invalid session range.');
        }

        return [
            Carbon::create($startYear, 7, 1)->startOfDay(),
            Carbon::create($endYear, 6, 30)->endOfDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySummary(): array
    {
        return [
            'total_days' => 0,
            'present_days' => 0,
            'late_days' => 0,
            'absent_days' => 0,
            'leave_days' => 0,
            'present_equivalent_days' => 0,
            'attendance_percentage' => null,
            'source' => 'teacher_attendance',
            'notes' => ['No teacher attendance entries were found for this session.'],
            'session_start' => null,
            'session_end' => null,
        ];
    }
}
