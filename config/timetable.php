<?php

return [
    'days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
    'periods_per_day' => (int) env('TIMETABLE_PERIODS_PER_DAY', 7),
    'start_time' => env('TIMETABLE_START_TIME', '08:00'),
    'period_minutes' => (int) env('TIMETABLE_PERIOD_MINUTES', 45),
    'break_minutes' => (int) env('TIMETABLE_BREAK_MINUTES', 5),
];

