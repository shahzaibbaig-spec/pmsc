<?php

namespace App\Modules\Exams\Services;

use App\Models\ExamRoom;
use App\Models\ExamSeatingPlan;
use App\Models\ExamSession;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamSeatingPlanService
{
    public function createRoom(array $attributes): ExamRoom
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $capacity = (int) ($attributes['capacity'] ?? 0);
        $isActive = (bool) ($attributes['is_active'] ?? true);

        if ($name === '') {
            throw new RuntimeException('Room name is required.');
        }

        if ($capacity <= 0) {
            throw new RuntimeException('Room capacity must be greater than zero.');
        }

        return ExamRoom::query()->create([
            'name' => $name,
            'capacity' => $capacity,
            'is_active' => $isActive,
        ]);
    }

    public function generatePlan(
        int $examSessionId,
        array $classIds,
        bool $randomize,
        ?int $generatedBy
    ): ExamSeatingPlan {
        $session = ExamSession::query()->find($examSessionId);
        if (! $session) {
            throw new RuntimeException('Exam session not found.');
        }

        $normalizedClassIds = collect($classIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($normalizedClassIds === []) {
            throw new RuntimeException('Select at least one class.');
        }

        $validClassIds = SchoolClass::query()
            ->whereIn('id', $normalizedClassIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($validClassIds === []) {
            throw new RuntimeException('No valid classes were selected.');
        }

        $rooms = ExamRoom::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'capacity']);

        if ($rooms->isEmpty()) {
            throw new RuntimeException('No active exam rooms found. Create rooms first.');
        }

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->whereIn('class_id', $validClassIds)
            ->where('status', 'active')
            ->get(['id', 'student_id', 'name', 'class_id']);

        if ($students->isEmpty()) {
            throw new RuntimeException('No active students found for selected classes.');
        }

        $orderedStudents = $randomize
            ? $this->randomizedInterleavedStudents($students)
            : $this->studentsByRollOrder($students);

        $totalCapacity = (int) $rooms->sum('capacity');
        if ($totalCapacity < $orderedStudents->count()) {
            throw new RuntimeException(sprintf(
                'Insufficient room capacity. Students: %d, Capacity: %d.',
                $orderedStudents->count(),
                $totalCapacity
            ));
        }

        return DB::transaction(function () use (
            $session,
            $rooms,
            $orderedStudents,
            $validClassIds,
            $randomize,
            $generatedBy
        ): ExamSeatingPlan {
            $plan = ExamSeatingPlan::query()->create([
                'exam_session_id' => (int) $session->id,
                'class_ids' => $validClassIds,
                'is_randomized' => $randomize,
                'total_students' => $orderedStudents->count(),
                'total_rooms' => 0,
                'generated_by' => $generatedBy ?: null,
                'generated_at' => now(),
            ]);

            $rows = [];
            $roomUsage = [];
            $roomIndex = 0;
            $seatNumber = 1;
            $currentRoom = $rooms[$roomIndex] ?? null;

            foreach ($orderedStudents as $student) {
                while ($currentRoom !== null && $seatNumber > (int) $currentRoom->capacity) {
                    $roomIndex++;
                    $currentRoom = $rooms[$roomIndex] ?? null;
                    $seatNumber = 1;
                }

                if ($currentRoom === null) {
                    throw new RuntimeException('Not enough room capacity while assigning seats.');
                }

                $roomId = (int) $currentRoom->id;
                $roomUsage[$roomId] = true;

                $rows[] = [
                    'exam_seating_plan_id' => (int) $plan->id,
                    'student_id' => (int) $student->id,
                    'class_id' => (int) $student->class_id,
                    'exam_room_id' => $roomId,
                    'seat_number' => $seatNumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $seatNumber++;
            }

            DB::table('exam_seat_assignments')->insert($rows);

            $plan->forceFill([
                'total_rooms' => count($roomUsage),
            ])->save();

            return $plan->fresh(['examSession', 'generator']) ?? $plan;
        });
    }

    /**
     * @param Collection<int, Student> $students
     * @return Collection<int, Student>
     */
    private function studentsByRollOrder(Collection $students): Collection
    {
        return $students
            ->sort(function (Student $a, Student $b): int {
                $aKey = $this->rollSortKey($a);
                $bKey = $this->rollSortKey($b);

                if ($aKey['number'] !== $bKey['number']) {
                    return $aKey['number'] <=> $bKey['number'];
                }

                if ($aKey['code'] !== $bKey['code']) {
                    return $aKey['code'] <=> $bKey['code'];
                }

                return $aKey['id'] <=> $bKey['id'];
            })
            ->values();
    }

    /**
     * @param Collection<int, Student> $students
     * @return Collection<int, Student>
     */
    private function randomizedInterleavedStudents(Collection $students): Collection
    {
        $grouped = $students
            ->groupBy('class_id')
            ->map(fn (Collection $rows): Collection => $rows->shuffle()->values());

        $result = collect();

        while ($grouped->isNotEmpty()) {
            $classIds = $grouped->keys()->all();
            shuffle($classIds);

            foreach ($classIds as $classId) {
                /** @var Collection<int, Student> $bucket */
                $bucket = $grouped->get($classId, collect());
                $student = $bucket->shift();

                if ($student) {
                    $result->push($student);
                }

                if ($bucket->isEmpty()) {
                    $grouped->forget($classId);
                } else {
                    $grouped->put($classId, $bucket);
                }
            }
        }

        return $result->values();
    }

    /**
     * @return array{number:int,code:string,id:int}
     */
    private function rollSortKey(Student $student): array
    {
        $code = strtolower(trim((string) ($student->student_id ?? '')));
        $number = PHP_INT_MAX;

        if (preg_match('/(\d+)/', $code, $matches) === 1) {
            $number = (int) $matches[1];
        }

        return [
            'number' => $number,
            'code' => $code !== '' ? $code : strtolower((string) $student->name),
            'id' => (int) $student->id,
        ];
    }
}
