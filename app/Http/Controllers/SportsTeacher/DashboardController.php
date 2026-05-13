<?php

namespace App\Http\Controllers\SportsTeacher;

use App\Http\Controllers\Controller;
use App\Services\SportsObservationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly SportsObservationService $sportsObservationService)
    {
    }

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
        ]);

        $data = $this->sportsObservationService->getObservationsForSportsTeacher($request->user(), [
            'session' => $filters['session'] ?? null,
            'date' => $filters['date'] ?? null,
            'per_page' => 10,
        ]);

        return view('sports-teacher.dashboard', $data);
    }
}
