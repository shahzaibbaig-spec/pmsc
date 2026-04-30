<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kcat\StoreKcatReportNoteRequest;
use App\Models\CareerProfile;
use App\Models\KcatAttempt;
use App\Services\Kcat\KcatReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KcatReportController extends Controller
{
    public function __construct(private readonly KcatReportService $reportService) {}

    public function show(KcatAttempt $attempt): View
    {
        return view('career-counselor.kcat.reports.show', ['report' => $this->reportService->generateStudentReport($attempt)]);
    }

    public function print(KcatAttempt $attempt): View
    {
        return view('career-counselor.kcat.reports.print', ['report' => $this->reportService->generateStudentReport($attempt)]);
    }

    public function notes(StoreKcatReportNoteRequest $request, KcatAttempt $attempt): RedirectResponse
    {
        $this->reportService->createCounselorNotes($attempt, $request->validated(), $request->user());
        return back()->with('success', 'KCAT report notes saved.');
    }

    public function attach(KcatAttempt $attempt, CareerProfile $profile): RedirectResponse
    {
        $this->reportService->attachReportToCareerProfile($attempt, $profile);
        return back()->with('success', 'KCAT report attached to career profile notes.');
    }
}
