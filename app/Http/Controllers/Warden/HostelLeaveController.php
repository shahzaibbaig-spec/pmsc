<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\StoreHostelLeaveRequest;
use App\Models\HostelLeaveRequest;
use App\Services\HostelLeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class HostelLeaveController extends Controller
{
    public function __construct(
        private readonly HostelLeaveService $hostelLeaveService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'hostel_room_id' => ['nullable', 'integer', 'exists:hostel_rooms,id'],
            'status' => ['nullable', 'in:pending,approved,rejected,returned'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $payload = $this->hostelLeaveService->getLeaveSummary($filters, $request->user());

        return view('warden.hostel.leaves.index', $payload);
    }

    public function create(Request $request): View
    {
        $payload = $this->hostelLeaveService->getLeaveSummary([
            'per_page' => 10,
        ], $request->user());

        return view('warden.hostel.leaves.create', [
            'students' => $payload['students'],
            'rooms' => $payload['rooms'],
        ]);
    }

    public function store(StoreHostelLeaveRequest $request): RedirectResponse
    {
        try {
            $this->hostelLeaveService->createLeaveRequest(
                $request->validated(),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['leave' => $exception->getMessage()]);
        }

        return redirect()
            ->route('warden.hostel.leaves.index')
            ->with('success', 'Hostel leave request recorded successfully.');
    }

    public function show(HostelLeaveRequest $leave, Request $request): View
    {
        try {
            $leave = $this->hostelLeaveService->getLeaveDetail($leave, $request->user());
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('warden.hostel.leaves.show', [
            'leave' => $leave,
        ]);
    }

    public function approve(Request $request, HostelLeaveRequest $leave): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->hostelLeaveService->approveLeave(
                (int) $leave->id,
                $request->user(),
                $validated['remarks'] ?? null
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['leave' => $exception->getMessage()]);
        }

        return back()->with('success', 'Leave request approved successfully.');
    }

    public function reject(Request $request, HostelLeaveRequest $leave): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->hostelLeaveService->rejectLeave(
                (int) $leave->id,
                $request->user(),
                $validated['remarks'] ?? null
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['leave' => $exception->getMessage()]);
        }

        return back()->with('success', 'Leave request rejected.');
    }

    public function returned(Request $request, HostelLeaveRequest $leave): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->hostelLeaveService->markReturned(
                (int) $leave->id,
                $request->user(),
                $validated['remarks'] ?? null
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['leave' => $exception->getMessage()]);
        }

        return back()->with('success', 'Student marked as returned to hostel.');
    }
}
