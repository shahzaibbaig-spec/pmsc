<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use App\Models\TimetableConstraint;
use App\Modules\Timetable\Requests\RegenerateTimeSlotsRequest;
use App\Modules\Timetable\Requests\StoreTimetableConstraintRequest;
use App\Modules\Timetable\Requests\TimeSlotDataRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TimetableSettingsController extends Controller
{
    public function index(): View
    {
        $sessions = $this->sessionOptions();

        return view('modules.principal.timetable.settings', [
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
            'days' => config('timetable.days', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']),
            'configPeriodsPerDay' => (int) config('timetable.periods_per_day', 7),
            'configStartTime' => (string) config('timetable.start_time', '08:00'),
            'configPeriodMinutes' => (int) config('timetable.period_minutes', 45),
            'configBreakMinutes' => (int) config('timetable.break_minutes', 5),
        ]);
    }

    public function timeSlotsData(TimeSlotDataRequest $request): JsonResponse
    {
        $search = (string) $request->input('search', '');
        $day = (string) $request->input('day_of_week', '');
        $perPage = (int) $request->input('per_page', 20);

        $query = TimeSlot::query()
            ->when($day !== '', fn ($q) => $q->where('day_of_week', $day))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($w) use ($search): void {
                    $w->where('day_of_week', 'like', $search.'%')
                        ->orWhere('slot_index', 'like', $search.'%')
                        ->orWhere('start_time', 'like', $search.'%')
                        ->orWhere('end_time', 'like', $search.'%');
                });
            })
            ->orderByRaw($this->dayOrderSql('day_of_week'))
            ->orderBy('slot_index');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function (TimeSlot $slot): array {
                return [
                    'id' => $slot->id,
                    'day_of_week' => $slot->day_of_week,
                    'day_label' => strtoupper($slot->day_of_week),
                    'slot_index' => $slot->slot_index,
                    'start_time' => substr((string) $slot->start_time, 0, 5),
                    'end_time' => substr((string) $slot->end_time, 0, 5),
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

    public function constraintsData(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $search = (string) $request->input('search', '');
        $perPage = (int) $request->input('per_page', 20);

        $paginator = TimetableConstraint::query()
            ->when($search !== '', fn ($q) => $q->where('session', 'like', $search.'%'))
            ->orderByDesc('session')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (TimetableConstraint $row): array => [
                'id' => $row->id,
                'session' => $row->session,
                'max_periods_per_day_teacher' => (int) $row->max_periods_per_day_teacher,
                'max_periods_per_week_teacher' => (int) $row->max_periods_per_week_teacher,
                'max_periods_per_day_class' => (int) $row->max_periods_per_day_class,
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function storeConstraint(StoreTimetableConstraintRequest $request): JsonResponse
    {
        $data = $request->validated();

        $constraint = TimetableConstraint::query()->updateOrCreate(
            ['session' => $data['session']],
            [
                'max_periods_per_day_teacher' => (int) $data['max_periods_per_day_teacher'],
                'max_periods_per_week_teacher' => (int) $data['max_periods_per_week_teacher'],
                'max_periods_per_day_class' => (int) $data['max_periods_per_day_class'],
            ]
        );

        return response()->json([
            'message' => 'Timetable constraints saved successfully.',
            'id' => $constraint->id,
        ]);
    }

    public function regenerateTimeSlots(RegenerateTimeSlotsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $days = $data['days'] ?? config('timetable.days', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']);
        $periodsPerDay = (int) $data['periods_per_day'];
        $periodMinutes = (int) $data['period_minutes'];
        $breakMinutes = (int) $data['break_minutes'];
        $startTime = (string) $data['start_time'];

        $generatedCount = DB::transaction(function () use (
            $days,
            $periodsPerDay,
            $periodMinutes,
            $breakMinutes,
            $startTime
        ): int {
            TimeSlot::query()->delete();

            $rows = [];
            $now = now();
            foreach ($days as $day) {
                $cursor = Carbon::createFromFormat('H:i', $startTime);
                for ($slot = 1; $slot <= $periodsPerDay; $slot++) {
                    $slotStart = $cursor->copy();
                    $slotEnd = $slotStart->copy()->addMinutes($periodMinutes);

                    $rows[] = [
                        'day_of_week' => $day,
                        'slot_index' => $slot,
                        'start_time' => $slotStart->format('H:i:s'),
                        'end_time' => $slotEnd->format('H:i:s'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $cursor = $slotEnd->copy()->addMinutes($breakMinutes);
                }
            }

            TimeSlot::query()->insert($rows);

            return count($rows);
        });

        return response()->json([
            'message' => 'Time slots regenerated successfully.',
            'generated_count' => $generatedCount,
        ]);
    }

    private function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        $sessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
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

