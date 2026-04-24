<?php

namespace App\Services;

use App\Models\HostelLeaveRequest;
use App\Models\HostelNightAttendance;
use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HostelNightAttendanceService
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{
     *     attendance_date:string,
     *     total_rows:int,
     *     created:int,
     *     updated:int
     * }
     */
    public function markAttendance(array $rows, string $date, ?User $user = null): array
    {
        $user = $this->resolveUser($user);
        $attendanceDate = Carbon::parse($date)->toDateString();

        return DB::transaction(function () use ($rows, $attendanceDate, $user): array {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $studentId = (int) ($row['student_id'] ?? 0);
                if ($studentId <= 0) {
                    throw new RuntimeException('Student ID is required for each attendance row.');
                }

                $student = Student::query()->findOrFail($studentId);
                $this->assertStudentVisibleToWarden($student, $user);

                $status = trim((string) ($row['status'] ?? HostelNightAttendance::STATUS_PRESENT));
                $this->ensureValidStatus($status);

                if (
                    $status === HostelNightAttendance::STATUS_ON_LEAVE
                    && ! $this->hasApprovedLeaveForDate($studentId, $attendanceDate)
                ) {
                    throw new RuntimeException('Student #'.$studentId.' does not have an approved hostel leave for '.$attendanceDate.'.');
                }

                $roomId = isset($row['hostel_room_id']) && $row['hostel_room_id'] !== ''
                    ? (int) $row['hostel_room_id']
                    : $this->activeRoomIdForStudent($studentId);

                if ($roomId !== null) {
                    $room = HostelRoom::query()->findOrFail($roomId);
                    $this->assertRoomVisibleToWarden($room, $user);
                }

                $attendance = HostelNightAttendance::query()->updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'hostel_room_id' => $roomId,
                        'status' => $status,
                        'remarks' => $this->nullableTrimmedString($row['remarks'] ?? null),
                        'marked_by' => (int) $user->id,
                    ]
                );

                if ($attendance->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            return [
                'attendance_date' => $attendanceDate,
                'total_rows' => count($rows),
                'created' => $created,
                'updated' => $updated,
            ];
        });
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     attendance_date:string,
     *     attendance_rows:LengthAwarePaginator,
     *     hostel_students:array<int, array{
     *         student_id:int,
     *         student_name:string,
     *         student_code:string,
     *         class_name:string,
     *         hostel_room_id:int,
     *         room_name:string,
     *         floor_number:int,
     *         existing_status:string,
     *         existing_remarks:?string
     *     }>,
     *     filters:array{search:?string,room_id:?int,class_id:?int,status:?string,per_page:int},
     *     rooms:array<int, array{id:int,name:string}>,
     *     classes:array<int, array{id:int,name:string}>,
     *     statuses:array<int, string>
     * }
     */
    public function getNightAttendanceByDate(string $date, array $filters = [], ?User $user = null): array
    {
        $user = $this->resolveUser($user);
        $attendanceDate = Carbon::parse($date)->toDateString();
        $normalized = $this->normalizeFilters($filters);

        $attendanceRows = HostelNightAttendance::query()
            ->forWarden($user)
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number',
                'markedBy:id,name',
            ])
            ->whereDate('attendance_date', $attendanceDate)
            ->when($normalized['search'] !== null, function (Builder $query) use ($normalized): void {
                $search = (string) $normalized['search'];
                $contains = '%'.$search.'%';
                $prefix = $search.'%';

                $query->where(function (Builder $inner) use ($contains, $prefix): void {
                    $inner->where('remarks', 'like', $contains)
                        ->orWhereHas('student', function (Builder $studentQuery) use ($contains, $prefix): void {
                            $studentQuery->where('name', 'like', $contains)
                                ->orWhere('student_id', 'like', $prefix);
                        });
                });
            })
            ->when($normalized['room_id'] !== null, fn (Builder $query) => $query->where('hostel_room_id', $normalized['room_id']))
            ->when($normalized['class_id'] !== null, function (Builder $query) use ($normalized): void {
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('class_id', $normalized['class_id']));
            })
            ->when($normalized['status'] !== null, fn (Builder $query) => $query->where('status', $normalized['status']))
            ->orderBy('hostel_room_id')
            ->orderBy('student_id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        return [
            'attendance_date' => $attendanceDate,
            'attendance_rows' => $attendanceRows,
            'hostel_students' => $this->hostelStudentsForDate($attendanceDate, $normalized, $user),
            'filters' => $normalized,
            'rooms' => $this->roomOptions($user),
            'classes' => $this->classOptions($user),
            'statuses' => [
                HostelNightAttendance::STATUS_PRESENT,
                HostelNightAttendance::STATUS_ABSENT,
                HostelNightAttendance::STATUS_ON_LEAVE,
                HostelNightAttendance::STATUS_LATE_RETURN,
            ],
        ];
    }

    /**
     * @return array<int, array{
     *     attendance_date:string,
     *     status:string,
     *     room_name:string,
     *     floor_number:int,
     *     remarks:?string,
     *     marked_by:string
     * }>
     */
    public function getStudentNightAttendanceHistory(int $studentId, ?User $user = null): array
    {
        $user = $this->resolveUser($user);

        return HostelNightAttendance::query()
            ->forWarden($user)
            ->with([
                'hostelRoom:id,room_name,floor_number',
                'markedBy:id,name',
            ])
            ->where('student_id', $studentId)
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->limit(90)
            ->get()
            ->map(function (HostelNightAttendance $row): array {
                return [
                    'attendance_date' => optional($row->attendance_date)->toDateString() ?? '-',
                    'status' => (string) $row->status,
                    'room_name' => (string) ($row->hostelRoom?->room_name ?? '-'),
                    'floor_number' => (int) ($row->hostelRoom?->floor_number ?? 0),
                    'remarks' => $row->remarks,
                    'marked_by' => (string) ($row->markedBy?->name ?? '-'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array{search:?string,room_id:?int,class_id:?int,status:?string,per_page:int} $filters
     * @return array<int, array{
     *     student_id:int,
     *     student_name:string,
     *     student_code:string,
     *     class_name:string,
     *     hostel_room_id:int,
     *     room_name:string,
     *     floor_number:int,
     *     existing_status:string,
     *     existing_remarks:?string
     * }>
     */
    private function hostelStudentsForDate(string $date, array $filters, User $user): array
    {
        $rows = HostelRoomAllocation::query()
            ->forWarden($user)
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number',
            ])
            ->active()
            ->when($filters['search'] !== null, function (Builder $query) use ($filters): void {
                $search = (string) $filters['search'];
                $contains = '%'.$search.'%';
                $prefix = $search.'%';

                $query->whereHas('student', function (Builder $studentQuery) use ($contains, $prefix): void {
                    $studentQuery->where('name', 'like', $contains)
                        ->orWhere('student_id', 'like', $prefix);
                });
            })
            ->when($filters['room_id'] !== null, fn (Builder $query) => $query->where('hostel_room_id', $filters['room_id']))
            ->when($filters['class_id'] !== null, function (Builder $query) use ($filters): void {
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('class_id', $filters['class_id']));
            })
            ->orderBy('hostel_room_id')
            ->orderBy('student_id')
            ->get();

        $existingMap = HostelNightAttendance::query()
            ->forWarden($user)
            ->whereDate('attendance_date', $date)
            ->whereIn('student_id', $rows->pluck('student_id')->values()->all())
            ->get(['student_id', 'status', 'remarks'])
            ->keyBy('student_id');

        return $rows
            ->map(function (HostelRoomAllocation $allocation) use ($existingMap): array {
                $existing = $existingMap->get($allocation->student_id);

                return [
                    'student_id' => (int) $allocation->student_id,
                    'student_name' => (string) ($allocation->student?->name ?? 'Student'),
                    'student_code' => (string) ($allocation->student?->student_id ?? '-'),
                    'class_name' => trim((string) ($allocation->student?->classRoom?->name ?? '').' '.(string) ($allocation->student?->classRoom?->section ?? '')),
                    'hostel_room_id' => (int) $allocation->hostel_room_id,
                    'room_name' => (string) ($allocation->hostelRoom?->room_name ?? 'Room'),
                    'floor_number' => (int) ($allocation->hostelRoom?->floor_number ?? 0),
                    'existing_status' => (string) ($existing?->status ?? HostelNightAttendance::STATUS_PRESENT),
                    'existing_remarks' => $existing?->remarks,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function roomOptions(User $user): array
    {
        return HostelRoom::query()
            ->forWarden($user)
            ->orderBy('floor_number')
            ->orderBy('room_name')
            ->get(['id', 'room_name', 'floor_number'])
            ->map(fn (HostelRoom $room): array => [
                'id' => (int) $room->id,
                'name' => $room->room_name.' (Floor '.$room->floor_number.')',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function classOptions(User $user): array
    {
        $classIds = Student::query()
            ->forWarden($user)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        return SchoolClass::query()
            ->when($classIds !== [], fn (Builder $query) => $query->whereIn('id', $classIds))
            ->when($classIds === [], fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $class): array => [
                'id' => (int) $class->id,
                'name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
            ])
            ->values()
            ->all();
    }

    private function activeRoomIdForStudent(int $studentId): ?int
    {
        return HostelRoomAllocation::query()
            ->where('student_id', $studentId)
            ->active()
            ->value('hostel_room_id');
    }

    private function hasApprovedLeaveForDate(int $studentId, string $date): bool
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        return HostelLeaveRequest::query()
            ->where('student_id', $studentId)
            ->where('status', HostelLeaveRequest::STATUS_APPROVED)
            ->where(function (Builder $query) use ($start, $end): void {
                $query->whereBetween('leave_from', [$start, $end])
                    ->orWhereBetween('leave_to', [$start, $end])
                    ->orWhere(function (Builder $inner) use ($start, $end): void {
                        $inner->where('leave_from', '<=', $start)
                            ->where('leave_to', '>=', $end);
                    });
            })
            ->exists();
    }

    private function ensureValidStatus(string $status): void
    {
        $allowed = [
            HostelNightAttendance::STATUS_PRESENT,
            HostelNightAttendance::STATUS_ABSENT,
            HostelNightAttendance::STATUS_ON_LEAVE,
            HostelNightAttendance::STATUS_LATE_RETURN,
        ];

        if (! in_array($status, $allowed, true)) {
            throw new RuntimeException('Invalid attendance status supplied.');
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{search:?string,room_id:?int,class_id:?int,status:?string,per_page:int}
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 20;
        $perPage = max(10, min(100, $perPage));

        return [
            'search' => $this->nullableTrimmedString($filters['search'] ?? null),
            'room_id' => isset($filters['room_id']) && $filters['room_id'] !== '' ? (int) $filters['room_id'] : null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
            'status' => $this->nullableTrimmedString($filters['status'] ?? null),
            'per_page' => $perPage,
        ];
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function resolveUser(?User $user): User
    {
        $resolved = $user ?? auth()->user();

        if (! $resolved instanceof User) {
            throw new RuntimeException('Authenticated user context is required.');
        }

        return $resolved;
    }

    private function assertRoomVisibleToWarden(HostelRoom $room, User $user): void
    {
        if (! $user->isWarden()) {
            return;
        }

        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0 || (int) ($room->hostel_id ?? 0) !== $hostelId) {
            throw new RuntimeException('You are not allowed to use this hostel room.');
        }
    }

    private function assertStudentVisibleToWarden(Student $student, User $user): void
    {
        if (! $user->isWarden()) {
            return;
        }

        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0) {
            throw new RuntimeException('Your warden account is not assigned to a hostel.');
        }

        $hasVisibleAllocation = HostelRoomAllocation::query()
            ->where('student_id', (int) $student->id)
            ->where('hostel_id', $hostelId)
            ->active()
            ->exists();

        if (! $hasVisibleAllocation) {
            throw new RuntimeException('Student is not allocated to your hostel.');
        }
    }
}
