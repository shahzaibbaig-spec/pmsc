<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Modules\Payroll\Requests\GeneratePayrollRunRequest;
use App\Modules\Payroll\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class PayrollRunController extends Controller
{
    public function __construct(private readonly PayrollService $service)
    {
    }

    public function generateForm(): View
    {
        $recentRuns = PayrollRun::query()
            ->withCount('items')
            ->withSum('items as net_total', 'net_salary')
            ->orderByDesc('month')
            ->limit(12)
            ->get(['id', 'month', 'run_date', 'status']);

        return view('modules.principal.payroll.generate.index', [
            'monthOptions' => $this->service->monthOptions(),
            'defaultMonth' => now()->format('Y-m'),
            'recentRuns' => $recentRuns,
            'latestSummary' => session('latest_payroll_summary'),
        ]);
    }

    public function generate(GeneratePayrollRunRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $summary = $this->service->generateMonthlyPayroll(
                (string) $request->validated('month'),
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Monthly payroll generated successfully.',
                'summary' => $summary,
            ]);
        }

        return redirect()
            ->route('principal.payroll.generate.index')
            ->with('status', 'Monthly payroll generated successfully.')
            ->with('latest_payroll_summary', $summary);
    }

    public function salarySheet(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $data = $this->service->salarySheet($filters['month'] ?? null);
        $run = $data['run'];
        $items = $data['items'];
        $summary = $data['summary'];

        $monthOptions = PayrollRun::query()
            ->orderByDesc('month')
            ->pluck('month')
            ->values()
            ->all();
        if (empty($monthOptions)) {
            $monthOptions = $this->service->monthOptions();
        }

        return view('modules.principal.payroll.sheet.index', [
            'run' => $run,
            'items' => $items,
            'summary' => $summary,
            'monthOptions' => $monthOptions,
            'selectedMonth' => $filters['month'] ?? ($run?->month ?? ''),
            'monthLabel' => $run ? $this->service->monthLabel((string) $run->month) : null,
        ]);
    }

    public function salarySlips(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $items = PayrollItem::query()
            ->with([
                'user:id,name,email',
                'payrollRun:id,month,run_date',
            ])
            ->when(($filters['month'] ?? null) !== null && $filters['month'] !== '', function ($query) use ($filters): void {
                $query->whereHas('payrollRun', function ($subQuery) use ($filters): void {
                    $subQuery->where('month', (string) $filters['month']);
                });
            })
            ->when(($filters['search'] ?? null) !== null && trim((string) $filters['search']) !== '', function ($query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->whereHas('user', function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('payroll_run_id')
            ->orderBy('user_id')
            ->paginate(15)
            ->withQueryString();

        $monthOptions = PayrollRun::query()
            ->orderByDesc('month')
            ->pluck('month')
            ->values()
            ->all();
        if (empty($monthOptions)) {
            $monthOptions = $this->service->monthOptions();
        }

        return view('modules.principal.payroll.slips.index', [
            'items' => $items,
            'monthOptions' => $monthOptions,
            'filters' => [
                'month' => $filters['month'] ?? '',
                'search' => $filters['search'] ?? '',
            ],
        ]);
    }

    public function salarySlipPdf(PayrollItem $payrollItem): Response
    {
        $payload = $this->service->salarySlipPayload($payrollItem);

        $pdf = Pdf::loadView('modules.reports.salary-slip', [
            'data' => $payload,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('salary_slip_'.$payrollItem->id.'.pdf');
    }

    public function reports(Request $request): View
    {
        $filters = $request->validate([
            'month_from' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'month_to' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $report = $this->service->reportsData(
            $filters['month_from'] ?? null,
            $filters['month_to'] ?? null
        );

        return view('modules.principal.payroll.reports.index', [
            'summary' => $report['summary'],
            'rows' => $report['rows'],
            'monthOptions' => $this->service->monthOptions(24, 3),
            'filters' => [
                'month_from' => $filters['month_from'] ?? '',
                'month_to' => $filters['month_to'] ?? '',
            ],
        ]);
    }
}
