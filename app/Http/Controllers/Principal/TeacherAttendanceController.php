<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\StoreTeacherAttendanceRequest;
use App\Http\Requests\Principal\UpdateTeacherAttendanceRequest;
use App\Models\Teacher;
use App\Models\TeacherAcr;
use App\Models\TeacherAttendance;
use App\Services\TeacherAcrService;
use App\Services\TeacherAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class TeacherAttendanceController extends Controller
{
    public function __construct(
        private readonly TeacherAttendanceService $attendanceService,
        private readonly TeacherAcrService $acrService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $query = TeacherAttendance::query()
            ->with([
                'teacher:id,teacher_id,user_id,employee_code',
                'teacher.user:id,name',
                'markedBy:id,name',
            ])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id');

        if (! empty($filters['teacher_id'])) {
            $query->where('teacher_id', (int) $filters['teacher_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('attendance_date', (string) $filters['date']);
        }

        if (! empty($filters['month'])) {
            $month = Carbon::createFromFormat('Y-m', (string) $filters['month']);
            $query->whereYear('attendance_date', $month->year)
                ->whereMonth('attendance_date', $month->month);
        }

        $attendances = $query
            ->paginate(20)
            ->withQueryString();

        $teachers = Teacher::query()
            ->with('user:id,name')
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id', 'employee_code']);

        return view('principal.teacher-attendance.index', [
            'attendances' => $attendances,
            'teachers' => $teachers,
            'filters' => [
                'teacher_id' => $filters['teacher_id'] ?? '',
                'date' => $filters['date'] ?? '',
                'month' => $filters['month'] ?? '',
            ],
        ]);
    }

    public function create(): View
    {
        $teachers = Teacher::query()
            ->with('user:id,name')
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id', 'employee_code']);

        return view('principal.teacher-attendance.create', [
            'teachers' => $teachers,
        ]);
    }

    public function store(StoreTeacherAttendanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $attendance = $this->attendanceService->markManualAttendance(
                (int) $validated['teacher_id'],
                (string) $validated['attendance_date'],
                (string) $validated['status'],
                $validated['remarks'] ?? null,
                (int) $request->user()->id
            );

            $this->syncAcrForTeacherSession(
                (int) $attendance->teacher_id,
                $this->sessionFromDate((string) $attendance->attendance_date)
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.teacher-attendance.index')
            ->with('success', 'Teacher attendance saved successfully.');
    }

    public function edit(TeacherAttendance $attendance): View
    {
        $attendance->load([
            'teacher:id,teacher_id,user_id,employee_code',
            'teacher.user:id,name',
        ]);

        $teachers = Teacher::query()
            ->with('user:id,name')
            ->orderBy('teacher_id')
            ->get(['id', 'teacher_id', 'user_id', 'employee_code']);

        return view('principal.teacher-attendance.edit', [
            'attendance' => $attendance,
            'teachers' => $teachers,
        ]);
    }

    public function update(UpdateTeacherAttendanceRequest $request, TeacherAttendance $attendance): RedirectResponse
    {
        $originalTeacherId = (int) $attendance->teacher_id;
        $originalDate = (string) optional($attendance->attendance_date)->toDateString();

        try {
            $updated = $this->attendanceService->updateManualAttendance(
                (int) $attendance->id,
                $request->validated(),
                (int) $request->user()->id
            );

            $this->syncAcrForTeacherSession(
                (int) $updated->teacher_id,
                $this->sessionFromDate((string) $updated->attendance_date)
            );

            if (
                $originalTeacherId !== (int) $updated->teacher_id
                || $originalDate !== (string) $updated->attendance_date
            ) {
                $this->syncAcrForTeacherSession(
                    $originalTeacherId,
                    $this->sessionFromDate($originalDate)
                );
            }
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.teacher-attendance.index')
            ->with('success', 'Teacher attendance updated successfully.');
    }

    private function syncAcrForTeacherSession(int $teacherId, string $session): void
    {
        $acr = TeacherAcr::query()
            ->where('teacher_id', $teacherId)
            ->where('session', $session)
            ->first(['id', 'status']);

        if (! $acr instanceof TeacherAcr) {
            return;
        }

        if ($acr->status === TeacherAcr::STATUS_FINALIZED) {
            $this->acrService->markNeedsRefreshIfFinalized($teacherId, $session);

            return;
        }

        $this->acrService->refreshCalculatedFields((int) $acr->id);
    }

    private function sessionFromDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }
}
