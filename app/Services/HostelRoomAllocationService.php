<?php

namespace App\Services;

use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HostelRoomAllocationService
{
    public function allocateStudentToRoom(int $studentId, int $roomId, array $data, int $userId): HostelRoomAllocation
    {
        $allocatedFrom = $this->resolveDate($data['allocated_from'] ?? now()->toDateString());
        $remarks = $this->nullableTrimmedString($data['remarks'] ?? null);

        return DB::transaction(function () use ($studentId, $roomId, $allocatedFrom, $remarks, $userId): HostelRoomAllocation {
            $student = Student::query()
                ->select('id')
                ->findOrFail($studentId);

            $activeAllocation = HostelRoomAllocation::query()
                ->where('student_id', (int) $student->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($activeAllocation instanceof HostelRoomAllocation) {
                throw new RuntimeException('This student already has an active hostel room allocation.');
            }

            $room = HostelRoom::query()
                ->lockForUpdate()
                ->findOrFail($roomId);

            $this->ensureRoomCanReceiveStudent($room);

            return HostelRoomAllocation::query()->create([
                'hostel_room_id' => (int) $room->id,
                'student_id' => (int) $student->id,
                'allocated_from' => $allocatedFrom,
                'allocated_to' => null,
                'status' => HostelRoomAllocation::STATUS_ACTIVE,
                'remarks' => $remarks,
                'allocated_by' => $userId,
            ])->load([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number,capacity',
            ]);
        });
    }

    public function shiftStudentRoom(int $studentId, int $newRoomId, array $data, int $userId): HostelRoomAllocation
    {
        $newAllocatedFrom = $this->resolveDate($data['allocated_from'] ?? now()->toDateString());
        $remarks = $this->nullableTrimmedString($data['remarks'] ?? null);

        return DB::transaction(function () use ($studentId, $newRoomId, $newAllocatedFrom, $remarks, $userId): HostelRoomAllocation {
            $activeAllocation = HostelRoomAllocation::query()
                ->where('student_id', $studentId)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $activeAllocation instanceof HostelRoomAllocation) {
                throw new RuntimeException('No active room allocation exists for this student.');
            }

            if ((int) $activeAllocation->hostel_room_id === $newRoomId) {
                throw new RuntimeException('Student is already allocated to this room.');
            }

            $newRoom = HostelRoom::query()
                ->lockForUpdate()
                ->findOrFail($newRoomId);

            $this->ensureRoomCanReceiveStudent($newRoom);

            $activeAllocation->forceFill([
                'allocated_to' => $newAllocatedFrom,
                'status' => HostelRoomAllocation::STATUS_SHIFTED,
                'remarks' => $this->appendShiftRemark($activeAllocation->remarks, $remarks),
            ])->save();

            return HostelRoomAllocation::query()->create([
                'hostel_room_id' => (int) $newRoom->id,
                'student_id' => (int) $activeAllocation->student_id,
                'allocated_from' => $newAllocatedFrom,
                'allocated_to' => null,
                'status' => HostelRoomAllocation::STATUS_ACTIVE,
                'remarks' => $remarks,
                'allocated_by' => $userId,
            ])->load([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number,capacity',
            ]);
        });
    }

    public function removeStudentFromRoom(int $studentId, string $date, ?string $remarks, int $userId): void
    {
        $allocatedTo = $this->resolveDate($date);

        DB::transaction(function () use ($studentId, $allocatedTo, $remarks, $userId): void {
            $activeAllocation = HostelRoomAllocation::query()
                ->where('student_id', $studentId)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $activeAllocation instanceof HostelRoomAllocation) {
                throw new RuntimeException('No active room allocation exists for this student.');
            }

            $note = $this->nullableTrimmedString($remarks);
            if ($note !== null) {
                $note .= ' (closed by user #'.$userId.')';
            } else {
                $note = 'Allocation closed by user #'.$userId.'.';
            }

            $activeAllocation->forceFill([
                'allocated_to' => $allocatedTo,
                'status' => HostelRoomAllocation::STATUS_COMPLETED,
                'remarks' => $note,
            ])->save();
        });
    }

    /**
     * @return array{
     *     students:array<int, array{id:int,name:string,class_name:string,student_code:string}>,
     *     rooms:array<int, array{id:int,name:string,capacity:int,occupied_beds:int,available_beds:int}>
     * }
     */
    public function getCreateFormOptions(): array
    {
        $activeStudentIds = HostelRoomAllocation::query()
            ->active()
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $studentsQuery = Student::query()
            ->with('classRoom:id,name,section')
            ->orderBy('name')
            ->orderBy('student_id');

        if ($activeStudentIds !== []) {
            $studentsQuery->whereNotIn('id', $activeStudentIds);
        }

        $students = $studentsQuery
            ->limit(1500)
            ->get(['id', 'name', 'student_id', 'class_id'])
            ->map(fn (Student $student): array => [
                'id' => (int) $student->id,
                'name' => (string) $student->name,
                'class_name' => trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? '')),
                'student_code' => (string) ($student->student_id ?? '-'),
            ])
            ->values()
            ->all();

        $rooms = $this->activeRoomsWithOccupancy();

        return [
            'students' => $students,
            'rooms' => $rooms,
        ];
    }

    /**
     * @return array{
     *     current_allocation:HostelRoomAllocation,
     *     available_rooms:array<int, array{id:int,name:string,capacity:int,occupied_beds:int,available_beds:int}>
     * }
     */
    public function getShiftFormOptions(int $studentId): array
    {
        $currentAllocation = HostelRoomAllocation::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number,capacity',
            ])
            ->where('student_id', $studentId)
            ->active()
            ->first();

        if (! $currentAllocation instanceof HostelRoomAllocation) {
            throw new RuntimeException('No active room allocation exists for this student.');
        }

        $availableRooms = collect($this->activeRoomsWithOccupancy())
            ->reject(fn (array $room): bool => (int) $room['id'] === (int) $currentAllocation->hostel_room_id)
            ->values()
            ->all();

        return [
            'current_allocation' => $currentAllocation,
            'available_rooms' => $availableRooms,
        ];
    }

    /**
     * @return array{
     *     allocations:LengthAwarePaginator,
     *     filters:array{search:?string,room_id:?int,class_id:?int,status:?string,date:?string,per_page:int},
     *     rooms:array<int, array{id:int,name:string}>,
     *     classes:array<int, array{id:int,name:string}>
     * }
     */
    public function getAllocationList(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        $allocations = HostelRoomAllocation::query()
            ->with([
                'student:id,name,student_id,class_id,status',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number,capacity',
            ])
            ->when($normalized['search'] !== null, function (Builder $query) use ($normalized): void {
                $search = (string) $normalized['search'];
                $contains = '%'.$search.'%';
                $prefix = $search.'%';

                $query->where(function (Builder $innerQuery) use ($contains, $prefix): void {
                    $innerQuery->where('remarks', 'like', $contains)
                        ->orWhereHas('student', function (Builder $studentQuery) use ($contains, $prefix): void {
                            $studentQuery->where('name', 'like', $contains)
                                ->orWhere('student_id', 'like', $prefix);
                        })
                        ->orWhereHas('hostelRoom', fn (Builder $roomQuery) => $roomQuery->where('room_name', 'like', $contains));
                });
            })
            ->when($normalized['room_id'] !== null, fn (Builder $query) => $query->where('hostel_room_id', $normalized['room_id']))
            ->when($normalized['class_id'] !== null, function (Builder $query) use ($normalized): void {
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('class_id', $normalized['class_id']));
            })
            ->when($normalized['status'] !== null, fn (Builder $query) => $query->where('status', $normalized['status']))
            ->when($normalized['date'] !== null, function (Builder $query) use ($normalized): void {
                $query->whereDate('allocated_from', '<=', $normalized['date'])
                    ->where(function (Builder $inner) use ($normalized): void {
                        $inner->whereNull('allocated_to')
                            ->orWhereDate('allocated_to', '>=', $normalized['date']);
                    });
            })
            ->orderByDesc('id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        return [
            'allocations' => $allocations,
            'filters' => $normalized,
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
            'classes' => SchoolClass::query()
                ->orderBy('name')
                ->orderBy('section')
                ->get(['id', 'name', 'section'])
                ->map(fn (SchoolClass $class): array => [
                    'id' => (int) $class->id,
                    'name' => trim($class->name.' '.(string) ($class->section ?? '')),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     student_id:int,
     *     student_name:string,
     *     student_code:string,
     *     class_name:string,
     *     room_id:int,
     *     room_name:string,
     *     floor_number:int,
     *     allocated_from:?string,
     *     remarks:?string
     * }|null
     */
    public function getStudentRoomAllocation(int $studentId): ?array
    {
        $allocation = HostelRoomAllocation::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number',
            ])
            ->where('student_id', $studentId)
            ->active()
            ->first();

        if (! $allocation instanceof HostelRoomAllocation) {
            return null;
        }

        return [
            'student_id' => (int) ($allocation->student?->id ?? $studentId),
            'student_name' => (string) ($allocation->student?->name ?? 'Student'),
            'student_code' => (string) ($allocation->student?->student_id ?? '-'),
            'class_name' => trim((string) ($allocation->student?->classRoom?->name ?? '').' '.(string) ($allocation->student?->classRoom?->section ?? '')),
            'room_id' => (int) $allocation->hostel_room_id,
            'room_name' => (string) ($allocation->hostelRoom?->room_name ?? 'Room'),
            'floor_number' => (int) ($allocation->hostelRoom?->floor_number ?? 0),
            'allocated_from' => optional($allocation->allocated_from)->toDateString(),
            'remarks' => $allocation->remarks,
        ];
    }

    /**
     * @return array<int, array{
     *     allocation_id:int,
     *     student_id:int,
     *     student_name:string,
     *     student_code:string,
     *     class_name:string,
     *     room_name:string,
     *     floor_number:int,
     *     allocated_from:?string,
     *     status:string
     * }>
     */
    public function getRoomStudents(int $roomId): array
    {
        return HostelRoomAllocation::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number',
            ])
            ->where('hostel_room_id', $roomId)
            ->active()
            ->orderBy('allocated_from')
            ->get()
            ->map(function (HostelRoomAllocation $allocation): array {
                return [
                    'allocation_id' => (int) $allocation->id,
                    'student_id' => (int) $allocation->student_id,
                    'student_name' => (string) ($allocation->student?->name ?? 'Student'),
                    'student_code' => (string) ($allocation->student?->student_id ?? '-'),
                    'class_name' => trim((string) ($allocation->student?->classRoom?->name ?? '').' '.(string) ($allocation->student?->classRoom?->section ?? '')),
                    'room_name' => (string) ($allocation->hostelRoom?->room_name ?? 'Room'),
                    'floor_number' => (int) ($allocation->hostelRoom?->floor_number ?? 0),
                    'allocated_from' => optional($allocation->allocated_from)->toDateString(),
                    'status' => (string) $allocation->status,
                ];
            })
            ->values()
            ->all();
    }

    private function ensureRoomCanReceiveStudent(HostelRoom $room): void
    {
        if (! (bool) $room->is_active) {
            throw new RuntimeException('This room is not active for new allocations.');
        }

        $activeCount = HostelRoomAllocation::query()
            ->where('hostel_room_id', (int) $room->id)
            ->active()
            ->lockForUpdate()
            ->count();

        if ($activeCount >= (int) $room->capacity) {
            throw new RuntimeException('Selected room has no available beds.');
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{search:?string,room_id:?int,class_id:?int,status:?string,date:?string,per_page:int}
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(10, min($perPage, 100));

        return [
            'search' => $this->nullableTrimmedString($filters['search'] ?? null),
            'room_id' => isset($filters['room_id']) && $filters['room_id'] !== '' ? (int) $filters['room_id'] : null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
            'status' => $this->nullableTrimmedString($filters['status'] ?? HostelRoomAllocation::STATUS_ACTIVE),
            'date' => $this->nullableTrimmedString($filters['date'] ?? null),
            'per_page' => $perPage,
        ];
    }

    private function resolveDate(mixed $date): string
    {
        return Carbon::parse((string) $date)->toDateString();
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function appendShiftRemark(?string $existingRemarks, ?string $shiftRemarks): ?string
    {
        $existing = trim((string) $existingRemarks);
        $incoming = trim((string) $shiftRemarks);

        if ($incoming === '') {
            return $existing !== '' ? $existing : null;
        }

        if ($existing === '') {
            return 'Shifted out note: '.$incoming;
        }

        return $existing.' | Shifted out note: '.$incoming;
    }

    /**
     * @return array<int, array{id:int,name:string,capacity:int,occupied_beds:int,available_beds:int}>
     */
    private function activeRoomsWithOccupancy(): array
    {
        return HostelRoom::query()
            ->withCount([
                'activeAllocations as occupied_beds' => fn (Builder $query) => $query->where('status', HostelRoomAllocation::STATUS_ACTIVE),
            ])
            ->where('is_active', true)
            ->orderBy('floor_number')
            ->orderBy('room_name')
            ->get(['id', 'room_name', 'floor_number', 'capacity'])
            ->map(function (HostelRoom $room): array {
                $occupiedBeds = (int) ($room->occupied_beds ?? 0);
                $capacity = (int) $room->capacity;

                return [
                    'id' => (int) $room->id,
                    'name' => $room->room_name.' (Floor '.$room->floor_number.')',
                    'capacity' => $capacity,
                    'occupied_beds' => $occupiedBeds,
                    'available_beds' => max(0, $capacity - $occupiedBeds),
                ];
            })
            ->values()
            ->all();
    }
}
