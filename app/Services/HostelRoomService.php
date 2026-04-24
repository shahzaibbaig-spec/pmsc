<?php

namespace App\Services;

use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HostelRoomService
{
    public function createRoom(array $data, User $user): HostelRoom
    {
        $payload = $this->normalizeRoomPayload($data, $user);

        return DB::transaction(function () use ($payload, $user): HostelRoom {
            $exists = HostelRoom::query()
                ->when(isset($payload['hostel_id']) && $payload['hostel_id'] !== null, fn (Builder $query) => $query->where('hostel_id', (int) $payload['hostel_id']))
                ->where('room_name', $payload['room_name'])
                ->where('floor_number', $payload['floor_number'])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new RuntimeException('A room with the same name and floor already exists.');
            }

            return HostelRoom::query()->create([
                ...$payload,
                'created_by' => (int) $user->id,
            ]);
        });
    }

    public function updateRoom(HostelRoom $room, array $data, User $user): HostelRoom
    {
        $this->assertRoomAccessible($room, $user);
        $payload = $this->normalizeRoomPayload($data, $user, $room);

        return DB::transaction(function () use ($room, $payload, $user): HostelRoom {
            $exists = HostelRoom::query()
                ->when(isset($payload['hostel_id']) && $payload['hostel_id'] !== null, fn (Builder $query) => $query->where('hostel_id', (int) $payload['hostel_id']))
                ->where('room_name', $payload['room_name'])
                ->where('floor_number', $payload['floor_number'])
                ->whereKeyNot($room->id)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new RuntimeException('A room with the same name and floor already exists.');
            }

            $room->forceFill([
                ...$payload,
                'created_by' => $room->created_by ?: (int) $user->id,
            ])->save();

            return $room->refresh();
        });
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     rooms:LengthAwarePaginator,
     *     filters:array{search:?string,floor_number:?int,is_active:?bool,gender:?string,per_page:int},
     *     floor_options:array<int, int>,
     *     gender_options:array<int, string>
     * }
     */
    public function getRoomList(array $filters = [], ?User $user = null): array
    {
        $user = $this->resolveUser($user);
        $normalized = $this->normalizeFilters($filters);

        $rooms = HostelRoom::query()
            ->forWarden($user)
            ->withCount([
                'activeAllocations as occupied_beds' => fn (Builder $query) => $query->where('status', HostelRoomAllocation::STATUS_ACTIVE),
            ])
            ->when($normalized['search'] !== null, function (Builder $query) use ($normalized): void {
                $search = (string) $normalized['search'];

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('room_name', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%');
                });
            })
            ->when($normalized['floor_number'] !== null, fn (Builder $query) => $query->where('floor_number', $normalized['floor_number']))
            ->when($normalized['is_active'] !== null, fn (Builder $query) => $query->where('is_active', $normalized['is_active']))
            ->when($normalized['gender'] !== null, fn (Builder $query) => $query->where('gender', $normalized['gender']))
            ->orderBy('floor_number')
            ->orderBy('room_name')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        return [
            'rooms' => $rooms,
            'filters' => $normalized,
            'floor_options' => HostelRoom::query()
                ->forWarden($user)
                ->select('floor_number')
                ->distinct()
                ->orderBy('floor_number')
                ->pluck('floor_number')
                ->map(fn ($floor): int => (int) $floor)
                ->values()
                ->all(),
            'gender_options' => HostelRoom::query()
                ->forWarden($user)
                ->select('gender')
                ->whereNotNull('gender')
                ->where('gender', '!=', '')
                ->distinct()
                ->orderBy('gender')
                ->pluck('gender')
                ->map(fn ($gender): string => (string) $gender)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     room:HostelRoom,
     *     occupied_beds:int,
     *     available_beds:int,
     *     occupancy_percentage:float,
     *     current_students:array<int, array{
     *         student_id:int,
     *         student_name:string,
     *         student_code:string,
     *         class_name:string,
     *         allocated_from:?string
     *     }>
     * }
     */
    public function getRoomOccupancySummary(int $roomId, ?User $user = null): array
    {
        $user = $this->resolveUser($user);
        $room = HostelRoom::query()
            ->forWarden($user)
            ->with([
                'activeAllocations.student:id,name,student_id,class_id',
                'activeAllocations.student.classRoom:id,name,section',
            ])
            ->findOrFail($roomId);

        $occupiedBeds = (int) $room->activeAllocations->count();
        $capacity = max(1, (int) $room->capacity);
        $availableBeds = max(0, $capacity - $occupiedBeds);
        $occupancyPercentage = round(($occupiedBeds / $capacity) * 100, 2);

        $currentStudents = $room->activeAllocations
            ->sortBy('allocated_from')
            ->values()
            ->map(function (HostelRoomAllocation $allocation): array {
                return [
                    'student_id' => (int) ($allocation->student?->id ?? 0),
                    'student_name' => (string) ($allocation->student?->name ?? 'Student'),
                    'student_code' => (string) ($allocation->student?->student_id ?? '-'),
                    'class_name' => trim((string) ($allocation->student?->classRoom?->name ?? '').' '.(string) ($allocation->student?->classRoom?->section ?? '')),
                    'allocated_from' => optional($allocation->allocated_from)->toDateString(),
                ];
            })
            ->all();

        return [
            'room' => $room,
            'occupied_beds' => $occupiedBeds,
            'available_beds' => $availableBeds,
            'occupancy_percentage' => $occupancyPercentage,
            'current_students' => $currentStudents,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     room_name:string,
     *     floor_number:int,
     *     capacity:int,
     *     gender:?string,
     *     notes:?string,
     *     is_active:bool
     * }
     */
    private function normalizeRoomPayload(array $data, User $user, ?HostelRoom $room = null): array
    {
        $roomName = trim((string) ($data['room_name'] ?? ''));
        if ($roomName === '') {
            throw new RuntimeException('Room name is required.');
        }

        $floorNumber = (int) ($data['floor_number'] ?? 0);
        if ($floorNumber < 0) {
            throw new RuntimeException('Floor number must be zero or greater.');
        }

        $capacity = max(1, (int) ($data['capacity'] ?? HostelRoom::DEFAULT_CAPACITY));

        $hostelId = null;
        if ($user->isWarden()) {
            $hostelId = (int) ($user->hostel_id ?? 0);
            if ($hostelId <= 0) {
                throw new RuntimeException('Your warden account is not assigned to a hostel.');
            }
        } elseif (array_key_exists('hostel_id', $data) && $data['hostel_id'] !== null && $data['hostel_id'] !== '') {
            $hostelId = (int) $data['hostel_id'];
        } elseif ($room instanceof HostelRoom) {
            $hostelId = $room->hostel_id !== null ? (int) $room->hostel_id : null;
        }

        return [
            'hostel_id' => $hostelId,
            'room_name' => $roomName,
            'floor_number' => $floorNumber,
            'capacity' => $capacity,
            'gender' => $this->nullableTrimmedString($data['gender'] ?? null),
            'notes' => $this->nullableTrimmedString($data['notes'] ?? null),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{search:?string,floor_number:?int,is_active:?bool,gender:?string,per_page:int}
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(10, min(100, $perPage));

        $isActive = null;
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $isActive = (bool) (int) $filters['is_active'];
        }

        return [
            'search' => $this->nullableTrimmedString($filters['search'] ?? null),
            'floor_number' => isset($filters['floor_number']) && $filters['floor_number'] !== ''
                ? max(0, (int) $filters['floor_number'])
                : null,
            'is_active' => $isActive,
            'gender' => $this->nullableTrimmedString($filters['gender'] ?? null),
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

    private function assertRoomAccessible(HostelRoom $room, User $user): void
    {
        if (! $user->isWarden()) {
            return;
        }

        $hostelId = (int) ($user->hostel_id ?? 0);
        if ($hostelId <= 0 || (int) ($room->hostel_id ?? 0) !== $hostelId) {
            throw new RuntimeException('You are not allowed to access this hostel room.');
        }
    }
}
