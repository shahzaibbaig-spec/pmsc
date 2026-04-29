<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\CareerCounselingSession;
use App\Services\CareerCounselorService;
use Illuminate\View\View;

class CareerUrgentCaseController extends Controller
{
    public function __construct(private readonly CareerCounselorService $careerCounselorService) {}

    public function index(): View
    {
        return view('principal.career-urgent-cases.index', ['cases' => $this->careerCounselorService->getUrgentCases()]);
    }

    public function show(CareerCounselingSession $session): View
    {
        return view('career-counselor.sessions.show', ['session' => $session->load(['student.classRoom', 'counselor', 'careerProfile'])]);
    }
}
