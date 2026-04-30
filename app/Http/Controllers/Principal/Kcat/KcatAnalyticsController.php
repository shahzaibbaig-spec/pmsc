<?php

namespace App\Http\Controllers\Principal\Kcat;

use App\Http\Controllers\Controller;
use App\Services\Kcat\KcatReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KcatAnalyticsController extends Controller
{
    public function __construct(private readonly KcatReportService $reportService) {}

    public function index(Request $request): View
    {
        return view('principal.kcat.analytics.index', ['summary' => $this->reportService->getGradeWiseSummary($request->only(['session']))]);
    }

    public function gradeWiseSummary(Request $request): View
    {
        return $this->index($request);
    }
}
