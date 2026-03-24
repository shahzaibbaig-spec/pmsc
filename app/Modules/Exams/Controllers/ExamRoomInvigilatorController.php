<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamRoom;
use App\Models\ExamRoomInvigilator;
use App\Models\Teacher;
use App\Modules\Exams\Services\ExamHallAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class ExamRoomInvigilatorController extends Controller
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

        $plan = $selectedExamSessionId > 0
            ? $this->attendanceService->latestPlanForSession($selectedExamSessionId)
            : null;

        $seatCountMap = $plan
            ? $this->attendanceService->seatCountMapForPlan((int) $plan->id)
            : collect();

        $roomIds = $seatCountMap->keys()
            ->map(fn ($id): int => (int) $id)
            ->values();

        $rooms = $roomIds->isNotEmpty()
            ? ExamRoom::query()
                ->whereIn('id', $roomIds)
                ->orderBy('name')
                ->get(['id', 'name', 'capacity'])
            : collect();

        $teachers = Teacher::query()
            ->with('user:id,name,status')
            ->get(['id', 'user_id', 'teacher_id', 'employee_code'])
            ->filter(fn (Teacher $teacher): bool => $teacher->user !== null)
            ->filter(function (Teacher $teacher): bool {
                $status = strtolower((string) ($teacher->user?->status ?? 'active'));

                return in_array($status, ['active', ''], true);
            })
            ->sortBy(fn (Teacher $teacher): string => strtolower((string) $teacher->user?->name))
            ->values();

        $assignmentBaseQuery = ExamRoomInvigilator::query()
            ->when($selectedExamSessionId > 0, function ($query) use ($selectedExamSessionId): void {
                $query->where('exam_session_id', $selectedExamSessionId);
            });

        $assignedRoomCount = (clone $assignmentBaseQuery)
            ->distinct('room_id')
            ->count('room_id');

        $totalAssignments = (clone $assignmentBaseQuery)->count();

        $assignments = (clone $assignmentBaseQuery)
            ->with([
                'room:id,name,capacity',
                'teacher:id,user_id,teacher_id,employee_code',
                'teacher.user:id,name',
            ])
            ->orderBy('room_id')
            ->orderBy('teacher_id')
            ->paginate(30)
            ->withQueryString();

        $summary = [
            'total_rooms' => $rooms->count(),
            'assigned_rooms' => $assignedRoomCount,
            'total_assignments' => $totalAssignments,
            'seats_in_plan' => (int) $seatCountMap->sum(),
        ];

        return view('modules.principal.exams.room-invigilators.index', [
            'examSessions' => $examSessions,
            'selectedExamSessionId' => $selectedExamSessionId > 0 ? $selectedExamSessionId : '',
            'rooms' => $rooms,
            'teachers' => $teachers,
            'assignments' => $assignments,
            'seatCountMap' => $seatCountMap,
            'hasSeatingPlan' => $plan !== null,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->tablesReady()) {
            return back()->with('error', $this->missingTablesMessage());
        }

        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'room_id' => ['required', 'integer', 'exists:exam_rooms,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
        ]);

        $examSessionId = (int) $validated['exam_session_id'];
        $roomId = (int) $validated['room_id'];

        $plan = $this->attendanceService->latestPlanForSession($examSessionId);
        if (! $plan) {
            return back()->with('error', 'No seating plan exists for selected exam session. Generate seating plan first.');
        }

        $roomExistsInPlan = $this->attendanceService
            ->seatCountMapForPlan((int) $plan->id)
            ->has($roomId);

        if (! $roomExistsInPlan) {
            return back()->with('error', 'Selected room has no assigned seats in latest seating plan for this exam session.');
        }

        try {
            $assignment = ExamRoomInvigilator::query()->firstOrCreate([
                'exam_session_id' => $examSessionId,
                'room_id' => $roomId,
                'teacher_id' => (int) $validated['teacher_id'],
            ]);
        } catch (Throwable) {
            return back()->with('error', 'Unable to assign invigilator. Please try again.');
        }

        $message = $assignment->wasRecentlyCreated
            ? 'Invigilator assigned successfully.'
            : 'This invigilator is already assigned to the selected room/session.';

        return redirect()
            ->route('principal.exams.room-invigilators.index', ['exam_session_id' => $examSessionId])
            ->with('status', $message);
    }

    public function destroy(Request $request, ExamRoomInvigilator $examRoomInvigilator): RedirectResponse
    {
        if (! $this->tablesReady()) {
            return back()->with('error', $this->missingTablesMessage());
        }

        $examSessionId = (int) $examRoomInvigilator->exam_session_id;
        $examRoomInvigilator->delete();

        return redirect()
            ->route('principal.exams.room-invigilators.index', [
                'exam_session_id' => (int) $request->query('exam_session_id', $examSessionId),
            ])
            ->with('status', 'Invigilator assignment removed successfully.');
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('exam_sessions')
            && Schema::hasTable('exam_rooms')
            && Schema::hasTable('exam_seating_plans')
            && Schema::hasTable('exam_seat_assignments')
            && Schema::hasTable('exam_room_invigilators');
    }

    private function missingTablesMessage(): string
    {
        return 'Exam invigilator tables are missing on server. Please run latest migrations: php artisan migrate --force';
    }
}
