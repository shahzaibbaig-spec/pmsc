<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamAttendance;
use App\Modules\Exams\Services\ExamHallAttendanceService;
use App\Modules\Reports\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

class ExamHallAttendanceController extends Controller
{
    public function __construct(
        private readonly ExamHallAttendanceService $attendanceService,
        private readonly ReportService $reportService,
    ) {
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

        $rooms = $selectedExamSessionId > 0
            ? $this->attendanceService->roomOptionsForUser($selectedExamSessionId, $request->user())
            : collect();

        $selectedRoomId = $request->filled('room_id')
            ? (int) $request->query('room_id')
            : (int) ($rooms->first()['id'] ?? 0);

        if (! $rooms->contains(fn (array $room): bool => (int) $room['id'] === $selectedRoomId)) {
            $selectedRoomId = (int) ($rooms->first()['id'] ?? 0);
        }

        return view('modules.exams.hall-attendance.index', [
            'examSessions' => $examSessions,
            'selectedExamSessionId' => $selectedExamSessionId > 0 ? $selectedExamSessionId : '',
            'rooms' => $rooms,
            'selectedRoomId' => $selectedRoomId > 0 ? $selectedRoomId : '',
            'canManageAllRooms' => $this->attendanceService->canManageAllRooms($request->user()),
            'statusOptions' => ExamAttendance::STATUSES,
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        if (! $this->tablesReady()) {
            return response()->json(['message' => $this->missingTablesMessage()], 422);
        }

        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
        ]);

        $rooms = $this->attendanceService->roomOptionsForUser(
            (int) $validated['exam_session_id'],
            $request->user()
        );

        return response()->json([
            'rooms' => $rooms->values()->all(),
        ]);
    }

    public function sheet(Request $request): JsonResponse
    {
        if (! $this->tablesReady()) {
            return response()->json(['message' => $this->missingTablesMessage()], 422);
        }

        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'room_id' => ['required', 'integer', 'exists:exam_rooms,id'],
        ]);

        try {
            $sheet = $this->attendanceService->roomAttendanceSheet(
                (int) $validated['exam_session_id'],
                (int) $validated['room_id'],
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $this->statusCodeForMessage($exception->getMessage()));
        }

        return response()->json($sheet);
    }

    public function save(Request $request): JsonResponse
    {
        if (! $this->tablesReady()) {
            return response()->json(['message' => $this->missingTablesMessage()], 422);
        }

        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'room_id' => ['required', 'integer', 'exists:exam_rooms,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer'],
            'records.*.status' => ['required', 'in:present,absent,late'],
            'records.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $summary = $this->attendanceService->saveRoomAttendance(
                (int) $validated['exam_session_id'],
                (int) $validated['room_id'],
                $request->user(),
                $validated['records']
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $this->statusCodeForMessage($exception->getMessage()));
        }

        return response()->json([
            'message' => 'Room attendance saved successfully.',
            'summary' => $summary,
        ]);
    }

    public function roomSheetPdf(Request $request): Response
    {
        if (! $this->tablesReady()) {
            return response($this->missingTablesMessage(), 422);
        }

        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'room_id' => ['required', 'integer', 'exists:exam_rooms,id'],
        ]);

        try {
            $sheet = $this->attendanceService->roomAttendanceSheet(
                (int) $validated['exam_session_id'],
                (int) $validated['room_id'],
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), $this->statusCodeForMessage($exception->getMessage()));
        }

        $pdf = Pdf::loadView('modules.reports.exam-room-attendance-sheet', [
            'school' => $this->reportService->schoolMeta(),
            'sheet' => $sheet,
            'generatedAt' => now()->format('d M Y h:i A'),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'room_attendance_%s_room_%d.pdf',
            preg_replace('/[^a-z0-9\-]+/i', '_', strtolower((string) ($sheet['exam_session']['name'] ?? 'exam'))),
            (int) ($sheet['room']['id'] ?? 0)
        );

        return $pdf->stream($filename);
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
        return 'Exam hall attendance tables are missing on server. Please run latest migrations: php artisan migrate --force';
    }

    private function statusCodeForMessage(string $message): int
    {
        $normalized = strtolower($message);
        if (str_contains($normalized, 'not assigned') || str_contains($normalized, 'teacher profile')) {
            return 403;
        }

        return 422;
    }
}
