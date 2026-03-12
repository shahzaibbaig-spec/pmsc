<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        $days = (array) config('timetable.days', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']);
        $periodsPerDay = max(1, (int) config('timetable.periods_per_day', 7));
        $startTime = (string) config('timetable.start_time', '08:00');
        $periodMinutes = max(1, (int) config('timetable.period_minutes', 45));
        $breakMinutes = max(0, (int) config('timetable.break_minutes', 5));

        foreach ($days as $day) {
            $cursor = Carbon::createFromFormat('H:i', $startTime);

            for ($slot = 1; $slot <= $periodsPerDay; $slot++) {
                $slotStart = $cursor->copy();
                $slotEnd = $slotStart->copy()->addMinutes($periodMinutes);

                TimeSlot::query()->updateOrCreate(
                    [
                        'day_of_week' => $day,
                        'slot_index' => $slot,
                    ],
                    [
                        'start_time' => $slotStart->format('H:i:s'),
                        'end_time' => $slotEnd->format('H:i:s'),
                    ]
                );

                $cursor = $slotEnd->copy()->addMinutes($breakMinutes);
            }
        }
    }
}

