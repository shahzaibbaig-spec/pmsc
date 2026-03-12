<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayrollProfile;
use App\Models\PayrollRun;
use App\Modules\Payroll\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PayrollDashboardController extends Controller
{
    public function __construct(private readonly PayrollService $service)
    {
    }

    public function index(Request $request): View
    {
        $monthOptions = $this->monthOptions();
        $defaultMonth = (int) now()->format('m');
        $defaultYear = (int) now()->format('Y');
        $yearOptions = collect($monthOptions)
            ->map(fn (string $month): int => (int) substr($month, 0, 4))
            ->push($defaultYear)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $monthDropdown = collect(range(1, 12))
            ->map(fn (int $month): array => [
                'value' => $month,
                'label' => Carbon::createFromDate($defaultYear, $month, 1)->format('F'),
            ])
            ->values()
            ->all();

        return view('modules.principal.payroll.dashboard.index', [
            'monthOptions' => $monthDropdown,
            'yearOptions' => $yearOptions,
            'defaultMonth' => $defaultMonth,
            'defaultYear' => $defaultYear,
            'canGenerate' => ($request->user()?->can('generate_payroll') ?? false)
                || ($request->user()?->can('generate_salary_sheet') ?? false),
            'canViewSlips' => $request->user()?->can('view_salary_slips') ?? false,
            'canEditProfiles' => ($request->user()?->can('manage_payroll_profiles') ?? false)
                || ($request->user()?->can('edit_salary_structure') ?? false),
            'canViewSheet' => ($request->user()?->can('generate_payroll') ?? false)
                || ($request->user()?->can('generate_salary_sheet') ?? false),
            'canViewReports' => ($request->user()?->can('view_payroll_reports') ?? false)
                || ($request->user()?->can('view_payroll') ?? false),
            'canViewProfiles' => ($request->user()?->can('manage_payroll_profiles') ?? false)
                || ($request->user()?->can('view_payroll') ?? false),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:generated,paid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $monthKey = sprintf('%04d-%02d', (int) $filters['year'], (int) $filters['month']);

        $monthBaseQuery = PayrollItem::query()
            ->whereHas('payrollRun', function ($query) use ($monthKey): void {
                $query->where('month', $monthKey);
            });

        $monthlyStats = (clone $monthBaseQuery)->get(['id', 'status', 'net_salary']);
        $totalInMonth = $monthlyStats->count();
        $paidStaff = $monthlyStats->where('status', 'paid')->count();
        $pendingStaff = max($totalInMonth - $paidStaff, 0);
        $thisMonthPayroll = round((float) $monthlyStats->sum('net_salary'), 2);

        $totalStaff = PayrollProfile::query()
            ->where('status', 'active')
            ->whereHas('user', function ($query): void {
                $query->where(function ($inner): void {
                    $inner->where('status', 'active')->orWhereNull('status');
                });
            })
            ->count();

        $tableQuery = PayrollItem::query()
            ->with([
                'user:id,name,email',
                'payrollRun:id,month,run_date,status',
                'payrollProfile:id,user_id,status,bank_name,account_no',
            ])
            ->whereHas('payrollRun', function ($query) use ($monthKey): void {
                $query->where('month', $monthKey);
            })
            ->when(($filters['search'] ?? null) !== null && trim((string) $filters['search']) !== '', function ($query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', function ($query) use ($filters): void {
                $query->where('status', (string) $filters['status']);
            })
            ->orderByDesc('payroll_run_id')
            ->orderBy('user_id');

        $items = $tableQuery
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        $rows = $items->getCollection()
            ->map(fn (PayrollItem $item): array => $this->mapItemRow($item))
            ->values()
            ->all();

        return response()->json([
            'rows' => $rows,
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
            'kpis' => [
                'total_staff' => $totalStaff,
                'this_month_payroll' => $thisMonthPayroll,
                'paid_staff' => $paidStaff,
                'pending_staff' => $pendingStaff,
            ],
            'selected_month' => $monthKey,
            'month_label' => $this->service->monthLabel($monthKey),
            'run_exists' => $totalInMonth > 0,
        ]);
    }

    public function item(PayrollItem $payrollItem): JsonResponse
    {
        $payrollItem->loadMissing([
            'payrollRun:id,month,run_date,status',
            'user:id,name,email',
            'payrollProfile:id,user_id,basic_salary,allowances,deductions,bank_name,account_no,status',
            'payrollProfile.allowancesRows:id,payroll_profile_id,title,amount',
            'payrollProfile.deductionsRows:id,payroll_profile_id,title,amount',
        ]);

        $profile = $payrollItem->payrollProfile;
        $allowanceRows = $profile?->allowancesRows
            ? $profile->allowancesRows->map(fn ($row): array => [
                'title' => $row->title,
                'amount' => round((float) $row->amount, 2),
            ])->values()->all()
            : [];
        $deductionRows = $profile?->deductionsRows
            ? $profile->deductionsRows->map(fn ($row): array => [
                'title' => $row->title,
                'amount' => round((float) $row->amount, 2),
            ])->values()->all()
            : [];

        return response()->json([
            'item_id' => (int) $payrollItem->id,
            'pdf_url' => route('principal.payroll.slips.pdf', $payrollItem),
            'payload' => $this->service->salarySlipPayload($payrollItem),
            'profile' => $profile ? [
                'id' => (int) $profile->id,
                'status' => (string) $profile->status,
                'basic_salary' => round((float) $profile->basic_salary, 2),
                'base_allowances' => round((float) $profile->allowances, 2),
                'base_deductions' => round((float) $profile->deductions, 2),
                'bank_name' => (string) ($profile->bank_name ?? ''),
                'account_no' => (string) ($profile->account_no ?? ''),
                'allowance_rows' => $allowanceRows,
                'deduction_rows' => $deductionRows,
                'edit_url' => route('principal.payroll.profiles.edit', $profile),
            ] : null,
        ]);
    }

    public function profile(PayrollProfile $payrollProfile): JsonResponse
    {
        $payrollProfile->loadMissing([
            'user:id,name,email',
            'allowancesRows:id,payroll_profile_id,title,amount',
            'deductionsRows:id,payroll_profile_id,title,amount',
        ]);

        $allowanceRows = $payrollProfile->allowancesRows
            ->map(fn ($row): array => [
                'title' => $row->title,
                'amount' => round((float) $row->amount, 2),
            ])
            ->values()
            ->all();

        $deductionRows = $payrollProfile->deductionsRows
            ->map(fn ($row): array => [
                'title' => $row->title,
                'amount' => round((float) $row->amount, 2),
            ])
            ->values()
            ->all();

        $allowancesTotal = round((float) $payrollProfile->allowances + (float) $payrollProfile->allowancesRows->sum('amount'), 2);
        $deductionsTotal = round((float) $payrollProfile->deductions + (float) $payrollProfile->deductionsRows->sum('amount'), 2);
        $netEstimate = round(max((float) $payrollProfile->basic_salary + $allowancesTotal - $deductionsTotal, 0), 2);

        return response()->json([
            'profile' => [
                'id' => (int) $payrollProfile->id,
                'status' => (string) $payrollProfile->status,
                'employee_name' => (string) ($payrollProfile->user?->name ?? 'Employee'),
                'employee_email' => (string) ($payrollProfile->user?->email ?? ''),
                'basic_salary' => round((float) $payrollProfile->basic_salary, 2),
                'base_allowances' => round((float) $payrollProfile->allowances, 2),
                'base_deductions' => round((float) $payrollProfile->deductions, 2),
                'allowances_total' => $allowancesTotal,
                'deductions_total' => $deductionsTotal,
                'net_estimate' => $netEstimate,
                'bank_name' => (string) ($payrollProfile->bank_name ?? ''),
                'account_no' => (string) ($payrollProfile->account_no ?? ''),
                'allowance_rows' => $allowanceRows,
                'deduction_rows' => $deductionRows,
                'edit_url' => route('principal.payroll.profiles.edit', $payrollProfile),
            ],
        ]);
    }

    private function monthOptions(): array
    {
        $existingMonths = PayrollRun::query()
            ->orderByDesc('month')
            ->pluck('month')
            ->values()
            ->all();

        return collect($existingMonths)
            ->merge($this->service->monthOptions(12, 3))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function mapItemRow(PayrollItem $item): array
    {
        $month = (string) ($item->payrollRun?->month ?? '');

        return [
            'id' => (int) $item->id,
            'payroll_profile_id' => (int) $item->payroll_profile_id,
            'employee_name' => (string) ($item->user?->name ?? 'Employee'),
            'employee_email' => (string) ($item->user?->email ?? ''),
            'month' => $month,
            'month_label' => $this->service->monthLabel($month),
            'basic_salary' => round((float) $item->basic_salary, 2),
            'allowances_total' => round((float) $item->allowances_total, 2),
            'deductions_total' => round((float) $item->deductions_total, 2),
            'net_salary' => round((float) $item->net_salary, 2),
            'status' => (string) $item->status,
            'paid_at' => optional($item->paid_at)->format('Y-m-d H:i'),
            'salary_slip_pdf_url' => route('principal.payroll.slips.pdf', $item),
            'profile_edit_url' => route('principal.payroll.profiles.edit', $item->payroll_profile_id),
        ];
    }
}
