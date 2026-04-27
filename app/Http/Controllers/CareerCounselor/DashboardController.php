<?php

namespace App\Http\Controllers\CareerCounselor;

use App\Http\Controllers\Controller;
use App\Services\CareerCounselorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly CareerCounselorService $careerCounselorService)
    {
    }

    public function __invoke(Request $request): View
    {
        return view('career-counselor.dashboard', [
            'stats' => $this->careerCounselorService->dashboardStats($request->user()),
        ]);
    }
}
