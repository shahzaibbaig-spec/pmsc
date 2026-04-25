<?php

namespace App\Services;

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

class HostelRoomAllocationService
{
    /**
     * @param array<int, int> $studentIds
     */
    public function allocateStudentsToRoomInBulk(array $studentIds, int $roomId, array $data, ?User $user = null): int
    {
        $user = $this->resolveUser($user);
        $studentIds = collect($studentIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($studentIds === []) {
            throw new RuntimeException('Please select at least one student for bulk allocation.');
        }

        $allocatedFrom = $this->resolveDate($data['allocated_from'] ?? now()->toDateString());
        $session = $this->resolveSession($data['session'] ?? null, $allocatedFrom);
        $remarks = $this->nullableTrimmedString($data['remarks'] ?? null);

        return DB::transaction(function () use ($studentIds, $roomId, $allocatedFrom, $session, $remarks, $user): int {
            $room = HostelRoom::query()
                ->lockForUpdate()
                ->findOrFail($roomId);
            $this->assertRoomBelongsToWarden($room, $user);

            if (! (bool) $room->is_active) {
                throw new RuntimeException('This room is not active for new allocations.');
            }

            $activeCount = HostelRoomAllocation::query()
                ->where('hostel_room_id', (int) $room->id)
                ->active()
                ->lockForUpdate()
                ->count();

            $availableBeds = max(0, (int) $room->capacity - $activeCount);
            if (count($studentIds) > $availableBeds) {
                throw new RuntimeException('Selected room does not have enough available beds for all selected students.');
            }

            $students = Student::query()
                ->select('id', 'date_of_birth', 'age', 'gender')
                ->whereIn('id', $studentIds)
                ->get()
                ->keyBy('id');

            if ($students->count() !== count($studentIds)) {
                throw new RuntimeException('One or more selected students could not be found.');
            }

            $existingActiveAllocations = HostelRoomAllocation::query()
                ->active()
                ->whereIn('student_id', $studentIds)
                ->lockForUpdate()
                ->pluck('student_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            if ($existingActiveAllocations !== []) {
                throw new RuntimeException('One or more selected students already have active hostel allocations.');
            }

            $now = now();
            $rows = [];
            foreach ($studentIds as $studentId) {
                $student = $students->get($studentId);
                if (! $student instanceof Student) {
                    throw new RuntimeException('One or more selected students could not be loaded.');
                }

                $this->assertStudentVisibleToWarden($student, $user);
                $this->ensureStudentMatchesHostelPolicy($student, $room);

                $rows[] = [
                    'hostel_room_id' => (int) $room->id,
                    'hostel_id' => (int) ($room->hostel_id ?? 0) ?: null,
                    'student_id' => (int) $student->id,
                    'allocated_from' => $allocatedFrom,
                    'allocated_to' => null,
                    'session' => $session,
                    'status' => HostelRoomAllocation::STATUS_ACTIVE,
                    'is_active' => true,
                    'remarks' => $remarks,
                    'allocated_by' => (int) $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            HostelRoomAllocation::query()->insert($rows);

            return count($rows);
        });
    }

    public function allocateStudentToRoom(int $studentId, int $roomId, array $data, ?User $user = null): HostelRoomAllocation
    {
        $user = $this->resolveUser($user);
        $allocatedFrom = $this->resolveDate($data['allocated_from'] ?? now()->toDateString());
        $session = $this->resolveSession($data['session'] ?? null, $allocatedFrom);
        $remarks = $this->nullableTrimmedString($data['remarks'] ?? null);

        return DB::transaction(function () use ($studentId, $roomId, $allocatedFrom, $session, $remarks, $user): HostelRoomAllocation {
            $student = Student::query()
                ->select('id', 'date_of_birth', 'age', 'gender')
                ->findOrFail($studentId);
            $this->assertStudentVisibleToWarden($student, $user);

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
            $this->assertRoomBelongsToWarden($room, $user);

            $this->ensureRoomCanReceiveStudent($room);
            $this->ensureStudentMatchesHostelPolicy($student, $room);

            return HostelRoomAllocation::query()->create([
                'hostel_room_id' => (int) $room->id,
                'hostel_id' => (int) ($room->hostel_id ?? 0) ?: null,
                'student_id' => (int) $student->id,
                'allocated_from' => $allocatedFrom,
                'allocated_to' => null,
                'session' => $session,
                'status' => HostelRoomAllocation::STATUS_ACTIVE,
                'is_active' => true,
                'remarks' => $remarks,
                'allocated_by' => (int) $user->id,
            ])->load([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number,capacity',
            ]);
        });
    }

    public function shiftStudentRoom(int $studentId, int $newRoomId, array $data, ?User $user = null): HostelRoomAllocation
    {
        $user = $this->resolveUser($user);
        $newAllocatedFrom = $this->resolveDate($data['allocated_from'] ?? now()->toDateString());
        $session = $this->resolveSession($data['session'] ?? null, $newAllocatedFrom);
        $remarks = $this->nullableTrimmedString($data['remarks'] ?? null);

        return DB::transaction(function () use ($studentId, $newRoomId, $newAllocatedFrom, $session, $remarks, $user): HostelRoomAllocation {
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

            $this->assertAllocationBelongsToWarden($activeAllocation, $user);

            $newRoom = HostelRoom::query()
                ->lockForUpdate()
                ->findOrFail($newRoomId);
            $this->assertRoomBelongsToWarden($newRoom, $user);

            $this->ensureRoomCanReceiveStudent($newRoom);

            $student = Student::query()
                ->select('id', 'date_of_birth', 'age', 'gender')
                ->findOrFail((int) $activeAllocation->student_id);
            $this->ensureStudentMatchesHostelPolicy($student, $newRoom);

            $activeAllocation->forceFill([
                'allocated_to' => $newAllocatedFrom,
                'status' => HostelRoomAllocation::STATUS_SHIFTED,
                'is_active' => false,
                'remarks' => $this->appendShiftRemark($activeAllocation->remarks, $remarks),
            ])->save();

            return HostelRoomAllocation::query()->create([
                'hostel_room_id' => (int) $newRoom->id,
                'hostel_id' => (int) ($newRoom->hostel_id ?? 0) ?: null,
                'student_id' => (int) $activeAllocation->student_id,
                'allocated_from' => $newAllocatedFrom,
                'allocated_to' => null,
                'session' => $session,
                'status' => HostelRoomAllocation::STATUS_ACTIVE,
                'is_active' => true,
                'remarks' => $remarks,
                'allocated_by' => (int) $user->id,
            ])->load([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'hostelRoom:id,room_name,floor_number,capacity',
            ]);
        });
    }

    public function removeStudentFromRoom(int $studentId, string $date, ?string $remarks, ?User $user = null): void
    {
        $user = $this->resolveUser($user);
        $allocatedTo = $this->resolveDate($date);

        DB::transaction(function () use ($studentId, $allocatedTo, $remarks, $user): void {
            $activeAllocation = HostelRoomAllocation::query()
                ->where('student_id', $studentId)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $activeAllocation instanceof HostelRoomAllocation) {
                throw new RuntimeException('No active room allocation exists for this student.');
            }

            $this->assertAllocationBelongsToWarden($activeAllocation, $user);

            $note = $this->nullableTrimmedString($remarks);
            if ($note !== null) {
                $note .= ' (closed by user #'.$user->id.')';
            } else {
                $note = 'Allocation closed by user #'.$user->id.'.';
            }

            $activeAllocation->forceFill([
                'allocated_to' => $allocatedTo,
                'status' => HostelRoomAllocation::STATUS_COMPLETED,
                'is_active' => false,
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
    public function getCreateFormOptions(?User $user = null): array
    {
        $user = $this->resolveUser($user);
        $students = $this->eligibleStudentsForAllocation($user);
        $rooms = $this->activeRoomsWithOccupancy($user);

        return [
            'students' => $students,
            'rooms' => $rooms,
        ];
    }

    /**
     * @return array{
     *     students:array<int, array{id:int,name:string,class_name:string,student_code:string}>,
     *     rooms:array<int, array{id:int,name:string,capacity:int,occupied_beds:int,available_beds:int}>
     * }
     */
    public function getBulkCreateFormOptions(?User $user = null): array
    {
        $user = $this->resolveUser($user);
        $students = $this->eligibleStudentsForAllocation($user);
        $rooms = $this->activeRoomsWithOccupancy($user);

        return [
            'students' => $students,
            'rooms' => $rooms,
        ];
    }

    /**
     * @return array<int, array{id:int,name:string,class_name:string,student_code:string}>
     */
    private function eligibleStudentsForAllocation(User $user): array
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

        if ($user->isWarden()) {
            $hostelId = (int) ($user->hostel_id ?? 0);
            if ($hostelId <= 0) {
                return [
                    'students' => [],
                    'rooms' => $this->activeRoomsWithOccupancy($user),
                ];
            }
        }

        if ($activeStudentIds !== []) {
            $studentsQuery->whereNotIn('id', $activeStudentIds);
        }

        $students = $studentsQuery
            ->limit(1500)
            ->get(['id', 'name', 'student_id', 'class_id', 'date_of_birth', 'age', 'gender'])
            ->filter(function (Student $student) use ($user): bool {
                if (! $user->isWarden()) {
                    return true;
                }

                $hostelName = trim((string) ($user->hostel?->name ?? ''));
                if ($hostelName === '') {
                    return false;
                }

                return $this->isStudentEligibleForHostelName($student, $hostelName);
            })
            ->map(fn (Student $student): array => [
                'id' => (int) $student->id,
                'name' => (string) $student->name,
                'class_name' => trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? '')),
                'student_code' => (string) ($student->student_id ?? '-'),
            ])
            ->values()
            ->all();

        return $students;
    }

    /**
     * @return array{
     *     current_allocation:HostelRoomAllocation,
     *     available_rooms:array<int, array{id:int,name:string,capacity:int,occupied_beds:int,available_beds:int}>
     * }
     */
    public function getShiftFormOptions(int $studentId, ?User $user = null): array
    {
        $user = $this->resolveUser($user);
        $currentAllocation = HostelRoomAllocation::query()
            ->forWarden($user)
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

        $availableRooms = collect($this->activeRoomsWithOccupancy($user))
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
    public function getAllocationList(array $filters = [], ?User $user = null): array
    {
        $user = $this->resolveUser($user);
        $normalized = $this->normalizeFilters($filters);
        $classIds = Student::query()
            ->forWarden($user)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $allocations = HostelRoomAllocation::query()
            ->forWarden($user)
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
                ->forWarden($user)
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
                ->when($classIds !== [], fn (Builder $query) => $query->whereIn('id', $classIds))
                ->when($classIds === [], fn (Builder $query) => $query->whereRaw('1 = 0'))
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
    public function getStudentRoomAllocation(int $studentId, ?User $user = null): ?array
    {
        $user = $this->resolveUser($user);
        $allocation = HostelRoomAllocation::query()
            ->forWarden($user)
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
    public function getRoomStudents(int $roomId, ?User $user = null): array
    {
        $user = $this->resolveUser($user);

        return HostelRoomAllocation::query()
            ->forWarden($user)
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
    private function activeRoomsWithOccupancy(?User $user = null): array
    {
        $user = $this->resolveUser($user);

        return HostelRoom::query()
            ->forWarden($user)
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

    private function resolveUser(?User $user): User
    {
        $resolved = $user ?? auth()->user();

        if (! $resolved instanceof User) {
            throw new RuntimeException('Authenticated user context is required.');
        }

        return $resolved;
    }

    private function assertRoomBelongsToWarden(HostelRoom $room, User $user): void
    {
        if (! $user->isWarden()) {
            return;
        }

        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0 || (int) ($room->hostel_id ?? 0) !== $hostelId) {
            throw new RuntimeException('You are not allowed to manage this hostel room.');
        }
    }

    private function assertAllocationBelongsToWarden(HostelRoomAllocation $allocation, User $user): void
    {
        if (! $user->isWarden()) {
            return;
        }

        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0 || (int) ($allocation->hostel_id ?? 0) !== $hostelId) {
            throw new RuntimeException('You are not allowed to access this hostel allocation.');
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

        $activeAllocation = HostelRoomAllocation::query()
            ->select('hostel_id')
            ->where('student_id', (int) $student->id)
            ->active()
            ->first();

        if (
            $activeAllocation instanceof HostelRoomAllocation
            && (int) ($activeAllocation->hostel_id ?? 0) !== $hostelId
        ) {
            throw new RuntimeException('Student is currently allocated in another hostel.');
        }
    }

    private function ensureStudentMatchesHostelPolicy(Student $student, HostelRoom $room): void
    {
        if ($room->hostel === null) {
            $room->loadMissing('hostel:id,name');
        }

        $hostelName = trim((string) ($room->hostel?->name ?? ''));
        if ($hostelName === '') {
            return;
        }

        $gender = $this->normalizeGender($student->gender ?? null);
        if ($gender === null) {
            throw new RuntimeException('Student gender is required before hostel allocation.');
        }

        $age = null;
        if ($student->date_of_birth !== null) {
            $age = Carbon::parse((string) $student->date_of_birth)->age;
        } elseif ($student->age !== null) {
            $age = (int) $student->age;
        }

        if ($age === null) {
            throw new RuntimeException('Student age or date of birth is required for hostel allocation.');
        }

        if ($hostelName === 'Fatimah House' && $gender === 'male' && $age >= 6) {
            throw new RuntimeException('Boys 6+ cannot be assigned to Fatimah House.');
        }

        if ($hostelName === 'Jinnah House') {
            if ($gender === 'female') {
                throw new RuntimeException('Girls cannot be assigned to Jinnah House.');
            }

            if ($age < 6) {
                throw new RuntimeException('Boys under 6 cannot be assigned to Jinnah House.');
            }
        }
    }

    private function resolveSession(mixed $session, string $allocatedFrom): string
    {
        $resolved = trim((string) $session);
        if ($resolved !== '') {
            return $resolved;
        }

        $date = Carbon::parse($allocatedFrom);
        $startYear = $date->month >= 7 ? $date->year : $date->year - 1;

        return $startYear.'-'.($startYear + 1);
    }

    private function normalizeGender(mixed $gender): ?string
    {
        $value = strtolower(trim((string) $gender));

        if ($value === '') {
            return null;
        }

        if (in_array($value, ['m', 'male', 'boy', 'boys'], true)) {
            return 'male';
        }

        if (in_array($value, ['f', 'female', 'girl', 'girls'], true)) {
            return 'female';
        }

        return $value;
    }

    private function isStudentEligibleForHostelName(Student $student, string $hostelName): bool
    {
        $gender = $this->normalizeGender($student->gender ?? null);
        if ($gender === null) {
            return false;
        }

        $age = null;
        if ($student->date_of_birth !== null) {
            $age = Carbon::parse((string) $student->date_of_birth)->age;
        } elseif ($student->age !== null) {
            $age = (int) $student->age;
        }

        if ($age === null) {
            return false;
        }

        if ($hostelName === 'Fatimah House') {
            return ! ($gender === 'male' && $age >= 6);
        }

        if ($hostelName === 'Jinnah House') {
            return $gender === 'male' && $age >= 6;
        }

        return true;
    }
}
