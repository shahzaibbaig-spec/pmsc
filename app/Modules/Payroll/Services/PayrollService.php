<?php

namespace App\Modules\Payroll\Services;

use App\Models\PayrollItem;
use App\Models\PayrollProfile;
use App\Models\PayrollRun;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollService
{
    public function monthOptions(int $backward = 6, int $forward = 3): array
    {
        $current = now()->startOfMonth();
        $months = [];

        for ($i = $backward; $i >= 1; $i--) {
            $months[] = $current->copy()->subMonths($i)->format('Y-m');
        }

        $months[] = $current->format('Y-m');

        for ($i = 1; $i <= $forward; $i++) {
            $months[] = $current->copy()->addMonths($i)->format('Y-m');
        }

        return $months;
    }

    public function eligibleUsers(): Collection
    {
        return User::query()
            ->where(function ($query): void {
                $query->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->whereHas('roles', function ($query): void {
                $query->where('name', '!=', 'Student');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function createProfile(array $payload, array $allowanceItems, array $deductionItems): PayrollProfile
    {
        return DB::transaction(function () use ($payload, $allowanceItems, $deductionItems): PayrollProfile {
            $profile = PayrollProfile::query()->create($payload);
            $this->syncBreakdownRows($profile, $allowanceItems, $deductionItems);

            return $profile;
        });
    }

    public function updateProfile(PayrollProfile $profile, array $payload, array $allowanceItems, array $deductionItems): PayrollProfile
    {
        return DB::transaction(function () use ($profile, $payload, $allowanceItems, $deductionItems): PayrollProfile {
            $profile->forceFill($payload)->save();
            $this->syncBreakdownRows($profile, $allowanceItems, $deductionItems);

            return $profile->refresh();
        });
    }

    public function generateMonthlyPayroll(string $month, int $generatedBy): array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new RuntimeException('Month must be in YYYY-MM format.');
        }

        if (PayrollRun::query()->where('month', $month)->exists()) {
            throw new RuntimeException('Payroll for this month is already generated.');
        }

        $profiles = PayrollProfile::query()
            ->with([
                'user:id,name,email,status',
                'allowancesRows:id,payroll_profile_id,title,amount',
                'deductionsRows:id,payroll_profile_id,title,amount',
            ])
            ->where('status', 'active')
            ->whereHas('user', function ($query): void {
                $query->where(function ($inner): void {
                    $inner->where('status', 'active')
                        ->orWhereNull('status');
                });
            })
            ->get();

        if ($profiles->isEmpty()) {
            throw new RuntimeException('No active payroll profiles found.');
        }

        $createdCount = 0;
        $totalNet = 0.0;
        $totalGross = 0.0;

        DB::transaction(function () use ($profiles, $month, $generatedBy, &$createdCount, &$totalNet, &$totalGross): void {
            $run = PayrollRun::query()->create([
                'month' => $month,
                'run_date' => now()->toDateString(),
                'status' => 'generated',
                'generated_by' => $generatedBy,
            ]);

            foreach ($profiles as $profile) {
                $baseSalary = (float) $profile->basic_salary;
                $allowancesTotal = round(
                    (float) $profile->allowances + (float) $profile->allowancesRows->sum('amount'),
                    2
                );
                $deductionsTotal = round(
                    (float) $profile->deductions + (float) $profile->deductionsRows->sum('amount'),
                    2
                );
                $netSalary = round(max($baseSalary + $allowancesTotal - $deductionsTotal, 0), 2);

                $run->items()->create([
                    'payroll_profile_id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'basic_salary' => round($baseSalary, 2),
                    'allowances_total' => $allowancesTotal,
                    'deductions_total' => $deductionsTotal,
                    'net_salary' => $netSalary,
                    'status' => 'generated',
                ]);

                $createdCount++;
                $totalGross += $baseSalary + $allowancesTotal;
                $totalNet += $netSalary;
            }
        });

        return [
            'month' => $month,
            'month_label' => $this->monthLabel($month),
            'profiles_processed' => $createdCount,
            'total_gross' => round($totalGross, 2),
            'total_net' => round($totalNet, 2),
        ];
    }

    public function salarySheet(?string $month = null): array
    {
        $run = PayrollRun::query()
            ->when($month !== null && $month !== '', function ($query) use ($month): void {
                $query->where('month', $month);
            })
            ->orderByDesc('month')
            ->first();

        if (! $run) {
            return [
                'run' => null,
                'items' => collect(),
                'summary' => [
                    'employees' => 0,
                    'basic_total' => 0.0,
                    'allowances_total' => 0.0,
                    'deductions_total' => 0.0,
                    'net_total' => 0.0,
                ],
            ];
        }

        $items = PayrollItem::query()
            ->with([
                'user:id,name,email',
                'payrollProfile:id,user_id,bank_name,account_no',
            ])
            ->where('payroll_run_id', $run->id)
            ->orderBy('user_id')
            ->get();

        return [
            'run' => $run,
            'items' => $items,
            'summary' => [
                'employees' => $items->count(),
                'basic_total' => round((float) $items->sum('basic_salary'), 2),
                'allowances_total' => round((float) $items->sum('allowances_total'), 2),
                'deductions_total' => round((float) $items->sum('deductions_total'), 2),
                'net_total' => round((float) $items->sum('net_salary'), 2),
            ],
        ];
    }

    public function salarySlipPayload(PayrollItem $item): array
    {
        $item->loadMissing([
            'user:id,name,email',
            'payrollRun:id,month,run_date',
            'payrollProfile:id,user_id,basic_salary,allowances,deductions,bank_name,account_no',
            'payrollProfile.allowancesRows:id,payroll_profile_id,title,amount',
            'payrollProfile.deductionsRows:id,payroll_profile_id,title,amount',
        ]);

        $allowances = collect();
        if ((float) $item->payrollProfile?->allowances > 0) {
            $allowances->push([
                'title' => 'Base Allowances',
                'amount' => round((float) $item->payrollProfile?->allowances, 2),
            ]);
        }
        foreach ($item->payrollProfile?->allowancesRows ?? [] as $row) {
            $allowances->push([
                'title' => $row->title,
                'amount' => round((float) $row->amount, 2),
            ]);
        }

        $deductions = collect();
        if ((float) $item->payrollProfile?->deductions > 0) {
            $deductions->push([
                'title' => 'Base Deductions',
                'amount' => round((float) $item->payrollProfile?->deductions, 2),
            ]);
        }
        foreach ($item->payrollProfile?->deductionsRows ?? [] as $row) {
            $deductions->push([
                'title' => $row->title,
                'amount' => round((float) $row->amount, 2),
            ]);
        }

        return [
            'school' => $this->schoolMeta(),
            'employee' => [
                'name' => (string) ($item->user?->name ?? 'Employee'),
                'email' => (string) ($item->user?->email ?? ''),
                'employee_ref' => (string) ($item->user?->id ?? '-'),
            ],
            'payroll' => [
                'month' => (string) ($item->payrollRun?->month ?? ''),
                'month_label' => $this->monthLabel((string) ($item->payrollRun?->month ?? '')),
                'run_date' => optional($item->payrollRun?->run_date)->format('Y-m-d'),
                'status' => (string) $item->status,
            ],
            'bank' => [
                'bank_name' => (string) ($item->payrollProfile?->bank_name ?? ''),
                'account_no' => (string) ($item->payrollProfile?->account_no ?? ''),
            ],
            'components' => [
                'basic_salary' => round((float) $item->basic_salary, 2),
                'allowances' => $allowances->all(),
                'deductions' => $deductions->all(),
            ],
            'summary' => [
                'allowances_total' => round((float) $item->allowances_total, 2),
                'deductions_total' => round((float) $item->deductions_total, 2),
                'net_salary' => round((float) $item->net_salary, 2),
            ],
        ];
    }

    public function reportsData(?string $monthFrom, ?string $monthTo): array
    {
        $base = PayrollRun::query()
            ->when($monthFrom !== null && $monthFrom !== '', function ($query) use ($monthFrom): void {
                $query->where('month', '>=', $monthFrom);
            })
            ->when($monthTo !== null && $monthTo !== '', function ($query) use ($monthTo): void {
                $query->where('month', '<=', $monthTo);
            });

        $runs = (clone $base)
            ->with('items:id,payroll_run_id,basic_salary,allowances_total,deductions_total,net_salary')
            ->orderByDesc('month')
            ->get(['id', 'month', 'run_date', 'status']);

        $summary = [
            'runs' => $runs->count(),
            'employees_processed' => 0,
            'basic_total' => 0.0,
            'allowances_total' => 0.0,
            'deductions_total' => 0.0,
            'net_total' => 0.0,
        ];

        $rows = $runs->map(function (PayrollRun $run) use (&$summary): array {
            $items = $run->items;
            $employeeCount = $items->count();
            $basic = round((float) $items->sum('basic_salary'), 2);
            $allowances = round((float) $items->sum('allowances_total'), 2);
            $deductions = round((float) $items->sum('deductions_total'), 2);
            $net = round((float) $items->sum('net_salary'), 2);

            $summary['employees_processed'] += $employeeCount;
            $summary['basic_total'] += $basic;
            $summary['allowances_total'] += $allowances;
            $summary['deductions_total'] += $deductions;
            $summary['net_total'] += $net;

            return [
                'month' => $run->month,
                'month_label' => $this->monthLabel($run->month),
                'run_date' => optional($run->run_date)->format('Y-m-d'),
                'employees' => $employeeCount,
                'basic_total' => $basic,
                'allowances_total' => $allowances,
                'deductions_total' => $deductions,
                'net_total' => $net,
            ];
        })->values()->all();

        $summary['basic_total'] = round((float) $summary['basic_total'], 2);
        $summary['allowances_total'] = round((float) $summary['allowances_total'], 2);
        $summary['deductions_total'] = round((float) $summary['deductions_total'], 2);
        $summary['net_total'] = round((float) $summary['net_total'], 2);

        return [
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    public function monthLabel(string $month): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $month)->format('F Y');
        } catch (\Throwable) {
            return $month;
        }
    }

    private function schoolMeta(): array
    {
        $setting = SchoolSetting::cached();
        $logoAbsolutePath = null;

        if ($setting?->logo_path) {
            $absolute = public_path('storage/'.$setting->logo_path);
            if (is_file($absolute)) {
                $logoAbsolutePath = $absolute;
            }
        }

        return [
            'name' => $setting?->school_name ?? 'School Management System',
            'logo_absolute_path' => $logoAbsolutePath,
        ];
    }

    private function syncBreakdownRows(PayrollProfile $profile, array $allowanceItems, array $deductionItems): void
    {
        $profile->allowancesRows()->delete();
        $profile->deductionsRows()->delete();

        foreach ($this->normalizedItems($allowanceItems) as $item) {
            $profile->allowancesRows()->create($item);
        }

        foreach ($this->normalizedItems($deductionItems) as $item) {
            $profile->deductionsRows()->create($item);
        }
    }

    private function normalizedItems(array $items): array
    {
        return collect($items)
            ->map(function ($row): array {
                return [
                    'title' => trim((string) ($row['title'] ?? '')),
                    'amount' => round((float) ($row['amount'] ?? 0), 2),
                ];
            })
            ->filter(fn (array $row): bool => $row['title'] !== '' && $row['amount'] > 0)
            ->values()
            ->all();
    }
}
