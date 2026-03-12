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
        $classes = $this->service->classTeacherClassesForUser((int) auth()->id(), $date);

        return view('modules.teacher.attendance.index', [
            'defaultDate' => $date,
            'classes' => $classes,
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $classes = $this->service->classTeacherClassesForUser((int) auth()->id(), (string) $request->input('date'));

        return response()->json([
            'classes' => $classes,
        ]);
    }

    public function sheet(AttendanceSheetRequest $request): JsonResponse
    {
        $classId = (int) $request->input('class_id');
        $date = $request->string('date')->toString();

        $classes = $this->service->classTeacherClassesForUser((int) auth()->id(), $date);
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
                $request->input('records', [])
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Attendance saved successfully.']);
    }
}

