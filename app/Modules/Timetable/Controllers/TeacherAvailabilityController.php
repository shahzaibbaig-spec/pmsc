<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TimeSlot;
use App\Modules\Timetable\Requests\SaveTeacherAvailabilityRequest;
use App\Modules\Timetable\Requests\TeacherAvailabilityDataRequest;
use App\Modules\Timetable\Requests\TeacherAvailabilityMatrixRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeacherAvailabilityController extends Controller
{
    public function index(): View
    {
        return view('modules.principal.timetable.teacher-availability');
    }

    public function options(): JsonResponse
    {
        $teachers = Teacher::query()
            ->with('user:id,name,email')
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id', 'employee_code', 'designation']);

        $slotHeaders = TimeSlot::query()
            ->select('slot_index')
            ->selectRaw('MIN(start_time) as start_time')
            ->selectRaw('MAX(end_time) as end_time')
            ->groupBy('slot_index')
            ->orderBy('slot_index')
            ->get();

        return response()->json([
            'teachers' => $teachers->map(function (Teacher $teacher): array {
                return [
                    'id' => $teacher->id,
                    'teacher_id' => $teacher->teacher_id,
                    'name' => $teacher->user?->name ?? 'Teacher',
                    'email' => $teacher->user?->email,
                    'designation' => $teacher->designation,
                    'employee_code' => $teacher->employee_code,
                ];
            })->values()->all(),
            'slot_headers' => $slotHeaders->map(fn (TimeSlot $slot): array => [
                'slot_index' => (int) $slot->slot_index,
                'start_time' => substr((string) $slot->start_time, 0, 5),
                'end_time' => substr((string) $slot->end_time, 0, 5),
            ])->values()->all(),
            'days' => config('timetable.days', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']),
        ]);
    }

    public function matrix(TeacherAvailabilityMatrixRequest $request): JsonResponse
    {
        $teacherId = (int) $request->input('teacher_id');
        $days = config('timetable.days', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']);

        $slotHeaders = TimeSlot::query()
            ->select('slot_index')
            ->selectRaw('MIN(start_time) as start_time')
            ->selectRaw('MAX(end_time) as end_time')
            ->groupBy('slot_index')
            ->orderBy('slot_index')
            ->get();

        $availabilityMap = TeacherAvailability::query()
            ->where('teacher_id', $teacherId)
            ->get(['day_of_week', 'slot_index', 'is_available'])
            ->mapWithKeys(fn (TeacherAvailability $a): array => [
                $a->day_of_week.'|'.$a->slot_index => (bool) $a->is_available,
            ]);

        $matrixRows = collect($days)->map(function (string $day) use ($slotHeaders, $availabilityMap): array {
            return [
                'day_of_week' => $day,
                'day_label' => strtoupper($day),
                'slots' => $slotHeaders->map(function (TimeSlot $slot) use ($day, $availabilityMap): array {
                    $key = $day.'|'.$slot->slot_index;

                    return [
                        'slot_index' => (int) $slot->slot_index,
                        'start_time' => substr((string) $slot->start_time, 0, 5),
                        'end_time' => substr((string) $slot->end_time, 0, 5),
                        'is_available' => $availabilityMap->has($key) ? (bool) $availabilityMap->get($key) : true,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'teacher_id' => $teacherId,
            'slot_headers' => $slotHeaders->map(fn (TimeSlot $slot): array => [
                'slot_index' => (int) $slot->slot_index,
                'start_time' => substr((string) $slot->start_time, 0, 5),
                'end_time' => substr((string) $slot->end_time, 0, 5),
            ])->values()->all(),
            'rows' => $matrixRows,
        ]);
    }

    public function save(SaveTeacherAvailabilityRequest $request): JsonResponse
    {
        $teacherId = (int) $request->input('teacher_id');
        $records = $request->input('records', []);

        $validKeys = TimeSlot::query()
            ->get(['day_of_week', 'slot_index'])
            ->mapWithKeys(fn (TimeSlot $slot): array => [
                $slot->day_of_week.'|'.$slot->slot_index => true,
            ]);

        foreach ($records as $record) {
            $key = $record['day_of_week'].'|'.$record['slot_index'];
            if (! $validKeys->has($key)) {
                return response()->json(['message' => 'Invalid day/slot combination submitted.'], 422);
            }
        }

        DB::transaction(function () use ($teacherId, $records): void {
            TeacherAvailability::query()
                ->where('teacher_id', $teacherId)
                ->delete();

            $now = now();
            $rows = collect($records)->map(function (array $row) use ($teacherId, $now): array {
                return [
                    'teacher_id' => $teacherId,
                    'day_of_week' => $row['day_of_week'],
                    'slot_index' => (int) $row['slot_index'],
                    'is_available' => (bool) $row['is_available'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            TeacherAvailability::query()->insert($rows);
        });

        return response()->json(['message' => 'Teacher availability updated successfully.']);
    }

    public function data(TeacherAvailabilityDataRequest $request): JsonResponse
    {
        $teacherId = (int) $request->input('teacher_id');
        $day = (string) $request->input('day_of_week', '');
        $search = (string) $request->input('search', '');
        $perPage = (int) $request->input('per_page', 20);

        $timeSlotMap = TimeSlot::query()
            ->get(['day_of_week', 'slot_index', 'start_time', 'end_time'])
            ->mapWithKeys(fn (TimeSlot $slot): array => [
                $slot->day_of_week.'|'.$slot->slot_index => [
                    'start_time' => substr((string) $slot->start_time, 0, 5),
                    'end_time' => substr((string) $slot->end_time, 0, 5),
                ],
            ]);

        $query = TeacherAvailability::query()
            ->where('teacher_id', $teacherId)
            ->when($day !== '', fn ($q) => $q->where('day_of_week', $day))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($w) use ($search): void {
                    $w->where('day_of_week', 'like', $search.'%')
                        ->orWhere('slot_index', 'like', $search.'%')
                        ->orWhere('is_available', strtolower($search) === 'available' ? '1' : '0');
                });
            })
            ->orderByRaw($this->dayOrderSql('day_of_week'))
            ->orderBy('slot_index');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function (TeacherAvailability $row) use ($timeSlotMap): array {
                $slotMeta = $timeSlotMap->get($row->day_of_week.'|'.$row->slot_index, [
                    'start_time' => null,
                    'end_time' => null,
                ]);

                return [
                    'id' => $row->id,
                    'day_of_week' => $row->day_of_week,
                    'day_label' => strtoupper($row->day_of_week),
                    'slot_index' => (int) $row->slot_index,
                    'start_time' => $slotMeta['start_time'],
                    'end_time' => $slotMeta['end_time'],
                    'is_available' => (bool) $row->is_available,
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    private function dayOrderSql(string $column): string
    {
        return "CASE {$column}
            WHEN 'mon' THEN 1
            WHEN 'tue' THEN 2
            WHEN 'wed' THEN 3
            WHEN 'thu' THEN 4
            WHEN 'fri' THEN 5
            WHEN 'sat' THEN 6
            ELSE 99 END";
    }
}
