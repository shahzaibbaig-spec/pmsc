<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\StoreHostelNightAttendanceRequest;
use App\Services\HostelNightAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class HostelNightAttendanceController extends Controller
{
    public function __construct(
        private readonly HostelNightAttendanceService $nightAttendanceService
    ) {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'room_id' => ['nullable', 'integer', 'exists:hostel_rooms,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'status' => ['nullable', 'in:present,absent,on_leave,late_return'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $date = (string) ($validated['date'] ?? now()->toDateString());
        $payload = $this->nightAttendanceService->getNightAttendanceByDate($date, [
            'search' => $validated['search'] ?? null,
            'room_id' => $validated['room_id'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'per_page' => $validated['per_page'] ?? null,
        ], $request->user());

        return view('warden.hostel.night-attendance.index', $payload);
    }

    public function create(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'room_id' => ['nullable', 'integer', 'exists:hostel_rooms,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        $date = (string) ($validated['date'] ?? now()->toDateString());
        $payload = $this->nightAttendanceService->getNightAttendanceByDate($date, [
            'search' => $validated['search'] ?? null,
            'room_id' => $validated['room_id'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'per_page' => 100,
        ], $request->user());

        return view('warden.hostel.night-attendance.create', $payload);
    }

    public function store(StoreHostelNightAttendanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $summary = $this->nightAttendanceService->markAttendance(
                $validated['rows'],
                (string) $validated['attendance_date'],
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['night_attendance' => $exception->getMessage()]);
        }

        return redirect()
            ->route('warden.hostel.night-attendance.index', [
                'date' => $summary['attendance_date'],
            ])
            ->with(
                'success',
                'Night attendance saved successfully. Created: '.$summary['created'].', Updated: '.$summary['updated'].'.'
            );
    }
}
