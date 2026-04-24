<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\WardenDisciplineFilterRequest;
use App\Models\DisciplineComplaint;
use App\Services\WardenDisciplineService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class WardenDisciplineController extends Controller
{
    public function __construct(
        private readonly WardenDisciplineService $wardenDisciplineService
    ) {
    }

    public function index(WardenDisciplineFilterRequest $request): View
    {
        $reportData = $this->wardenDisciplineService->getReports(
            $request->validated(),
            $request->user()
        );

        return view('warden.discipline-reports.index', $reportData);
    }

    public function show(DisciplineComplaint $report, Request $request): View
    {
        try {
            $report = $this->wardenDisciplineService->getReportDetail($report, $request->user());
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('warden.discipline-reports.show', ['report' => $report]);
    }
}
