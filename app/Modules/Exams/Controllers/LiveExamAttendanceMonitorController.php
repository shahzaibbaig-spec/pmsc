<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Services\ExamHallAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LiveExamAttendanceMonitorController extends Controller
{
    public function __construct(private readonly ExamHallAttendanceService $attendanceService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->tablesReady()) {
            return redirect()
                ->route('dashboard')
                ->with('error', $this->missingTablesMessage());
        }

        $examSessions = $this->attendanceService->examSessions();
        $selectedExamSessionId = $request->filled('exam_session_id')
            ? (int) $request->query('exam_session_id')
            : (int) ($examSessions->first()->id ?? 0);

        $payload = $selectedExamSessionId > 0
            ? $this->attendanceService->liveMonitorPayload($selectedExamSessionId)
            : [
                'summary' => [
                    'total_rooms' => 0,
                    'total_seats' => 0,
                    'marked' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'unmarked' => 0,
                ],
                'rooms' => [],
            ];

        return view('modules.principal.exams.live-attendance-monitor.index', [
            'examSessions' => $examSessions,
            'selectedExamSessionId' => $selectedExamSessionId > 0 ? $selectedExamSessionId : '',
            'payload' => $payload,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        if (! $this->tablesReady()) {
            return response()->json(['message' => $this->missingTablesMessage()], 422);
        }

        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
        ]);

        return response()->json(
            $this->attendanceService->liveMonitorPayload((int) $validated['exam_session_id'])
        );
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('exam_sessions')
            && Schema::hasTable('exam_rooms')
            && Schema::hasTable('exam_seating_plans')
            && Schema::hasTable('exam_seat_assignments')
            && Schema::hasTable('exam_room_invigilators')
            && Schema::hasTable('exam_attendances');
    }

    private function missingTablesMessage(): string
    {
        return 'Exam attendance monitor tables are missing on server. Please run latest migrations: php artisan migrate --force';
    }
}
