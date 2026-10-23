<?php

return [
    // Enable only on the installation whose student records belong to Mirpur.
    'enabled' => env('SENIOR_PROGRESS_REPORTS_ENABLED', false),
    'campus' => env('SENIOR_PROGRESS_REPORTS_CAMPUS', ''),
];
