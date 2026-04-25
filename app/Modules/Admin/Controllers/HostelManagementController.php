<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Modules\Admin\Requests\StoreHostelRequest;
use App\Modules\Admin\Requests\UpdateHostelRequest;
use App\Services\HostelManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class HostelManagementController extends Controller
{
    public function __construct(
        private readonly HostelManagementService $hostelManagementService
    ) {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        return view('modules.admin.hostels.index', [
            'hostels' => $this->hostelManagementService->getHostelList($validated),
            'filters' => [
                'search' => (string) ($validated['search'] ?? ''),
                'per_page' => (int) ($validated['per_page'] ?? 15),
            ],
        ]);
    }

    public function store(StoreHostelRequest $request): RedirectResponse
    {
        try {
            $this->hostelManagementService->createHostel($request->validated());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['hostel' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.hostels.index')
            ->with('success', 'Hostel created successfully.');
    }

    public function update(UpdateHostelRequest $request, Hostel $hostel): RedirectResponse
    {
        try {
            $this->hostelManagementService->updateHostel($hostel, $request->validated());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['hostel' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.hostels.index')
            ->with('success', 'Hostel updated successfully.');
    }

    public function destroy(Hostel $hostel): RedirectResponse
    {
        try {
            $this->hostelManagementService->deleteHostel($hostel);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['hostel' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.hostels.index')
            ->with('success', 'Hostel deleted successfully.');
    }
}

