<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryDemandRequest;
use App\Http\Requests\Inventory\UpdateInventoryDemandRequest;
use App\Models\InventoryDemand;
use App\Models\InventoryItem;
use App\Models\Teacher;
use App\Services\InventoryDemandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherInventoryDemandController extends Controller
{
    public function index(): View
    {
        $teacher = $this->teacherFromAuth();

        $demands = InventoryDemand::query()
            ->withCount('lines')
            ->where('teacher_id', $teacher->id)
            ->latest('request_date')
            ->latest('id')
            ->paginate(15);

        return view('teacher.my-inventory.demands.index', [
            'demands' => $demands,
        ]);
    }

    public function create(): View
    {
        return view('teacher.my-inventory.demands.create', [
            'items' => $this->stationeryItems(),
        ]);
    }

    public function store(
        StoreInventoryDemandRequest $request,
        InventoryDemandService $service
    ): RedirectResponse {
        $teacher = $this->teacherFromAuth();
        $demand = $service->createDemand($teacher->id, $request->validated());

        return redirect()
            ->route('teacher.my-inventory.demands.show', $demand)
            ->with('success', 'Stationery demand submitted successfully.');
    }

    public function show(InventoryDemand $demand): View
    {
        $teacher = $this->teacherFromAuth();
        abort_if((int) $demand->teacher_id !== (int) $teacher->id, 403);

        $demand->load([
            'lines.item:id,name',
            'reviewer:id,name',
        ]);

        return view('teacher.my-inventory.demands.show', [
            'demand' => $demand,
            'items' => $this->stationeryItems(),
        ]);
    }

    public function update(
        UpdateInventoryDemandRequest $request,
        InventoryDemand $demand,
        InventoryDemandService $service
    ): RedirectResponse {
        $teacher = $this->teacherFromAuth();
        abort_if((int) $demand->teacher_id !== (int) $teacher->id, 403);

        $service->updateDemand($demand, $teacher->id, $request->validated());

        return redirect()
            ->route('teacher.my-inventory.demands.show', $demand)
            ->with('success', 'Demand updated successfully.');
    }

    private function stationeryItems()
    {
        return InventoryItem::query()
            ->where('category', 'stationery')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function teacherFromAuth(): Teacher
    {
        $teacher = Auth::user()?->teacher;
        abort_if($teacher === null, 403, 'Teacher profile not found.');

        return $teacher;
    }
}
