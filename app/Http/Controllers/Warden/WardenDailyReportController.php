<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\StoreWardenDailyReportRequest;
use App\Services\WardenDailyReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class WardenDailyReportController extends Controller
{
    public function __construct(
        private readonly WardenDailyReportService $wardenDailyReportService
    ) {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        try {
            $payload = $this->wardenDailyReportService->getReportList($request->user(), $validated);
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('warden.daily-reports.index', $payload);
    }

    public function create(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $reportDate = (string) ($validated['date'] ?? now()->toDateString());

        try {
            $students = $this->wardenDailyReportService->getHostelStudents($request->user());
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('warden.daily-reports.create', [
            'reportDate' => $reportDate,
            'students' => $students,
        ]);
    }

    public function store(StoreWardenDailyReportRequest $request): RedirectResponse
    {
        try {
            $report = $this->wardenDailyReportService->saveDailyReport(
                $request->user(),
                $request->validated()
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['daily_report' => $exception->getMessage()]);
        }

        return redirect()
            ->route('warden.daily-reports.show', $report)
            ->with('success', 'Warden daily report saved successfully.');
    }

    public function show(int $dailyReport, Request $request): View
    {
        try {
            $report = $this->wardenDailyReportService->getReportForWarden($dailyReport, $request->user())
                ->load([
                    'hostel:id,name',
                    'createdBy:id,name',
                    'attendance.student:id,name,student_id,class_id',
                    'attendance.student.classRoom:id,name,section',
                    'disciplineLogs.student:id,name,student_id,class_id',
                    'disciplineLogs.student.classRoom:id,name,section',
                    'healthLogs.student:id,name,student_id,class_id',
                    'healthLogs.student.classRoom:id,name,section',
                ]);
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('warden.daily-reports.show', [
            'report' => $report,
        ]);
    }
}

