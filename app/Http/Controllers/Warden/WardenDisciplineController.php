<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\WardenDisciplineFilterRequest;
use App\Models\DisciplineComplaint;
use App\Services\WardenDisciplineService;
use Illuminate\View\View;

class WardenDisciplineController extends Controller
{
    public function __construct(
        private readonly WardenDisciplineService $wardenDisciplineService
    ) {
    }

    public function index(WardenDisciplineFilterRequest $request): View
    {
        $reportData = $this->wardenDisciplineService->getReports($request->validated());

        return view('warden.discipline-reports.index', $reportData);
    }

    public function show(DisciplineComplaint $report): View
    {
        return view('warden.discipline-reports.show', [
            'report' => $this->wardenDisciplineService->getReportDetail($report),
        ]);
    }
}
