<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Services\WardenDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WardenDashboardController extends Controller
{
    public function __construct(
        private readonly WardenDashboardService $wardenDashboardService
    ) {
    }

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        return view('warden.dashboard', [
            'summary' => $this->wardenDashboardService->getDashboardData($validated['date'] ?? null),
        ]);
    }
}
