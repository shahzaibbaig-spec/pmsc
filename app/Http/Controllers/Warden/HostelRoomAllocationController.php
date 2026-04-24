<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\StoreHostelRoomAllocationRequest;
use App\Http\Requests\Warden\UpdateHostelRoomAllocationRequest;
use App\Models\HostelRoom;
use App\Models\Student;
use App\Services\HostelRoomAllocationService;
use App\Services\HostelRoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class HostelRoomAllocationController extends Controller
{
    public function __construct(
        private readonly HostelRoomAllocationService $allocationService,
        private readonly HostelRoomService $hostelRoomService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'room_id' => ['nullable', 'integer', 'exists:hostel_rooms,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'status' => ['nullable', 'in:active,shifted,completed'],
            'date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $payload = $this->allocationService->getAllocationList($filters, $request->user());

        return view('warden.hostel.allocations.index', $payload);
    }

    public function create(Request $request): View
    {
        $options = $this->allocationService->getCreateFormOptions($request->user());

        return view('warden.hostel.allocations.create', $options);
    }

    public function store(StoreHostelRoomAllocationRequest $request): RedirectResponse
    {
        try {
            $this->allocationService->allocateStudentToRoom(
                (int) $request->integer('student_id'),
                (int) $request->integer('hostel_room_id'),
                $request->validated(),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['allocation' => $exception->getMessage()]);
        }

        return redirect()
            ->route('warden.hostel.allocations.index')
            ->with('success', 'Student allocated to hostel room successfully.');
    }

    public function editShift(Student $student, Request $request): View
    {
        try {
            $options = $this->allocationService->getShiftFormOptions((int) $student->id, $request->user());
        } catch (RuntimeException $exception) {
            abort(404, $exception->getMessage());
        }

        return view('warden.hostel.allocations.shift', [
            'student' => $student,
            'currentAllocation' => $options['current_allocation'],
            'availableRooms' => $options['available_rooms'],
        ]);
    }

    public function shift(UpdateHostelRoomAllocationRequest $request, Student $student): RedirectResponse
    {
        try {
            $this->allocationService->shiftStudentRoom(
                (int) $student->id,
                (int) $request->integer('hostel_room_id'),
                $request->validated(),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['allocation' => $exception->getMessage()]);
        }

        return redirect()
            ->route('warden.hostel.allocations.index')
            ->with('success', 'Student room shifted successfully.');
    }

    public function remove(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'allocated_to' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->allocationService->removeStudentFromRoom(
                (int) $student->id,
                (string) $validated['allocated_to'],
                $validated['remarks'] ?? null,
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['allocation' => $exception->getMessage()]);
        }

        return redirect()
            ->route('warden.hostel.allocations.index')
            ->with('success', 'Student room allocation closed successfully.');
    }

    public function roomStudents(HostelRoom $room, Request $request): View
    {
        try {
            $students = $this->allocationService->getRoomStudents((int) $room->id, $request->user());
            $occupancy = $this->hostelRoomService->getRoomOccupancySummary((int) $room->id, $request->user());
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('warden.hostel.rooms.students', [
            'room' => $room,
            'students' => $students,
            'occupancy' => $occupancy,
        ]);
    }
}
