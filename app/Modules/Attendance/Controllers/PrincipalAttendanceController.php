<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Modules\Attendance\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrincipalAttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        $date = (string) ($validated['date'] ?? now()->toDateString());
        $session = trim((string) ($validated['session'] ?? ''));
        $classId = isset($validated['class_id']) ? (int) $validated['class_id'] : null;

        $overview = $this->attendanceService->principalClasswiseAttendanceOverview(
            $date,
            $session !== '' ? $session : null,
            $classId
        );

        $sessions = $this->attendanceService->principalSessionOptions($date);
        $selectedSession = (string) ($overview['session'] ?? ($sessions[0] ?? ''));

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        return view('modules.principal.attendance.index', [
            'overview' => $overview,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'selectedDate' => (string) ($overview['date'] ?? $date),
            'selectedClassId' => $classId,
            'classes' => $classes,
        ]);
    }
}

