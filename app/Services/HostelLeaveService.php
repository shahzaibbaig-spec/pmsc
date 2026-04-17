<?php

namespace App\Services;

use App\Models\HostelLeaveRequest;
use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HostelLeaveService
{
    public function createLeaveRequest(array $data, int $userId): HostelLeaveRequest
    {
        $studentId = (int) ($data['student_id'] ?? 0);
        $leaveFrom = Carbon::parse((string) ($data['leave_from'] ?? now()))->toDateTimeString();
        $leaveTo = Carbon::parse((string) ($data['leave_to'] ?? now()))->toDateTimeString();

        if (Carbon::parse($leaveTo)->lt(Carbon::parse($leaveFrom))) {
            throw new RuntimeException('Leave end date/time must be after or equal to leave start date/time.');
        }

        $remarks = $this->nullableTrimmedString($data['remarks'] ?? null);
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            throw new RuntimeException('Leave reason is required.');
        }

        return DB::transaction(function () use ($studentId, $leaveFrom, $leaveTo, $reason, $remarks, $userId, $data): HostelLeaveRequest {
            $student = Student::query()->findOrFail($studentId);

            $roomId = isset($data['hostel_room_id']) && $data['hostel_room_id'] !== ''
                ? (int) $data['hostel_room_id']
                : $this->activeRoomIdForStudent((int) $student->id);

            if ($roomId !== null) {
                HostelRoom::query()->findOrFail($roomId);
            }

            return HostelLeaveRequest::query()->create([
                'student_id' => (int) $student->id,
                'hostel_room_id' => $roomId,
                'leave_from' => $leaveFrom,
                'leave_to' => $leaveTo,
                'reason' => $reason,
                'status' => HostelLeaveRequest::STATUS_PENDING,
                'requested_by' => $userId,
                'remarks' => $remarks,
            ])->load([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number',
            ]);
        });
    }

    public function approveLeave(int $leaveId, int $userId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($leaveId, $userId, $remarks): void {
            $leave = HostelLeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveId);

            if ($leave->status !== HostelLeaveRequest::STATUS_PENDING) {
                throw new RuntimeException('Only pending leave requests can be approved.');
            }

            $leave->forceFill([
                'status' => HostelLeaveRequest::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'remarks' => $this->mergeRemarks($leave->remarks, $remarks),
            ])->save();
        });
    }

    public function rejectLeave(int $leaveId, int $userId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($leaveId, $userId, $remarks): void {
            $leave = HostelLeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveId);

            if ($leave->status !== HostelLeaveRequest::STATUS_PENDING) {
                throw new RuntimeException('Only pending leave requests can be rejected.');
            }

            $leave->forceFill([
                'status' => HostelLeaveRequest::STATUS_REJECTED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'remarks' => $this->mergeRemarks($leave->remarks, $remarks),
            ])->save();
        });
    }

    public function markReturned(int $leaveId, int $userId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($leaveId, $userId, $remarks): void {
            $leave = HostelLeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($leaveId);

            if ($leave->status !== HostelLeaveRequest::STATUS_APPROVED) {
                throw new RuntimeException('Only approved leave requests can be marked as returned.');
            }

            $leave->forceFill([
                'status' => HostelLeaveRequest::STATUS_RETURNED,
                'returned_at' => now(),
                'approved_by' => $leave->approved_by ?: $userId,
                'remarks' => $this->mergeRemarks($leave->remarks, $remarks),
            ])->save();
        });
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     leaves:LengthAwarePaginator,
     *     filters:array{search:?string,student_id:?int,class_id:?int,hostel_room_id:?int,status:?string,date_from:?string,date_to:?string,per_page:int},
     *     students:array<int, array{id:int,name:string}>,
     *     classes:array<int, array{id:int,name:string}>,
     *     rooms:array<int, array{id:int,name:string}>,
     *     statuses:array<int, string>
     * }
     */
    public function getLeaveSummary(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        $leaves = HostelLeaveRequest::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number',
                'requestedBy:id,name',
                'approvedBy:id,name',
            ])
            ->when($normalized['search'] !== null, function (Builder $query) use ($normalized): void {
                $search = (string) $normalized['search'];
                $contains = '%'.$search.'%';
                $prefix = $search.'%';

                $query->where(function (Builder $inner) use ($contains, $prefix): void {
                    $inner->where('reason', 'like', $contains)
                        ->orWhere('remarks', 'like', $contains)
                        ->orWhereHas('student', function (Builder $studentQuery) use ($contains, $prefix): void {
                            $studentQuery->where('name', 'like', $contains)
                                ->orWhere('student_id', 'like', $prefix);
                        });
                });
            })
            ->when($normalized['student_id'] !== null, fn (Builder $query) => $query->where('student_id', $normalized['student_id']))
            ->when($normalized['class_id'] !== null, function (Builder $query) use ($normalized): void {
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('class_id', $normalized['class_id']));
            })
            ->when($normalized['hostel_room_id'] !== null, fn (Builder $query) => $query->where('hostel_room_id', $normalized['hostel_room_id']))
            ->when($normalized['status'] !== null, fn (Builder $query) => $query->where('status', $normalized['status']))
            ->when($normalized['date_from'] !== null, fn (Builder $query) => $query->where('leave_from', '>=', Carbon::parse($normalized['date_from'])->startOfDay()))
            ->when($normalized['date_to'] !== null, fn (Builder $query) => $query->where('leave_to', '<=', Carbon::parse($normalized['date_to'])->endOfDay()))
            ->orderByDesc('leave_from')
            ->orderByDesc('id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        return [
            'leaves' => $leaves,
            'filters' => $normalized,
            'students' => Student::query()
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'student_id'])
                ->map(fn (Student $student): array => [
                    'id' => (int) $student->id,
                    'name' => trim((string) $student->name.' ('.(string) $student->student_id.')'),
                ])
                ->values()
                ->all(),
            'classes' => SchoolClass::query()
                ->orderBy('name')
                ->orderBy('section')
                ->get(['id', 'name', 'section'])
                ->map(fn (SchoolClass $class): array => [
                    'id' => (int) $class->id,
                    'name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
                ])
                ->values()
                ->all(),
            'rooms' => HostelRoom::query()
                ->orderBy('floor_number')
                ->orderBy('room_name')
                ->get(['id', 'room_name', 'floor_number'])
                ->map(fn (HostelRoom $room): array => [
                    'id' => (int) $room->id,
                    'name' => $room->room_name.' (Floor '.$room->floor_number.')',
                ])
                ->values()
                ->all(),
            'statuses' => [
                HostelLeaveRequest::STATUS_PENDING,
                HostelLeaveRequest::STATUS_APPROVED,
                HostelLeaveRequest::STATUS_REJECTED,
                HostelLeaveRequest::STATUS_RETURNED,
            ],
        ];
    }

    private function activeRoomIdForStudent(int $studentId): ?int
    {
        return HostelRoomAllocation::query()
            ->where('student_id', $studentId)
            ->active()
            ->value('hostel_room_id');
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{search:?string,student_id:?int,class_id:?int,hostel_room_id:?int,status:?string,date_from:?string,date_to:?string,per_page:int}
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(10, min(100, $perPage));

        return [
            'search' => $this->nullableTrimmedString($filters['search'] ?? null),
            'student_id' => isset($filters['student_id']) && $filters['student_id'] !== '' ? (int) $filters['student_id'] : null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
            'hostel_room_id' => isset($filters['hostel_room_id']) && $filters['hostel_room_id'] !== '' ? (int) $filters['hostel_room_id'] : null,
            'status' => $this->nullableTrimmedString($filters['status'] ?? null),
            'date_from' => $this->nullableTrimmedString($filters['date_from'] ?? null),
            'date_to' => $this->nullableTrimmedString($filters['date_to'] ?? null),
            'per_page' => $perPage,
        ];
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function mergeRemarks(?string $existing, ?string $incoming): ?string
    {
        $existingValue = trim((string) $existing);
        $incomingValue = trim((string) $incoming);

        if ($incomingValue === '') {
            return $existingValue !== '' ? $existingValue : null;
        }

        if ($existingValue === '') {
            return $incomingValue;
        }

        return $existingValue.' | '.$incomingValue;
    }
}

