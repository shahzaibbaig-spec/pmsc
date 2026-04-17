<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\StoreHostelRoomRequest;
use App\Http\Requests\Warden\UpdateHostelRoomRequest;
use App\Models\HostelRoom;
use App\Services\HostelRoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class HostelRoomController extends Controller
{
    public function __construct(
        private readonly HostelRoomService $hostelRoomService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'floor_number' => ['nullable', 'integer', 'min:0'],
            'gender' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'in:0,1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $payload = $this->hostelRoomService->getRoomList($filters);

        return view('warden.hostel.rooms.index', $payload);
    }

    public function create(): View
    {
        return view('warden.hostel.rooms.create');
    }

    public function store(StoreHostelRoomRequest $request): RedirectResponse
    {
        try {
            $this->hostelRoomService->createRoom(
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['hostel_room' => $exception->getMessage()]);
        }

        return redirect()
            ->route('warden.hostel.rooms.index')
            ->with('success', 'Hostel room created successfully.');
    }

    public function edit(HostelRoom $room): View
    {
        $occupancy = $this->hostelRoomService->getRoomOccupancySummary((int) $room->id);

        return view('warden.hostel.rooms.edit', [
            'room' => $room,
            'occupancy' => $occupancy,
        ]);
    }

    public function update(UpdateHostelRoomRequest $request, HostelRoom $room): RedirectResponse
    {
        try {
            $this->hostelRoomService->updateRoom(
                $room,
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['hostel_room' => $exception->getMessage()]);
        }

        return redirect()
            ->route('warden.hostel.rooms.index')
            ->with('success', 'Hostel room updated successfully.');
    }
}

