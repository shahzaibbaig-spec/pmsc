<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ReviewInventoryDemandRequest;
use App\Models\InventoryDemand;
use App\Services\InventoryDemandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryDemandReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $demands = InventoryDemand::query()
            ->with(['teacher.user:id,name,email'])
            ->withCount('lines')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('session', 'like', "%{$search}%")
                        ->orWhereHas('teacher.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('request_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.demands.index', [
            'demands' => $demands,
            'status' => $status,
            'search' => $search,
            'statusOptions' => ['pending', 'approved', 'partially_approved', 'rejected', 'fulfilled'],
        ]);
    }

    public function show(InventoryDemand $demand): View
    {
        $demand->load([
            'teacher.user:id,name,email',
            'lines.item:id,name,current_stock,unit',
            'reviewer:id,name',
            'issues.lines.item:id,name',
            'issues.issuer:id,name',
        ]);

        return view('inventory.demands.show', [
            'demand' => $demand,
        ]);
    }

    public function review(
        ReviewInventoryDemandRequest $request,
        InventoryDemand $demand,
        InventoryDemandService $service
    ): RedirectResponse {
        $service->reviewDemand($demand, $request->validated(), (int) $request->user()->id);

        return redirect()
            ->route('inventory.demands.show', $demand)
            ->with('success', 'Demand reviewed successfully.');
    }

    public function fulfill(
        Request $request,
        InventoryDemand $demand,
        InventoryDemandService $service
    ): RedirectResponse {
        abort_unless($request->user()?->can('fulfill_inventory_demands'), 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $service->fulfillDemand(
            $demand,
            (int) $request->user()->id,
            $validated['note'] ?? null
        );

        return redirect()
            ->route('inventory.demands.show', $demand)
            ->with('success', 'Demand fulfilled successfully.');
    }
}
