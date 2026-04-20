<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Requests\AttendanceSheetRequest;
use App\Modules\Attendance\Requests\MarkAttendanceRequest;
use App\Modules\Attendance\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class TeacherAttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $service)
    {
    }

    public function index(): View
    {
        $date = now()->toDateString();
        $userId = (int) auth()->id();
        $sessions = $this->service->classTeacherSessionsForUser($userId, $date);
        $selectedSession = trim((string) request()->query('session', ''));
        if ($selectedSession === '') {
            $selectedSession = (string) ($sessions[0] ?? '');
        }
        $classes = $this->service->classTeacherClassesForUser($userId, $date, $selectedSession);
        $selectedSession = (string) ($classes->first()['session'] ?? $selectedSession);

        return view('modules.teacher.attendance.index', [
            'defaultDate' => $date,
            'classes' => $classes,
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'session' => ['nullable', 'string', 'max:20'],
        ]);

        $userId = (int) auth()->id();
        $date = (string) $request->input('date');
        $requestedSession = trim((string) $request->input('session', ''));
        $classes = $this->service->classTeacherClassesForUser($userId, $date, $requestedSession);
        $sessions = $this->service->classTeacherSessionsForUser($userId, $date);
        $selectedSession = (string) ($classes->first()['session'] ?? ($requestedSession !== '' ? $requestedSession : ($sessions[0] ?? '')));

        return response()->json([
            'classes' => $classes,
            'sessions' => $sessions,
            'selected_session' => $selectedSession,
        ]);
    }

    public function sheet(AttendanceSheetRequest $request): JsonResponse
    {
        $classId = (int) $request->input('class_id');
        $date = $request->string('date')->toString();
        $session = trim($request->string('session')->toString());

        $classes = $this->service->classTeacherClassesForUser((int) auth()->id(), $date, $session);
        $isAllowed = $classes->contains(fn (array $row): bool => (int) $row['class_id'] === $classId);

        if (! $isAllowed) {
            return response()->json(['message' => 'You are not assigned to this class for the selected date/session.'], 403);
        }

        $sheet = $this->service->classAttendanceSheet($classId, $date);

        return response()->json($sheet);
    }

    public function mark(MarkAttendanceRequest $request): JsonResponse
    {
        try {
            $this->service->markAttendance(
                (int) auth()->id(),
                (int) $request->input('class_id'),
                $request->string('date')->toString(),
                $request->input('records', []),
                trim($request->string('session')->toString())
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Attendance saved successfully.']);
    }
}
