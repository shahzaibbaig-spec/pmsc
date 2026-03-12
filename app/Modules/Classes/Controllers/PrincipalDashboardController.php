<?php

namespace App\Modules\Classes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MedicalReferral;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Modules\Attendance\Services\AttendanceService;
use Illuminate\View\View;

class PrincipalDashboardController extends Controller
{
    public function __invoke(AttendanceService $attendanceService): View
    {
        $attendanceSummary = $attendanceService->principalDailySummary(now()->toDateString());

        return view('modules.principal.dashboard', [
            'attendanceSummary' => $attendanceSummary,
            'stats' => [
                'classes' => SchoolClass::query()->count(),
                'subjects' => Subject::query()->count(),
                'teachers' => Teacher::query()->count(),
                'pending_medical' => MedicalReferral::query()->where('status', 'pending')->count(),
            ],
        ]);
    }
}
