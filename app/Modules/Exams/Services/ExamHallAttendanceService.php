<?php

namespace App\Modules\Exams\Services;

use App\Models\ExamAttendance;
use App\Models\ExamRoom;
use App\Models\ExamRoomInvigilator;
use App\Models\ExamSeatAssignment;
use App\Models\ExamSeatingPlan;
use App\Models\ExamSession;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamHallAttendanceService
{
    public function canManageAllRooms(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Admin', 'Principal']);
    }

    /**
     * @return Collection<int, ExamSession>
     */
    public function examSessions(): Collection
    {
        return ExamSession::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'name', 'session', 'start_date', 'end_date']);
    }

    public function latestPlanForSession(int $examSessionId): ?ExamSeatingPlan
    {
        return ExamSeatingPlan::query()
            ->where('exam_session_id', $examSessionId)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, array{id:int,name:string,capacity:int,total_seats:int,invigilators:array<int,string>}>
     */
    public function roomOptionsForUser(int $examSessionId, ?User $user): Collection
    {
        $plan = $this->latestPlanForSession($examSessionId);
        if (! $plan) {
            return collect();
        }

        $seatCountMap = $this->seatCountMapForPlan((int) $plan->id);
        if ($seatCountMap->isEmpty()) {
            return collect();
        }

        $roomIds = $seatCountMap->keys()->map(fn ($id): int => (int) $id)->values();

        if (! $this->canManageAllRooms($user)) {
            $teacherId = $this->teacherIdForUser((int) ($user?->id ?? 0));
            if (! $teacherId) {
                return collect();
            }

            $assignedRoomIds = ExamRoomInvigilator::query()
                ->where('exam_session_id', $examSessionId)
                ->where('teacher_id', $teacherId)
                ->whereIn('room_id', $roomIds)
                ->pluck('room_id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            if ($assignedRoomIds->isEmpty()) {
                return collect();
            }

            $roomIds = $assignedRoomIds;
        }

        $rooms = ExamRoom::query()
            ->whereIn('id', $roomIds)
            ->orderBy('name')
            ->get(['id', 'name', 'capacity']);

        $invigilatorMap = $this->invigilatorNamesByRoom($examSessionId, $rooms->pluck('id'));

        return $rooms->map(function (ExamRoom $room) use ($seatCountMap, $invigilatorMap): array {
            return [
                'id' => (int) $room->id,
                'name' => (string) $room->name,
                'capacity' => (int) $room->capacity,
                'total_seats' => (int) ($seatCountMap->get((int) $room->id) ?? 0),
                'invigilators' => $invigilatorMap->get((int) $room->id, []),
            ];
        })->values();
    }

    /**
     * @return array{
     *   exam_session:array{id:int,name:string,session:string,start_date:string,end_date:string},
     *   room:array{id:int,name:string,capacity:int},
     *   plan:array{id:int,generated_at:string,generated_by:string},
     *   invigilators:array<int,string>,
     *   rows:array<int,array{
     *      seat_assignment_id:int,
     *      student_id:int,
     *      student_code:string,
     *      student_name:string,
     *      class_name:string,
     *      seat_number:int,
     *      status:string,
     *      remarks:string,
     *      is_marked:bool
     *   }>,
     *   summary:array{total_seats:int,marked:int,present:int,absent:int,late:int,unmarked:int}
     * }
     */
    public function roomAttendanceSheet(int $examSessionId, int $roomId, User $user): array
    {
        $examSession = ExamSession::query()->find($examSessionId, ['id', 'name', 'session', 'start_date', 'end_date']);
        if (! $examSession) {
            throw new RuntimeException('Exam session not found.');
        }

        $room = ExamRoom::query()->find($roomId, ['id', 'name', 'capacity']);
        if (! $room) {
            throw new RuntimeException('Exam room not found.');
        }

        $this->ensureUserCanAccessRoom($user, $examSessionId, $roomId);

        $plan = $this->latestPlanForSession($examSessionId);
        if (! $plan) {
            throw new RuntimeException('No seating plan is available for this exam session.');
        }

        $assignments = ExamSeatAssignment::query()
            ->with([
                'student:id,name,student_id,class_id',
                'classRoom:id,name,section',
            ])
            ->where('exam_seating_plan_id', (int) $plan->id)
            ->where('exam_room_id', $roomId)
            ->orderBy('seat_number')
            ->get(['id', 'student_id', 'class_id', 'exam_room_id', 'seat_number']);

        if ($assignments->isEmpty()) {
            throw new RuntimeException('No seat assignments found for this room in selected exam session.');
        }

        $attendanceMap = ExamAttendance::query()
            ->where('exam_session_id', $examSessionId)
            ->where('room_id', $roomId)
            ->whereIn('student_id', $assignments->pluck('student_id'))
            ->get(['id', 'student_id', 'status', 'remarks'])
            ->keyBy(fn (ExamAttendance $attendance): int => (int) $attendance->student_id);

        $rows = $assignments->map(function (ExamSeatAssignment $assignment) use ($attendanceMap): array {
            $student = $assignment->student;
            $classLabel = trim((string) ($assignment->classRoom?->name ?? '').' '.(string) ($assignment->classRoom?->section ?? ''));
            $attendance = $attendanceMap->get((int) $assignment->student_id);

            return [
                'seat_assignment_id' => (int) $assignment->id,
                'student_id' => (int) $assignment->student_id,
                'student_code' => (string) ($student?->student_id ?: $assignment->student_id),
                'student_name' => (string) ($student?->name ?? 'Student'),
                'class_name' => $classLabel !== '' ? $classLabel : '-',
                'seat_number' => (int) $assignment->seat_number,
                'status' => (string) ($attendance?->status ?? ExamAttendance::STATUS_PRESENT),
                'remarks' => (string) ($attendance?->remarks ?? ''),
                'is_marked' => $attendance !== null,
            ];
        })->values()->all();

        $summary = $this->roomSummaryFromRows(
            totalSeats: $assignments->count(),
            attendanceRows: $attendanceMap->values()
        );

        return [
            'exam_session' => [
                'id' => (int) $examSession->id,
                'name' => (string) $examSession->name,
                'session' => (string) $examSession->session,
                'start_date' => optional($examSession->start_date)->format('d M Y') ?: '-',
                'end_date' => optional($examSession->end_date)->format('d M Y') ?: '-',
            ],
            'room' => [
                'id' => (int) $room->id,
                'name' => (string) $room->name,
                'capacity' => (int) $room->capacity,
            ],
            'plan' => [
                'id' => (int) $plan->id,
                'generated_at' => optional($plan->generated_at)->format('d M Y h:i A') ?: '-',
                'generated_by' => (string) ($plan->generator?->name ?? 'System'),
            ],
            'invigilators' => $this->invigilatorNamesByRoom($examSessionId, collect([(int) $room->id]))
                ->get((int) $room->id, []),
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * @param array<int, array{student_id:int,status:string,remarks?:string|null}> $records
     * @return array{total_seats:int,marked:int,present:int,absent:int,late:int,unmarked:int}
     */
    public function saveRoomAttendance(int $examSessionId, int $roomId, User $user, array $records): array
    {
        $this->ensureUserCanAccessRoom($user, $examSessionId, $roomId);

        $plan = $this->latestPlanForSession($examSessionId);
        if (! $plan) {
            throw new RuntimeException('No seating plan is available for this exam session.');
        }

        $assignments = ExamSeatAssignment::query()
            ->where('exam_seating_plan_id', (int) $plan->id)
            ->where('exam_room_id', $roomId)
            ->get(['id', 'student_id']);

        if ($assignments->isEmpty()) {
            throw new RuntimeException('No seat assignments found for this room in selected exam session.');
        }

        $recordMap = collect($records)
            ->keyBy(fn (array $row): int => (int) ($row['student_id'] ?? 0));

        $normalizedRows = $assignments->map(function (ExamSeatAssignment $assignment) use ($recordMap): array {
            $record = $recordMap->get((int) $assignment->student_id, []);
            $status = (string) ($record['status'] ?? ExamAttendance::STATUS_PRESENT);
            $remarks = trim((string) ($record['remarks'] ?? ''));

            return [
                'student_id' => (int) $assignment->student_id,
                'seat_assignment_id' => (int) $assignment->id,
                'status' => in_array($status, ExamAttendance::STATUSES, true) ? $status : ExamAttendance::STATUS_PRESENT,
                'remarks' => $remarks !== '' ? $remarks : null,
            ];
        })->values();

        DB::transaction(function () use ($examSessionId, $roomId, $normalizedRows, $user): void {
            ExamAttendance::query()
                ->where('exam_session_id', $examSessionId)
                ->where('room_id', $roomId)
                ->whereNotIn('student_id', $normalizedRows->pluck('student_id'))
                ->delete();

            foreach ($normalizedRows as $row) {
                ExamAttendance::query()->updateOrCreate(
                    [
                        'exam_session_id' => $examSessionId,
                        'room_id' => $roomId,
                        'student_id' => (int) $row['student_id'],
                    ],
                    [
                        'seat_assignment_id' => (int) $row['seat_assignment_id'],
                        'status' => (string) $row['status'],
                        'remarks' => $row['remarks'],
                        'marked_by' => (int) $user->id,
                        'marked_at' => now(),
                    ]
                );
            }
        });

        $attendanceRows = ExamAttendance::query()
            ->where('exam_session_id', $examSessionId)
            ->where('room_id', $roomId)
            ->get(['status']);

        return $this->roomSummaryFromRows(
            totalSeats: $normalizedRows->count(),
            attendanceRows: $attendanceRows
        );
    }

    /**
     * @return array{
     *   summary:array{
     *      total_rooms:int,total_seats:int,marked:int,present:int,absent:int,late:int,unmarked:int
     *   },
     *   rooms:array<int,array{
     *      room_id:int,room_name:string,capacity:int,invigilators:array<int,string>,
     *      total_seats:int,marked:int,present:int,absent:int,late:int,unmarked:int,progress:float
     *   }>
     * }
     */
    public function liveMonitorPayload(int $examSessionId): array
    {
        $plan = $this->latestPlanForSession($examSessionId);
        if (! $plan) {
            return [
                'summary' => [
                    'total_rooms' => 0,
                    'total_seats' => 0,
                    'marked' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'unmarked' => 0,
                ],
                'rooms' => [],
            ];
        }

        $seatCountMap = $this->seatCountMapForPlan((int) $plan->id);
        $roomIds = $seatCountMap->keys()->map(fn ($id): int => (int) $id)->values();

        if ($roomIds->isEmpty()) {
            return [
                'summary' => [
                    'total_rooms' => 0,
                    'total_seats' => 0,
                    'marked' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'unmarked' => 0,
                ],
                'rooms' => [],
            ];
        }

        $rooms = ExamRoom::query()
            ->whereIn('id', $roomIds)
            ->orderBy('name')
            ->get(['id', 'name', 'capacity']);

        $attendanceStatusRows = ExamAttendance::query()
            ->where('exam_session_id', $examSessionId)
            ->whereIn('room_id', $roomIds)
            ->selectRaw('room_id, status, count(*) as total')
            ->groupBy('room_id', 'status')
            ->get();

        $attendanceByRoom = $attendanceStatusRows
            ->groupBy(fn ($row): int => (int) $row->room_id);

        $invigilatorMap = $this->invigilatorNamesByRoom($examSessionId, $roomIds);

        $roomRows = $rooms->map(function (ExamRoom $room) use ($seatCountMap, $attendanceByRoom, $invigilatorMap): array {
            $totalSeats = (int) ($seatCountMap->get((int) $room->id) ?? 0);
            $statusRows = $attendanceByRoom->get((int) $room->id, collect());

            $present = (int) ($statusRows->firstWhere('status', ExamAttendance::STATUS_PRESENT)->total ?? 0);
            $absent = (int) ($statusRows->firstWhere('status', ExamAttendance::STATUS_ABSENT)->total ?? 0);
            $late = (int) ($statusRows->firstWhere('status', ExamAttendance::STATUS_LATE)->total ?? 0);
            $marked = $present + $absent + $late;
            $unmarked = max(0, $totalSeats - $marked);
            $progress = $totalSeats > 0 ? round(($marked / $totalSeats) * 100, 2) : 0.0;

            return [
                'room_id' => (int) $room->id,
                'room_name' => (string) $room->name,
                'capacity' => (int) $room->capacity,
                'invigilators' => $invigilatorMap->get((int) $room->id, []),
                'total_seats' => $totalSeats,
                'marked' => $marked,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'unmarked' => $unmarked,
                'progress' => $progress,
            ];
        })->values();

        $summary = [
            'total_rooms' => $roomRows->count(),
            'total_seats' => (int) $roomRows->sum('total_seats'),
            'marked' => (int) $roomRows->sum('marked'),
            'present' => (int) $roomRows->sum('present'),
            'absent' => (int) $roomRows->sum('absent'),
            'late' => (int) $roomRows->sum('late'),
            'unmarked' => (int) $roomRows->sum('unmarked'),
        ];

        return [
            'summary' => $summary,
            'rooms' => $roomRows->all(),
        ];
    }

    public function ensureUserCanAccessRoom(User $user, int $examSessionId, int $roomId): void
    {
        if ($this->canManageAllRooms($user)) {
            return;
        }

        $teacherId = $this->teacherIdForUser((int) $user->id);
        if (! $teacherId) {
            throw new RuntimeException('Teacher profile was not found for this account.');
        }

        $isAssigned = ExamRoomInvigilator::query()
            ->where('exam_session_id', $examSessionId)
            ->where('room_id', $roomId)
            ->where('teacher_id', $teacherId)
            ->exists();

        if (! $isAssigned) {
            throw new RuntimeException('You are not assigned to mark attendance for this room.');
        }
    }

    /**
     * @param Collection<int, int> $roomIds
     * @return Collection<int, array<int, string>>
     */
    public function invigilatorNamesByRoom(int $examSessionId, Collection $roomIds): Collection
    {
        if ($roomIds->isEmpty()) {
            return collect();
        }

        return ExamRoomInvigilator::query()
            ->with('teacher.user:id,name')
            ->where('exam_session_id', $examSessionId)
            ->whereIn('room_id', $roomIds)
            ->get(['id', 'room_id', 'teacher_id'])
            ->groupBy(fn (ExamRoomInvigilator $row): int => (int) $row->room_id)
            ->map(function (Collection $rows): array {
                return $rows
                    ->map(fn (ExamRoomInvigilator $row): string => (string) ($row->teacher?->user?->name ?? 'Teacher'))
                    ->filter(fn (string $name): bool => trim($name) !== '')
                    ->unique()
                    ->values()
                    ->all();
            });
    }

    /**
     * @return Collection<int, int>
     */
    public function seatCountMapForPlan(int $planId): Collection
    {
        return ExamSeatAssignment::query()
            ->where('exam_seating_plan_id', $planId)
            ->selectRaw('exam_room_id, count(*) as total')
            ->groupBy('exam_room_id')
            ->pluck('total', 'exam_room_id')
            ->map(fn ($total): int => (int) $total);
    }

    /**
     * @param Collection<int, mixed> $attendanceRows
     * @return array{total_seats:int,marked:int,present:int,absent:int,late:int,unmarked:int}
     */
    private function roomSummaryFromRows(int $totalSeats, Collection $attendanceRows): array
    {
        $present = (int) $attendanceRows
            ->where('status', ExamAttendance::STATUS_PRESENT)
            ->count();
        $absent = (int) $attendanceRows
            ->where('status', ExamAttendance::STATUS_ABSENT)
            ->count();
        $late = (int) $attendanceRows
            ->where('status', ExamAttendance::STATUS_LATE)
            ->count();
        $marked = $present + $absent + $late;

        return [
            'total_seats' => $totalSeats,
            'marked' => $marked,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'unmarked' => max(0, $totalSeats - $marked),
        ];
    }

    private function teacherIdForUser(int $userId): ?int
    {
        return Teacher::query()
            ->where('user_id', $userId)
            ->value('id');
    }
}
