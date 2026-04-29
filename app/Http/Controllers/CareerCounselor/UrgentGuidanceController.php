<?php

namespace App\Http\Controllers\CareerCounselor;

use App\Http\Controllers\Controller;
use App\Models\CareerCounselingSession;
use App\Services\CareerCounselorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UrgentGuidanceController extends Controller
{
    public function __construct(private readonly CareerCounselorService $careerCounselorService) {}

    public function index(): View
    {
        return view('career-counselor.urgent.index', ['cases' => $this->careerCounselorService->getUrgentCases()]);
    }

    public function mark(Request $request, CareerCounselingSession $session): RedirectResponse
    {
        $validated = $request->validate(['urgent_reason' => ['nullable', 'string']]);
        $this->careerCounselorService->markUrgentGuidance($session, $request->user(), $validated['urgent_reason'] ?? null);

        return back()->with('success', 'Urgent guidance marked.');
    }

    public function unmark(Request $request, CareerCounselingSession $session): RedirectResponse
    {
        $this->careerCounselorService->unmarkUrgentGuidance($session, $request->user());

        return back()->with('success', 'Urgent guidance removed.');
    }

    public function visibility(Request $request, CareerCounselingSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'visibility' => ['required', 'in:private,student,parent,student_parent'],
            'public_summary' => ['nullable', 'string'],
        ]);

        $this->careerCounselorService->updateVisibility($session, $validated, $request->user());

        return back()->with('success', 'Student/parent visibility updated.');
    }
}
